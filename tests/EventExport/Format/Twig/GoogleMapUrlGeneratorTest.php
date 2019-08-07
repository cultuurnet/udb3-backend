<?php

namespace CultuurNet\UDB3\EventExport\Format\Twig;

use CultuurNet\UDB3\EventExport\Format\HTML\Twig\GoogleMapUrlGenerator;
use PHPUnit\Framework\TestCase;

class GoogleMapUrlGeneratorTest extends TestCase
{
    const TEST_API_KEY = 'MY_API_KEY';

    /**
     * @var GoogleMapUrlGenerator
     */
    private $generator;

    protected function setUp()
    {
        $this->generator = new GoogleMapUrlGenerator(
            self::TEST_API_KEY
        );
    }

    /**
     * @test
     */
    public function it_can_generate_for_coordinates(): void
    {
        $coordinate1 = '-21.71996,138.35512';
        $coordinate2 = '32.28050,5.06552';
        $coordinate3 = '48.00919,-84.85563';
        $coordinates = [
            $coordinate1,
            $coordinate2,
            $coordinate3,
        ];

        $expected = 'https://maps.googleapis.com/maps/api/staticmap?size=800x400&scale=2';
        $expected .= '&markers='.$coordinate1;
        $expected .= '&markers='.$coordinate2;
        $expected .= '&markers='.$coordinate3;
        $expected .= '&key='.self::TEST_API_KEY;

        $this->assertEquals(
            $expected,
            $this->generator->generateGoogleMapUrl($coordinates, 800, 400)
        );
    }
}
