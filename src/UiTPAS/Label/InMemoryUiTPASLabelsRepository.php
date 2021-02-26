<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Label;

use CultuurNet\UDB3\Label;

final class InMemoryUiTPASLabelsRepository implements UiTPASLabelsRepository
{
    /**
     * @var Label[]
     */
    private $labels;

    /**
     * @param Label[] $labels
     *   Associative array of card system ids as keys and corresponding Label objects as values.
     *   See UiTPASLabelsRepository::loadAll()
     */
    public function __construct(array $labels)
    {
        $this->labels = $labels;
    }

    public function loadAll(): array
    {
        return $this->labels;
    }

    /**
     * @param string[] $labels
     *   Associative array of card system ids as keys and corresponding Label objects as values.
     */
    public static function fromStrings(array $labels): self
    {
        $labelVOs = [];
        foreach ($labels as $cardSystemId => $label) {
            $labelVOs[$cardSystemId] = new Label($label);
        }

        return new self($labelVOs);
    }
}
