<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Faq;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

final class FaqItems extends Collection
{
    public function __construct(TranslatedFaqItem ...$values)
    {
        parent::__construct(...$values);
    }

    public function getById(string $id): ?TranslatedFaqItem
    {
        foreach ($this->toArray() as $item) {
            if ($item->getOriginalValue()->id === $id) {
                return $item;
            }
        }
        return null;
    }
}
