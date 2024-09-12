<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Logger decorator to enrich log message context.
 */
class ContextEnrichingLogger implements LoggerInterface
{
    use LoggerTrait;

    protected LoggerInterface $decoratee;

    protected array $context;


    public function __construct(LoggerInterface $decoratee, array $context)
    {
        $this->decoratee = $decoratee;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = []): void
    {
        $enrichedContext = $this->context + $context;

        $this->decoratee->log($level, $message, $enrichedContext);
    }
}
