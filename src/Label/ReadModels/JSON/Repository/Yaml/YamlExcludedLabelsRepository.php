<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Yaml;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ExcludedLabelsRepository;

final class YamlExcludedLabelsRepository implements ExcludedLabelsRepository
{
    /**
     * @var string[]
     */
    private array $labels;

    public function __construct(array $labels)
    {
        $this->labels = $labels;
    }

    public function getAll(): array
    {
        return $this->labels;
    }
}
