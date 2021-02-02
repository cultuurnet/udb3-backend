<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use Broadway\Serializer\SerializableInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status as Udb3ModelStatus;
use InvalidArgumentException;

final class Status implements SerializableInterface
{
    /**
     * @var StatusType
     */
    private $type;

    /**
     * @var StatusReason[]
     */
    private $reason;

    public function __construct(StatusType $type, array $reason)
    {
        $this->ensureTranslationsAreUnique($reason);
        $this->type = $type;
        $this->reason = $reason;
    }

    public function getType(): StatusType
    {
        return $this->type;
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
            StatusType::fromNative($data['type']),
            $statusReasons
        );
    }

    public function serialize(): array
    {
        $serialized = [
            'type' => $this->type->toNative(),
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
        $type = StatusType::fromNative($udb3ModelStatus->getType()->toString());
        $reasons = [];

        $udb3ModelReason = $udb3ModelStatus->getReason();

        $languages = $udb3ModelReason ? $udb3ModelReason->getLanguages()->toArray() : [];
        foreach ($languages as $language) {
            $translation = $udb3ModelReason->getTranslation($language);
            $reasons[] = new StatusReason(new Language($language->getCode()), $translation->toString());
        }

        return new self($type, $reasons);
    }
}
