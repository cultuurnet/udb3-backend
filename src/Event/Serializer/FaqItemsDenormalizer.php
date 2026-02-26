<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Serializer;

use CultuurNet\UDB3\Model\ValueObject\Faq\Answer;
use CultuurNet\UDB3\Model\ValueObject\Faq\Faq;
use CultuurNet\UDB3\Model\ValueObject\Faq\FaqItems;
use CultuurNet\UDB3\Model\ValueObject\Faq\Question;
use CultuurNet\UDB3\Model\ValueObject\Faq\TranslatedFaqItem;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class FaqItemsDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): FaqItems
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new InvalidArgumentException("FaqItemsDenormalizer does not support $class.");
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException('FAQ items data should be an array.');
        }

        $faqItems = new FaqItems();

        foreach ($data as $itemData) {
            if (!isset($itemData['id'])) {
                $itemData['id'] = Uuid::uuid4()->toString();
            }
            $id = $itemData['id'];

            $languageKeys = array_filter(
                array_keys($itemData),
                static function (string $key): bool {
                    if ($key === 'id') {
                        return false;
                    }
                    try {
                        new Language($key);
                        return true;
                    } catch (InvalidArgumentException) {
                        return false;
                    }
                }
            );
            $languageKeys = array_values($languageKeys);

            $originalLanguageKey = $languageKeys[0];
            $originalLanguage = new Language($originalLanguageKey);
            $translatedFaqItem = new TranslatedFaqItem(
                $originalLanguage,
                $this->denormalizeFaqItem($id, $itemData[$originalLanguageKey])
            );

            foreach (array_slice($languageKeys, 1) as $languageKey) {
                $translatedFaqItem = $translatedFaqItem->withTranslation(
                    new Language($languageKey),
                    $this->denormalizeFaqItem($id, $itemData[$languageKey])
                );
            }

            $faqItems = $faqItems->with($translatedFaqItem);
        }

        return $faqItems;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === FaqItems::class;
    }

    private function denormalizeFaqItem(string $id, array $data): Faq
    {
        return new Faq(
            $id,
            new Question($data['question']),
            new Answer($data['answer'])
        );
    }
}
