<?php

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

interface WriteRepositoryInterface
{
    /**
     * @param UUID $uuid
     * @param StringLiteral $name
     * @param Visibility $visibility
     * @param Privacy $privacy
     * @param UUID|null $parentUuid
     */
    public function save(
        UUID $uuid,
        StringLiteral $name,
        Visibility $visibility,
        Privacy $privacy,
        UUID $parentUuid = null
    );

    /**
     * @param UUID $uuid
     */
    public function updateVisible(UUID $uuid);

    /**
     * @param UUID $uuid
     */
    public function updateInvisible(UUID $uuid);

    /**
     * @param UUID $uuid
     */
    public function updatePublic(UUID $uuid);

    /**
     * @param UUID $uuid
     */
    public function updatePrivate(UUID $uuid);

    /**
     * @param UUID $uuid
     */
    public function updateCountIncrement(UUID $uuid);

    /**
     * @param UUID $uuid
     */
    public function updateCountDecrement(UUID $uuid);
}
