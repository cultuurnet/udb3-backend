<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Commands;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;

class Create extends AbstractCommand
{
    private LabelName $name;

    private string $visibility;

    private string $privacy;

    public function __construct(
        Uuid $uuid,
        LabelName $name,
        Visibility $visibility,
        Privacy $privacy
    ) {
        parent::__construct($uuid);

        $this->name = $name;

        // The built-in serialize call does not work on Enum.
        // Just store them internally as string but expose as Enum.
        $this->visibility = $visibility->toString();
        $this->privacy = $privacy->toString();
    }

    public function getName(): LabelName
    {
        return $this->name;
    }

    public function getVisibility(): Visibility
    {
        return new Visibility($this->visibility);
    }

    public function getPrivacy(): Privacy
    {
        return new Privacy($this->privacy);
    }
}
