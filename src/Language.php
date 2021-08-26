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
    protected $code;

    public function __construct(string $code)
    {
        if (!preg_match('/^[a-z]{2}$/', $code)) {
            throw new \InvalidArgumentException(
                'Invalid language code: ' . $code
            );
        }
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return self
     */
    public static function fromUdb3ModelLanguage(Udb3ModelLanguage $language)
    {
        return new self($language->toString());
    }
}
