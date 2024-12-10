<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

interface WriteRepositoryInterface
{
    public function save(
        UUID $uuid,
        string $name,
        Visibility $visibility,
        Privacy $privacy
    ): void;

    public function updateVisible(UUID $uuid): void;

    public function updateInvisible(UUID $uuid): void;

    public function updatePublic(UUID $uuid): void;

    public function updatePrivate(UUID $uuid): void;

    public function updateExcluded(UUID $uuid): void;

    public function updateIncluded(UUID $uuid): void;
}
