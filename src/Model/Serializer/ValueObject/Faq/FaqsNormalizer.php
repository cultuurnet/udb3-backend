<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Faq;

use CultuurNet\UDB3\Model\ValueObject\Faq\Faqs;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class FaqsNormalizer implements NormalizerInterface
{
    /**
     * @param Faqs $faqs
     */
    public function normalize($faqs, $format = null, array $context = []): array
    {
        $faqsArray = [];
        foreach ($faqs->toArray() as $translatedFaq) {
            $faqArray = ['id' => $translatedFaq->getOriginalValue()->id->toString()];
            foreach ($translatedFaq->getLanguages() as $language) {
                $faq = $translatedFaq->getTranslation($language);
                $faqArray[$language->getCode()] = [
                    'question' => $faq->question->toString(),
                    'answer' => $faq->answer->toString(),
                ];
            }
            $faqsArray[] = $faqArray;
        }
        return $faqsArray;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Faqs;
    }
}
