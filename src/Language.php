<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language as Udb3ModelLanguage;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Translation\Language instead where possible.
 */
class Language
{
    protected string $code;

    public function __construct(string $code)
    {
        if (!preg_match('/^[a-z]{2}$/', $code)) {
            throw new \InvalidArgumentException(
                'Invalid language code: ' . $code
            );
        }
        $this->code = $code;
    }

    public function __toString(): string
    {
        return $this->code;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public static function fromUdb3ModelLanguage(Udb3ModelLanguage $language): Language
    {
        return new self($language->toString());
    }
}
