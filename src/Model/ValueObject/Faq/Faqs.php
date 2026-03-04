<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Faq;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Faq\FaqsNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

final class Faqs extends Collection
{
    public function __construct(TranslatedFaq ...$faqs)
    {
        parent::__construct(...$faqs);
    }

    /**
     * @param Faqs|mixed $other
     */
    public function sameAs($other): bool
    {
        $faqsNormalizer = new FaqsNormalizer();
        $stripId = static fn (array $faq) => array_diff_key($faq, ['id' => null]);
        return array_map($stripId, $faqsNormalizer->normalize($this)) === array_map($stripId, $faqsNormalizer->normalize($other));
    }
}
