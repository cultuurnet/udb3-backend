<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\Security;

use CultuurNet\UDB3\Offer\Security\Permission\GodUserVoter;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Provides general security services usable by other services.
 */
class GeneralSecurityServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['god_user_voter'] = $app->share(
            function (Application $app) {
                return new GodUserVoter(
                    $app['config']['user_permissions']['allow_all']
                );
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }
}
