<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

interface WriteRepositoryInterface
{
    public function save(
        Uuid $uuid,
        string $name,
        Visibility $visibility,
        Privacy $privacy
    ): void;

    public function updateVisible(Uuid $uuid): void;

    public function updateInvisible(Uuid $uuid): void;

    public function updatePublic(Uuid $uuid): void;

    public function updatePrivate(Uuid $uuid): void;

    public function updateExcluded(Uuid $uuid): void;

    public function updateIncluded(Uuid $uuid): void;
}
