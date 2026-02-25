<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Faq;

final class FaqItems
{
    /** @var array<string, TranslatedFaqItem> */
    private array $items = [];

    public function with(string $id, TranslatedFaqItem $faqItem): self
    {
        $c = clone $this;
        $c->items[$id] = $faqItem;
        return $c;
    }

    public function without(string $id): self
    {
        $c = clone $this;
        unset($c->items[$id]);
        return $c;
    }

    public function getById(string $id): ?TranslatedFaqItem
    {
        return $this->items[$id] ?? null;
    }

    /** @return array<string, TranslatedFaqItem> */
    public function toArray(): array
    {
        return $this->items;
    }
}
