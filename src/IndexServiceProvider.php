<?php

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\ReadModel\Index\Projector;
use CultuurNet\UDB3\ReadModel\Index\UDB2Projector;
use CultuurNet\UDB3\Silex\User\UserServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Domain;

class IndexServiceProvider implements ServiceProviderInterface
{
    public const PROJECTOR = 'index.projector';
    public const UDB2_PROJECTOR = 'index.udb2.projector';

    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['index.table_name'] = new StringLiteral('index_readmodel');

        $app['index.repository'] = $app->share(
            function (Application $app) {
                return new \CultuurNet\UDB3\ReadModel\Index\Doctrine\DBALRepository(
                    $app['dbal_connection'],
                    $app['index.table_name'],
                    $app['entity_iri_generator_factory']
                );
            }
        );

        $app[self::PROJECTOR] = $app->share(
            function (Application $app) {
                $projector = new Projector(
                    $app['index.repository'],
                    $app['local_domain'],
                    $app['iri_offer_identifier_factory']
                );

                return $projector;
            }
        );

        $app[self::UDB2_PROJECTOR] = $app->share(
            function (Application $app) {
                return new UDB2Projector(
                    $app['index.repository'],
                    $app[UserServiceProvider::ITEM_BASE_ADAPTER_FACTORY],
                    Domain::specifyType('uitdatabank.be')
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
