<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\FeatureToggles;

use Silex\Application;
use Silex\ServiceProviderInterface;

class FeatureTogglesProvider implements ServiceProviderInterface
{
    /**
     * @var array
     */
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function register(Application $app)
    {
        $config = $this->config;
    }

    public function boot(Application $app)
    {
    }
}
