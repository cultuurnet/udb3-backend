<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Faq\Answer;
use CultuurNet\UDB3\Model\ValueObject\Faq\FaqItem;
use CultuurNet\UDB3\Model\ValueObject\Faq\Question;
use CultuurNet\UDB3\Model\ValueObject\Faq\TranslatedFaqItem;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

trait FaqItemEventTrait
{
    public function serialize(): array
    {
        $translations = [];
        foreach ($this->faqItem->getLanguages() as $language) {
            $faqItem = $this->faqItem->getTranslation($language);
            $translations[$language->getCode()] = [
                'question' => $faqItem->question->toString(),
                'answer' => $faqItem->answer->toString(),
            ];
        }

        return parent::serialize() + [
            'faq_item_id' => $this->faqItem->getOriginalValue()->id,
            'original_language' => $this->faqItem->getOriginalLanguage()->getCode(),
            'translations' => $translations,
        ];
    }

    public static function deserialize(array $data): self
    {
        $faqItemId = $data['faq_item_id'];
        $originalLanguageKey = $data['original_language'];
        $originalLanguage = new Language($originalLanguageKey);

        $translatedFaqItem = new TranslatedFaqItem(
            $originalLanguage,
            new FaqItem(
                $faqItemId,
                new Question($data['translations'][$originalLanguageKey]['question']),
                new Answer($data['translations'][$originalLanguageKey]['answer'])
            )
        );

        foreach ($data['translations'] as $languageKey => $translation) {
            if ($languageKey === $originalLanguageKey) {
                continue;
            }
            $translatedFaqItem = $translatedFaqItem->withTranslation(
                new Language($languageKey),
                new FaqItem(
                    $faqItemId,
                    new Question($translation['question']),
                    new Answer($translation['answer'])
                )
            );
        }

        return new self($data['item_id'], $translatedFaqItem);
    }
}
