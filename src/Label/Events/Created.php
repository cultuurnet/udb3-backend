<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;

class Created extends AbstractEvent
{
    public const VISIBILITY = 'visibility';
    public const PRIVACY = 'privacy';

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @var Privacy
     */
    private $privacy;

    public function __construct(
        UUID $uuid,
        LabelName $name,
        Visibility $visibility,
        Privacy $privacy
    ) {
        parent::__construct($uuid, $name);

        $this->visibility = $visibility;
        $this->privacy = $privacy;
    }

    public function getVisibility(): Visibility
    {
        return $this->visibility;
    }

    public function getPrivacy(): Privacy
    {
        return $this->privacy;
    }

    /**
     * @return Created
     */
    public static function deserialize(array $data)
    {
        return new self(
            new UUID($data[self::UUID]),
            new LabelName($data[self::NAME]),
            new Visibility($data[self::VISIBILITY]),
            new Privacy($data[self::PRIVACY])
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
