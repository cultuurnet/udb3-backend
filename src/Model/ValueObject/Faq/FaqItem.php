<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Faq;

final class FaqItem
{
    public function __construct(
        public readonly string $id,
        public readonly Question $question,
        public readonly Answer $answer
    ) {
    }

    /**
     * @param FaqItem|mixed $other
     */
    public function sameAs($other): bool
    {
        return get_class($this) === get_class($other) &&
            $this->id === $other->id &&
            $this->question->sameAs($other->question) &&
            $this->answer->sameAs($other->answer);
    }
}
