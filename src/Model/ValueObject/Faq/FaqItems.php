<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Faq;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

final class FaqItems extends Collection
{
    public function __construct(TranslatedFaqItem ...$faqItems)
    {
        parent::__construct(...$faqItems);
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

    /**
     * @param FaqItems|mixed $other
     */
    public function sameAs($other): bool
    {
        if (get_class($this) !== get_class($other)) {
            return false;
        }

        $thisItems = $this->toArray();
        $otherItems = $other->toArray();

        if (count($thisItems) !== count($otherItems)) {
            return false;
        }

        foreach ($thisItems as $index => $translatedFaqItem) {
            $otherTranslatedFaqItem = $otherItems[$index];

            if ($translatedFaqItem->getOriginalLanguage()->getCode() !== $otherTranslatedFaqItem->getOriginalLanguage()->getCode()) {
                return false;
            }

            $languages = $translatedFaqItem->getLanguages();
            if ($languages->getLength() !== $otherTranslatedFaqItem->getLanguages()->getLength()) {
                return false;
            }

            foreach ($languages as $language) {
                try {
                    if (!$translatedFaqItem->getTranslation($language)->sameAs($otherTranslatedFaqItem->getTranslation($language))) {
                        return false;
                    }
                } catch (\OutOfBoundsException) {
                    return false;
                }
            }
        }

        return true;
    }
}
