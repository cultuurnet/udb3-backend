<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Faq;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class FaqsDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): Faqs
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new InvalidArgumentException("FaqsDenormalizer does not support $class.");
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException('FAQ items data should be an array.');
        }

        $faqs = new Faqs();

        foreach ($data as $faqData) {
            if (!isset($faqData['id'])) {
                $faqData['id'] = Uuid::uuid4()->toString();
            }
            $id = $faqData['id'];

            $languageKeys = array_filter(
                array_keys($faqData),
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
            $translatedFaq = new TranslatedFaq(
                $originalLanguage,
                $this->denormalizeFaq($id, $faqData[$originalLanguageKey])
            );

            foreach (array_slice($languageKeys, 1) as $languageKey) {
                $translatedFaq = $translatedFaq->withTranslation(
                    new Language($languageKey),
                    $this->denormalizeFaq($id, $faqData[$languageKey])
                );
            }

            $faqs = $faqs->with($translatedFaq);
        }

        return $faqs;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === Faqs::class;
    }

    private function denormalizeFaq(string $id, array $data): Faq
    {
        return new Faq(
            new Uuid($id),
            new Question($data['question']),
            new Answer($data['answer'])
        );
    }
}
