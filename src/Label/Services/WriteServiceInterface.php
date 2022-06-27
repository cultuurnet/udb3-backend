<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Services;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;

interface WriteServiceInterface
{
    public function create(LabelName $name, Visibility $visibility, Privacy $privacy): UUID;

    public function makeVisible(UUID $uuid): void;

    public function makeInvisible(UUID $uuid): void;

    public function makePublic(UUID $uuid): void;

    public function makePrivate(UUID $uuid): void;
}
