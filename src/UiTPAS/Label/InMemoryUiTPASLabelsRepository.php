<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Label;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;

final class InMemoryUiTPASLabelsRepository implements UiTPASLabelsRepository
{
    private array $labels;

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
            $labelVOs[$cardSystemId] = new Label(new LabelName($label));
        }

        return new self($labelVOs);
    }
}
