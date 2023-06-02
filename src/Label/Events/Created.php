<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class Created extends AbstractEvent
{
    public const VISIBILITY = 'visibility';
    public const PRIVACY = 'privacy';

    private Visibility $visibility;

    private Privacy $privacy;

    public function __construct(
        UUID $uuid,
        string $name,
        Visibility $visibility,
        Privacy $privacy
    ) {
        parent::__construct($uuid, $name);

        $this->visibility = $visibility;
        $this->privacy = $privacy;
        $this->excluded = $excluded;
    }

    public function getVisibility(): Visibility
    {
        return $this->visibility;
    }

    public function getPrivacy(): Privacy
    {
        return $this->privacy;
    }

    public function isExcluded(): bool
    {
        return false;
    }

    public static function deserialize(array $data): Created
    {
        return new self(
            new UUID($data[self::UUID]),
            $data[self::NAME],
            new Visibility($data[self::VISIBILITY]),
            new Privacy($data[self::PRIVACY]),
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            self::VISIBILITY => $this->getVisibility()->toString(),
            self::PRIVACY => $this->getPrivacy()->toString(),
        ];
    }
}
