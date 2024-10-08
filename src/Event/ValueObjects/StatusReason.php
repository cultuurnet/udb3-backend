<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use CultuurNet\UDB3\Language;
use InvalidArgumentException;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason as much as possible
 */
final class StatusReason
{
    private Language $language;

    private string $reason;

    public function __construct(Language $language, string $reason)
    {
        if (empty($reason)) {
            throw new InvalidArgumentException('The reason string can\'t be empty.');
        }

        $this->language = $language;
        $this->reason = $reason;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
