<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\StringLiteral;

class Entity implements \JsonSerializable
{
    public const ID = 'uuid';
    public const NAME = 'name';
    public const VISIBILITY = 'visibility';
    public const PRIVACY = 'privacy';
    public const EXCLUDED = 'excluded';

    private UUID $uuid;

    private StringLiteral $name;

    private Visibility $visibility;

    private Privacy $privacy;

    private bool $excluded;

    public function __construct(
        UUID $uuid,
        StringLiteral $name,
        Visibility $visibility,
        Privacy $privacy,
        bool $excluded = false
    ) {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->visibility = $visibility;
        $this->privacy = $privacy;
        $this->excluded = $excluded;
    }

    public function getUuid(): UUID
    {
        return $this->uuid;
    }

    public function getName(): StringLiteral
    {
        return $this->name;
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
        return $this->excluded;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return [
            self::ID => $this->uuid->toString(),
            self::NAME => $this->name->toNative(),
            self::VISIBILITY => $this->visibility->toString(),
            self::PRIVACY => $this->privacy->toString(),
            self::EXCLUDED => $this->excluded,
        ];
    }
}
