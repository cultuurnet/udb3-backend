<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use Monolog\Processor\ProcessorInterface;

final class ContextFilteringProcessor implements ProcessorInterface
{
    /**
     * @var string[]
     */
    private $filterKeys;

    /**
     * @param string[] $filterKeys
     */
    public function __construct(array $filterKeys)
    {
        $this->filterKeys = $filterKeys;
    }

    public function __invoke(array $record): array
    {
        foreach ($this->filterKeys as $filterKey) {
            unset($record['context'][$filterKey]);
        }
        return $record;
    }
}
