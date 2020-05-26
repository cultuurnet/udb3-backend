<?php

namespace CultuurNet\UDB3\Label\Commands;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use ValueObjects\Identity\UUID;

class Create extends AbstractCommand
{
    /**
     * @var LabelName
     */
    private $name;

    /**
     * @var string
     */
    private $visibility;

    /**
     * @var string
     */
    private $privacy;

    /**
     * Create constructor.
     * @param UUID $uuid
     * @param LabelName $name
     * @param Visibility $visibility
     * @param Privacy $privacy
     */
    public function __construct(
        UUID $uuid,
        LabelName $name,
        Visibility $visibility,
        Privacy $privacy
    ) {
        parent::__construct($uuid);

        $this->name = $name;

        // The built-in serialize call does not work on Enum.
        // Just store them internally as string but expose as Enum.
        $this->visibility = $visibility->toNative();
        $this->privacy = $privacy->toNative();
    }

    /**
     * @return LabelName
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
        return Visibility::fromNative($this->visibility);
    }

    /**
     * @return Privacy
     */
    public function getPrivacy()
    {
        return Privacy::fromNative($this->privacy);
    }
}
