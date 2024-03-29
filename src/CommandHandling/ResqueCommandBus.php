<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Broadway\Domain\Metadata;
use Broadway\EventDispatcher\EventDispatcher;
use Psr\Log\LoggerInterface;

/**
 * Command bus decorator for asynchronous processing with PHP-Resque.
 */
class ResqueCommandBus extends CommandBusDecoratorBase implements ContextAwareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const EVENT_COMMAND_CONTEXT_SET = 'broadway.command_handling.context';

    protected CommandBus $decoratee;

    protected ?Metadata $context = null;

    protected string $queueName;

    protected EventDispatcher $eventDispatcher;

    public function __construct(
        CommandBus $decoratee,
        string $queueName,
        EventDispatcher $dispatcher,
        string $host,
        int $port
    ) {
        \Resque::setBackend($host . ':' . $port);

        parent::__construct($decoratee);
        $this->queueName = $queueName;
        $this->eventDispatcher = $dispatcher;
    }

    public function setContext(?Metadata $context = null): void
    {
        $this->context = $context;

        if ($this->decoratee instanceof ContextAwareInterface) {
            $this->decoratee->setContext($this->context);
        }

        $this->eventDispatcher->dispatch(
            self::EVENT_COMMAND_CONTEXT_SET,
            [
                'context' => $this->context,
            ]
        );
    }

    public function getContext(): ?Metadata
    {
        return $this->context;
    }

    public function dispatch($command): void
    {
        if ($this->decoratee instanceof AuthorizedCommandBusInterface &&
            $command instanceof AuthorizableCommand) {
            if (!$this->decoratee->isAuthorized($command)) {
                throw new CommandAuthorizationException(
                    $this->decoratee->getUserId(),
                    $command
                );
            }
        }

        $args = [];
        $args['command'] = base64_encode(serialize($command));
        $args['context'] = base64_encode(serialize($this->context));
        $id = \Resque::enqueue($this->queueName, QueueJob::class, $args, true);

        if ($command instanceof AsyncCommand) {
            $command->setAsyncCommandId($id);
        }
    }

    /**
     * Really dispatches the command to the proper handler to be executed.
     *
     * @throws \Exception
     */
    public function deferredDispatch(object $command): void
    {
        if ($this->decoratee instanceof LoggerAwareInterface && $this->logger instanceof LoggerInterface) {
            $this->decoratee->setLogger($this->logger);
        }

        parent::dispatch($command);
    }
}
