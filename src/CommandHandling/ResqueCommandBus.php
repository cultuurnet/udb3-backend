<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\Log\ContextEnrichingLogger;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Broadway\Domain\Metadata;
use Broadway\EventDispatcher\EventDispatcherInterface;

/**
 * Command bus decorator for asynchronous processing with PHP-Resque.
 */
class ResqueCommandBus extends CommandBusDecoratorBase implements ContextAwareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const EVENT_COMMAND_CONTEXT_SET = 'broadway.command_handling.context';

    /**
     * @var CommandBusInterface|ContextAwareInterface
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
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param CommandBusInterface $decoratee
     * @param string $queueName
     */
    public function __construct(
        CommandBusInterface $decoratee,
        $queueName,
        EventDispatcherInterface $dispatcher
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
            array(
                'context' => $this->context,
            )
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
     * @param mixed $command
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

        $args = array();
        $args['command'] = base64_encode(serialize($command));
        $args['context'] = base64_encode(serialize($this->context));
        $id = \Resque::enqueue($this->queueName, QueueJob::class, $args, true);

        return $id;
    }

    /**
     * Really dispatches the command to the proper handler to be executed.
     *
     * @param string $jobId
     * @param mixed $command
     *
     * @throws \Exception
     */
    public function deferredDispatch($jobId, $command)
    {
        $exception = null;
        $currentCommandLogger = null;
        if ($this->logger) {
            $jobMetadata = array(
                'job_id' => $jobId,
            );
            $currentCommandLogger = new ContextEnrichingLogger(
                $this->logger,
                $jobMetadata
            );
        }

        if ($currentCommandLogger) {
            $currentCommandLogger->info('job_started');
        }

        if ($this->decoratee instanceof LoggerAwareInterface) {
            $this->decoratee->setLogger($currentCommandLogger);
        }

        try {
            parent::dispatch($command);
        } catch (\Exception $e) {
            if ($currentCommandLogger) {
                $currentCommandLogger->error('job_failed');

                $currentCommandLogger->debug(
                    'exception caused job failure',
                    ['exception' => $e]
                );
            }
            $exception = $e;
        }

        $this->setContext(null);

        if ($currentCommandLogger) {
            $currentCommandLogger->info('job_finished');
        }

        if ($exception) {
            throw $exception;
        }
    }
}
