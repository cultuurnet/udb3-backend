<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use Silex\Application;

class ConfigWriter
{
    /**
     * @var Application
     */
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function merge(array $configSection): void
    {
        $config = $this->application['config'];
        $this->application['config'] = array_merge($config, $configSection);
    }
}
