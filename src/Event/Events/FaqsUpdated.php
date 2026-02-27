<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Faq\Answer;
use CultuurNet\UDB3\Model\ValueObject\Faq\Faq;
use CultuurNet\UDB3\Model\ValueObject\Faq\Faqs;
use CultuurNet\UDB3\Model\ValueObject\Faq\Question;
use CultuurNet\UDB3\Model\ValueObject\Faq\TranslatedFaq;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;

final class FaqsUpdated extends AbstractEvent
{
    public function __construct(string $itemId, public readonly Faqs $faqs)
    {
        parent::__construct($itemId);
    }

    public function serialize(): array
    {
        $serializedItems = [];
        foreach ($this->faqs->toArray() as $translatedFaq) {
            $translations = [];
            foreach ($translatedFaq->getLanguages() as $language) {
                $faq = $translatedFaq->getTranslation($language);
                $translations[$language->getCode()] = [
                    'question' => $faq->question->toString(),
                    'answer' => $faq->answer->toString(),
                ];
            }
            $serializedItems[] = [
                'faq_id' => $translatedFaq->getOriginalValue()->id,
                'original_language' => $translatedFaq->getOriginalLanguage()->getCode(),
                'translations' => $translations,
            ];
        }

        return parent::serialize() + [
            'faqs' => $serializedItems,
        ];
    }

    public static function deserialize(array $data): self
    {
        $faqs = new Faqs();

        foreach ($data['faqs'] as $faqData) {
            $faqId = $faqData['faq_id'];
            $originalLanguageKey = $faqData['original_language'];
            $originalLanguage = new Language($originalLanguageKey);

            $translatedFaq = new TranslatedFaq(
                $originalLanguage,
                new Faq(
                    $faqId,
                    new Question($faqData['translations'][$originalLanguageKey]['question']),
                    new Answer($faqData['translations'][$originalLanguageKey]['answer'])
                )
            );

            foreach ($faqData['translations'] as $languageKey => $translation) {
                if ($languageKey === $originalLanguageKey) {
                    continue;
                }
                $translatedFaq = $translatedFaq->withTranslation(
                    new Language($languageKey),
                    new Faq(
                        $faqId,
                        new Question($translation['question']),
                        new Answer($translation['answer'])
                    )
                );
            }

            $faqs = $faqs->with($translatedFaq);
        }

        return new self($data['item_id'], $faqs);
    }
}
