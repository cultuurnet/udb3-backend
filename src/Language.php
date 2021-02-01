<?php

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language as Udb3ModelLanguage;

/**
 * @todo Replace by CultuurNet\UDB3\Model\ValueObject\Translation\Language.
 */
class Language
{
    protected $code;

    /**
     * @param string $code
     */
    public function __construct($code)
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
     * @param Udb3ModelLanguage $language
     * @return self
     */
    public static function fromUdb3ModelLanguage(Udb3ModelLanguage $language)
    {
        return new self($language->toString());
    }
}
