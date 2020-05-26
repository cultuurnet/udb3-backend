<?php

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use ValueObjects\Identity\UUID;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class Entity implements \JsonSerializable
{
    const ID = 'uuid';
    const NAME = 'name';
    const VISIBILITY = 'visibility';
    const PRIVACY = 'privacy';

    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var StringLiteral
     */
    private $name;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @var Privacy
     */
    private $privacy;

    /**
     * @var UUID
     */
    private $parentUuid;

    /**
     * @var Natural
     */
    private $count;

    /**
     * LabelEntity constructor.
     * @param UUID $uuid
     * @param StringLiteral $name
     * @param Visibility $visibility
     * @param Privacy $privacy
     * @param UUID $parentUuid
     * @param Natural $count
     */
    public function __construct(
        UUID $uuid,
        StringLiteral $name,
        Visibility $visibility,
        Privacy $privacy,
        UUID $parentUuid = null,
        Natural $count = null
    ) {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->visibility = $visibility;
        $this->privacy = $privacy;
        $this->parentUuid = $parentUuid;
        $this->count = $count ? $count : new Natural(0);
    }

    /**
     * @return UUID
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return StringLiteral
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Visibility
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @return Privacy
     */
    public function getPrivacy()
    {
        return $this->privacy;
    }

    /**
     * @return UUID
     */
    public function getParentUuid()
    {
        return $this->parentUuid;
    }

    /**
     * @return Natural
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return [
            self::ID => $this->uuid->toNative(),
            self::NAME => $this->name->toNative(),
            self::VISIBILITY => $this->visibility->toNative(),
            self::PRIVACY => $this->privacy->toNative(),
        ];
    }
}
