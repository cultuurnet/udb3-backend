<?php

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

interface WriteRepositoryInterface
{
    public function save(
        UUID $uuid,
        StringLiteral $name,
        Visibility $visibility,
        Privacy $privacy,
        UUID $parentUuid = null
    );


    public function updateVisible(UUID $uuid);


    public function updateInvisible(UUID $uuid);


    public function updatePublic(UUID $uuid);


    public function updatePrivate(UUID $uuid);


    public function updateCountIncrement(UUID $uuid);


    public function updateCountDecrement(UUID $uuid);
}
