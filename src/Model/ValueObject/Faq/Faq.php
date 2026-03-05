<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Faq;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class Faq
{
    public function __construct(
        public readonly Uuid $id,
        public readonly Question $question,
        public readonly Answer $answer
    ) {
    }
}
