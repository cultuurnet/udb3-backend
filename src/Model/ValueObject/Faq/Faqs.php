<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Faq;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

final class Faqs extends Collection
{
    public function __construct(TranslatedFaq ...$faqs)
    {
        parent::__construct(...$faqs);
    }

    public function getById(string $id): ?TranslatedFaq
    {
        foreach ($this->toArray() as $translatedFaq) {
            if ($translatedFaq->getOriginalValue()->id === $id) {
                return $translatedFaq;
            }
        }
        return null;
    }

    /**
     * @param Faqs|mixed $other
     */
    public function sameAs($other): bool
    {
        if (get_class($this) !== get_class($other)) {
            return false;
        }

        $thisAsArray = $this->toArray();
        $otherAsArray = $other->toArray();

        if (count($thisAsArray) !== count($otherAsArray)) {
            return false;
        }

        foreach ($thisAsArray as $index => $translatedFaq) {
            $otherTranslatedFaq = $otherAsArray[$index];

            if ($translatedFaq->getOriginalLanguage()->getCode() !== $otherTranslatedFaq->getOriginalLanguage()->getCode()) {
                return false;
            }

            $languages = $translatedFaq->getLanguages();
            if ($languages->getLength() !== $otherTranslatedFaq->getLanguages()->getLength()) {
                return false;
            }

            foreach ($languages as $language) {
                try {
                    if (!$translatedFaq->getTranslation($language)->sameAs($otherTranslatedFaq->getTranslation($language))) {
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
