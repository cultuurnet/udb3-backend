<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\StringLiteral;

interface WriteRepositoryInterface
{
    public function save(
        UUID $uuid,
        StringLiteral $name,
        Visibility $visibility,
        Privacy $privacy
    );

    public function updateVisible(UUID $uuid): void;

    public function updateInvisible(UUID $uuid): void;

    public function updatePublic(UUID $uuid): void;

    public function updatePrivate(UUID $uuid): void;

    public function updateExcluded(UUID $uuid): void;

    public function updateIncluded(UUID $uuid): void;

    public function updateCountIncrement(UUID $uuid): void;

    public function updateCountDecrement(UUID $uuid): void;
}
