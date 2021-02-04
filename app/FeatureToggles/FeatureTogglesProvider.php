<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\FeatureToggles;

use Qandidate\Toggle\Context;
use Qandidate\Toggle\Serializer\InMemoryCollectionSerializer;
use Qandidate\Toggle\ToggleManager;
use Silex\Application;
use Silex\ServiceProviderInterface;

class FeatureTogglesProvider implements ServiceProviderInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * FeatureTogglesProvider constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $config = $this->config;

        // Global context for feature toggles. You can add to this context from
        // your own middlewares, by extending the service, etc.
        $app['toggles.context'] = $app->share(
            function () {
                return new Context();
            }
        );

        // The feature toggles manager which can be asked for the current state
        // of feature toggles.
        $app['toggles'] = $app->share(
            function () use ($config) {
                $serializer = new InMemoryCollectionSerializer();
                $collection = $serializer->deserialize($config);

                $toggles = new ToggleManager($collection);
                return $toggles;
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
