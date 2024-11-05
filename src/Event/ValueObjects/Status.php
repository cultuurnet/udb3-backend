<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status as Udb3ModelStatus;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use InvalidArgumentException;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Calendar\Status as much as possible, and convert to this using
 *   fromUdb3ModelStatus() where still needed.
 */
final class Status implements Serializable
{
    use IsString;

    /**
     * Store the StatusType as a string to prevent serialization issues when the Calendar is part of a command that
     * gets queued in Redis, as the base Enum class that it extends from does not support serialization for some reason.
     */

    /**
     * @var StatusReason[]
     */
    private array $reason;

    public function __construct(StatusType $type, array $reason)
    {
        $this->ensureTranslationsAreUnique($reason);
        $this->value = $type->toString();
        $this->reason = $reason;
    }

    public function getType(): StatusType
    {
        return new StatusType($this->value);
    }

    public function getReason(): array
    {
        return $this->reason;
    }

    public static function deserialize(array $data): Status
    {
        $statusReasons = [];
        if (isset($data['reason'])) {
            foreach ($data['reason'] as $language => $statusReason) {
                $statusReasons[] = new StatusReason(
                    new Language($language),
                    $statusReason
                );
            }
        }

        return new Status(
            new StatusType($data['type']),
            $statusReasons
        );
    }

    public function serialize(): array
    {
        $serialized = [
            'type' => $this->value,
        ];

        $statusReasons = [];
        foreach ($this->reason as $statusReason) {
            $statusReasons[$statusReason->getLanguage()->getCode()] = $statusReason->getReason();
        }

        if (!empty($statusReasons)) {
            $serialized['reason'] = $statusReasons;
        }

        return $serialized;
    }

    /**
     * @param StatusReason[] $statusReason
     */
    private function ensureTranslationsAreUnique(array $statusReason): void
    {
        $languageCodes = \array_map(static function (StatusReason $reason) {
            return $reason->getLanguage()->getCode();
        }, $statusReason);

        if (count($languageCodes) !== count(array_unique($languageCodes))) {
            throw new InvalidArgumentException('Duplicate translations are not allowed for StatusReason');
        }
    }

    public static function fromUdb3ModelStatus(Udb3ModelStatus $udb3ModelStatus): self
    {
        $reasons = [];

        $udb3ModelReason = $udb3ModelStatus->getReason();

        $languages = $udb3ModelReason ? $udb3ModelReason->getLanguages()->toArray() : [];
        foreach ($languages as $language) {
            $translation = $udb3ModelReason->getTranslation($language);
            $reasons[] = new StatusReason(new Language($language->getCode()), $translation->toString());
        }

        return new self(new StatusType($udb3ModelStatus->getType()->toString()), $reasons);
    }
}
