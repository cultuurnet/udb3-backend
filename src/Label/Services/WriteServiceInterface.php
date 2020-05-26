<?php

namespace CultuurNet\UDB3\Label\Services;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use ValueObjects\Identity\UUID;

interface WriteServiceInterface
{
    public function create(LabelName $name, Visibility $visibility, Privacy $privacy): UUID;

    public function makeVisible(UUID $uuid): void;

    public function makeInvisible(UUID $uuid): void;

    public function makePublic(UUID $uuid): void;

    public function makePrivate(UUID $uuid): void;
}
