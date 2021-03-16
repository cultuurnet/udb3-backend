<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Log\ContextEnrichingLogger;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Broadway\Domain\Metadata;
use Broadway\EventDispatcher\EventDispatcher;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Command bus decorator for asynchronous processing with PHP-Resque.
 */
class ResqueCommandBus extends CommandBusDecoratorBase implements ContextAwareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const EVENT_COMMAND_CONTEXT_SET = 'broadway.command_handling.context';

    /**
     * @var CommandBus|ContextAwareInterface
     */
    protected $decoratee;

    /**
     * @var Metadata
     */
    protected $context;

    /**
     * @var string
     */
    protected $queueName;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    public function __construct(
        CommandBus $decoratee,
        string $queueName,
        EventDispatcher $dispatcher
    ) {
        parent::__construct($decoratee);
        $this->queueName = $queueName;
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(Metadata $context = null)
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

    /**
     * Get the current execution context.
     *
     * @return Metadata
     */
    public function getContext()
    {
        return $this->context;
    }


    /**
     * Dispatches the command $command to a queue.
     *
     * @return string the command id
     *
     * @throws CommandAuthorizationException
     */
    public function dispatch($command)
    {
        if ($this->decoratee instanceof AuthorizedCommandBusInterface &&
            $command instanceof AuthorizableCommandInterface) {
            if (!$this->decoratee->isAuthorized($command)) {
                throw new CommandAuthorizationException(
                    $this->decoratee->getUserIdentification()->getId(),
                    $command
                );
            }
        }

        $args = [];
        $args['command'] = base64_encode(serialize($command));
        $args['context'] = base64_encode(serialize($this->context));
        $id = \Resque::enqueue($this->queueName, QueueJob::class, $args, true);

        return $id;
    }

    /**
     * Really dispatches the command to the proper handler to be executed.
     *
     * @param mixed $command
     *
     * @throws \Exception
     */
    public function deferredDispatch($command): void
    {
        if ($this->decoratee instanceof LoggerAwareInterface && $this->logger instanceof LoggerInterface) {
            $this->decoratee->setLogger($this->logger);
        }

        parent::dispatch($command);
    }
}
