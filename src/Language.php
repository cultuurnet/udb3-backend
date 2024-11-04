<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language as Udb3ModelLanguage;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Translation\Language instead where possible.
 */
class Language
{
    use IsString;

    public function __construct(string $code)
    {
        if (!preg_match('/^[a-z]{2}$/', $code)) {
            throw new \InvalidArgumentException(
                'Invalid language code: ' . $code
            );
        }
        $this->value = $code;
    }

    public function getCode(): string
    {
        return $this->value;
    }

    public static function fromUdb3ModelLanguage(Udb3ModelLanguage $language): Language
    {
        return new self($language->toString());
    }

    public function toUdb3ModelLanguage(): Udb3ModelLanguage
    {
        return new Udb3ModelLanguage($this->toString());
    }
}
