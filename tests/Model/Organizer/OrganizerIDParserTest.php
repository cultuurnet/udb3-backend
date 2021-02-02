<?php

namespace CultuurNet\UDB3\Model\Organizer;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

class OrganizerIDParserTest extends TestCase
{
    /**
     * @var OrganizerIDParser
     */
    private $parser;

    public function setUp()
    {
        $this->parser = new OrganizerIDParser();
    }

    /**
     * @test
     * @dataProvider organizerUrlDataProvider
     *
     * @param string $url
     * @param string $uuid
     */
    public function it_should_return_an_organizer_id_from_the_given_organizer_url($url, $uuid)
    {
        $url = new Url($url);
        $expected = new UUID($uuid);
        $actual = $this->parser->fromUrl($url);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function organizerUrlDataProvider()
    {
        return [
            'regular' => [
                'url' => 'http://io.uitdatabank.be/organizer/118353f3-dd1a-4c8f-845a-f0c625261332',
                'uuid' => '118353f3-dd1a-4c8f-845a-f0c625261332',
            ],
            'plural' => [
                'url' => 'http://io.uitdatabank.be/organizers/118353f3-dd1a-4c8f-845a-f0c625261332',
                'uuid' => '118353f3-dd1a-4c8f-845a-f0c625261332',
            ],
            'https' => [
                'url' => 'https://io.uitdatabank.be/organizer/118353f3-dd1a-4c8f-845a-f0c625261332',
                'uuid' => '118353f3-dd1a-4c8f-845a-f0c625261332',
            ],
            'different_base_url' => [
                'url' => 'http://io-test.uitdatabank.be/organizer/118353f3-dd1a-4c8f-845a-f0c625261332',
                'uuid' => '118353f3-dd1a-4c8f-845a-f0c625261332',
            ],
            'trailing_slash' => [
                'url' => 'http://io.uitdatabank.be/organizer/118353f3-dd1a-4c8f-845a-f0c625261332/',
                'uuid' => '118353f3-dd1a-4c8f-845a-f0c625261332',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_no_uuid_could_be_found_in_the_given_url()
    {
        $this->expectException(\InvalidArgumentException::class);

        $url = new Url('http://publiq.be');
        $this->parser->fromUrl($url);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_no_organizer_uuid_could_be_found_in_the_given_url()
    {
        $this->expectException(\InvalidArgumentException::class);

        $url = new Url('http://io.uitdatabank.be/event/0ccbbd06-44cf-47f2-9be3-e3c643d48484');
        $this->parser->fromUrl($url);
    }
}
