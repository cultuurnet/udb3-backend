<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use InvalidArgumentException;

class Entity implements \JsonSerializable
{
    public const ID = 'uuid';
    public const NAME = 'name';
    public const VISIBILITY = 'visibility';
    public const PRIVACY = 'privacy';
    public const EXCLUDED = 'excluded';

    /**
     * @var UUID
     */
    private $uuid;

    private LabelName $name;

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

    private int $count;

    private bool $excluded;

    public function __construct(
        UUID $uuid,
        LabelName $name,
        Visibility $visibility,
        Privacy $privacy,
        UUID $parentUuid = null,
        int $count = null,
        bool $excluded = false
    ) {
        if ($count < 0) {
            throw new InvalidArgumentException('Count should be zero or higher.');
        }

        $this->uuid = $uuid;
        $this->name = $name;
        $this->visibility = $visibility;
        $this->privacy = $privacy;
        $this->parentUuid = $parentUuid;
        $this->count = $count ?: 0;
        $this->excluded = $excluded;
    }

    /**
     * @return UUID
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    public function getName(): LabelName
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
     * @return ?UUID
     */
    public function getParentUuid()
    {
        return $this->parentUuid;
    }

    public function getCount(): int
    {
        return $this->count;
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
            self::NAME => $this->name->toString(),
            self::VISIBILITY => $this->visibility->toString(),
            self::PRIVACY => $this->privacy->toString(),
            self::EXCLUDED => $this->excluded,
        ];
    }
}
