<?php

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\SecurityDecoratorBase;

class MediaSecurity extends SecurityDecoratorBase
{
    /**
     * @inheritdoc
     */
    public function isAuthorized(AuthorizableCommandInterface $command)
    {
        // All authenticated users can upload media.
        if ($command->getPermission()->sameValueAs(Permission::MEDIA_UPLOADEN())) {
            return true;
        }

        return parent::isAuthorized($command);
    }
}
