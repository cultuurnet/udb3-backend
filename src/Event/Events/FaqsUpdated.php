<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Faq\Answer;
use CultuurNet\UDB3\Model\ValueObject\Faq\Faq;
use CultuurNet\UDB3\Model\ValueObject\Faq\FaqItems;
use CultuurNet\UDB3\Model\ValueObject\Faq\Question;
use CultuurNet\UDB3\Model\ValueObject\Faq\TranslatedFaqItem;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;

final class FaqsUpdated extends AbstractEvent
{
    public function __construct(string $itemId, public readonly FaqItems $faqItems)
    {
        parent::__construct($itemId);
    }

    public function serialize(): array
    {
        $serializedItems = [];
        foreach ($this->faqItems->toArray() as $translatedFaqItem) {
            $translations = [];
            foreach ($translatedFaqItem->getLanguages() as $language) {
                $faqItem = $translatedFaqItem->getTranslation($language);
                $translations[$language->getCode()] = [
                    'question' => $faqItem->question->toString(),
                    'answer' => $faqItem->answer->toString(),
                ];
            }
            $serializedItems[] = [
                'faq_item_id' => $translatedFaqItem->getOriginalValue()->id,
                'original_language' => $translatedFaqItem->getOriginalLanguage()->getCode(),
                'translations' => $translations,
            ];
        }

        return parent::serialize() + [
            'faq_items' => $serializedItems,
        ];
    }

    public static function deserialize(array $data): self
    {
        $faqItems = new FaqItems();

        foreach ($data['faq_items'] as $itemData) {
            $faqItemId = $itemData['faq_item_id'];
            $originalLanguageKey = $itemData['original_language'];
            $originalLanguage = new Language($originalLanguageKey);

            $translatedFaqItem = new TranslatedFaqItem(
                $originalLanguage,
                new Faq(
                    $faqItemId,
                    new Question($itemData['translations'][$originalLanguageKey]['question']),
                    new Answer($itemData['translations'][$originalLanguageKey]['answer'])
                )
            );

            foreach ($itemData['translations'] as $languageKey => $translation) {
                if ($languageKey === $originalLanguageKey) {
                    continue;
                }
                $translatedFaqItem = $translatedFaqItem->withTranslation(
                    new Language($languageKey),
                    new Faq(
                        $faqItemId,
                        new Question($translation['question']),
                        new Answer($translation['answer'])
                    )
                );
            }

            $faqItems = $faqItems->with($translatedFaqItem);
        }

        return new self($data['item_id'], $faqItems);
    }
}
