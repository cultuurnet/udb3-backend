<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Silex\Console\PurgeModelCommand;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PurgeServiceProvider implements ServiceProviderInterface
{
    private const TABLES_TO_PURGE = [
        'event_permission_readmodel',
        'event_relations',
        'labels_json',
        'label_roles',
        'labels_relations',
        'organizer_permission_readmodel',
        'place_permission_readmodel',
        'place_relations',
        'role_permissions',
        'roles_search_v3',
        'user_roles',
        'offer_metadata',
    ];

    public function register(Application $application)
    {
        $application[PurgeModelCommand::class] = $application->share(
            function (Application $application) {
                return new PurgeModelCommand(
                    self::TABLES_TO_PURGE,
                    $application['dbal_connection']
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
