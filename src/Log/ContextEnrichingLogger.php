<?php

declare(strict_types=1);
/**
 * @file
 */

namespace CultuurNet\UDB3\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Logger decorator to enrich log message context.
 */
class ContextEnrichingLogger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * @var LoggerInterface
     */
    protected $decoratee;

    /**
     * @var array
     */
    protected $context;


    public function __construct(LoggerInterface $decoratee, array $context)
    {
        $this->decoratee = $decoratee;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        $enrichedContext = $this->context + $context;

        $this->decoratee->log($level, $message, $enrichedContext);
    }
}
