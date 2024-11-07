<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status as Udb3ModelStatus;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusReason;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

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

    private ?TranslatedStatusReason $reason;

    public function __construct(StatusType $type, ?TranslatedStatusReason $reason)
    {
        $this->value = $type->toString();
        $this->reason = $reason;
    }

    public function getType(): StatusType
    {
        return new StatusType($this->value);
    }

    public function getReason(): ?TranslatedStatusReason
    {
        return $this->reason;
    }

    public static function deserialize(array $data): Status
    {
        $reason = null;
        if (isset($data['reason'])) {
            foreach ($data['reason'] as $language => $statusReason) {
                if ($reason === null) {
                    $reason = new TranslatedStatusReason(new Language($language), new StatusReason($statusReason));
                } else {
                    $reason = $reason->withTranslation(new Language($language), new StatusReason($statusReason));
                }
            }
        }

        return new Status(
            new StatusType($data['type']),
            $reason
        );
    }

    public function serialize(): array
    {
        $serialized = [
            'type' => $this->value,
        ];

        if ($this->reason === null) {
            return $serialized;
        }

        $statusReasons = [];
        foreach ($this->reason->getLanguages() as $language) {
            $statusReasons[$language->getCode()] = $this->reason->getTranslation($language)->toString();
        }
        $serialized['reason'] = $statusReasons;

        return $serialized;
    }

    public static function fromUdb3ModelStatus(Udb3ModelStatus $udb3ModelStatus): self
    {
        return new self($udb3ModelStatus->getType(), $udb3ModelStatus->getReason());
    }
}
