<?php

namespace CultuurNet\UDB3\Silex;

use PHPUnit\Framework\TestCase;
use Silex\Application;

class ConfigWriterTest extends TestCase
{
    /**
     * @test
     */
    public function itWillOverwriteConfigSection()
    {
        $app = new Application();
        $app['config'] = ['foo' => 'bar'];

        $configWriter = new ConfigWriter($app);
        $configWriter->merge(['foo' => 'baz']);

        $this->assertEquals('baz', $app['config']['foo']);
    }

    /**
     * @test
     */
    public function itWillAppendConfigSection()
    {
        $app = new Application();
        $app['config'] = ['foo' => 'bar'];

        $configWriter = new ConfigWriter($app);
        $configWriter->merge(['other' => 'baz']);

        $this->assertEquals('bar', $app['config']['foo']);
        $this->assertEquals('baz', $app['config']['other']);
    }
}
