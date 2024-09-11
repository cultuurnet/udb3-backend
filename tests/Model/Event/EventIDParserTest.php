<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Event;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

class EventIDParserTest extends TestCase
{
    private EventIDParser $parser;

    public function setUp(): void
    {
        $this->parser = new EventIDParser();
    }

    /**
     * @test
     * @dataProvider eventUrlDataProvider
     *
     * @param string $url
     * @param string $uuid
     */
    public function it_should_return_an_event_id_from_the_given_event_url($url, $uuid): void
    {
        $url = new Url($url);
        $expected = new UUID($uuid);
        $actual = $this->parser->fromUrl($url);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function eventUrlDataProvider()
    {
        return [
            'regular' => [
                'url' => 'http://io.uitdatabank.be/event/118353f3-dd1a-4c8f-845a-f0c625261332',
                'uuid' => '118353f3-dd1a-4c8f-845a-f0c625261332',
            ],
            'plural' => [
                'url' => 'http://io.uitdatabank.be/events/118353f3-dd1a-4c8f-845a-f0c625261332',
                'uuid' => '118353f3-dd1a-4c8f-845a-f0c625261332',
            ],
            'https' => [
                'url' => 'https://io.uitdatabank.be/event/118353f3-dd1a-4c8f-845a-f0c625261332',
                'uuid' => '118353f3-dd1a-4c8f-845a-f0c625261332',
            ],
            'different_base_url' => [
                'url' => 'http://io-test.uitdatabank.be/event/118353f3-dd1a-4c8f-845a-f0c625261332',
                'uuid' => '118353f3-dd1a-4c8f-845a-f0c625261332',
            ],
            'trailing_slash' => [
                'url' => 'http://io.uitdatabank.be/event/118353f3-dd1a-4c8f-845a-f0c625261332/',
                'uuid' => '118353f3-dd1a-4c8f-845a-f0c625261332',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_no_uuid_could_be_found_in_the_given_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $url = new Url('http://publiq.be');
        $this->parser->fromUrl($url);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_no_event_uuid_could_be_found_in_the_given_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $url = new Url('http://io.uitdatabank.be/place/0ccbbd06-44cf-47f2-9be3-e3c643d48484');
        $this->parser->fromUrl($url);
    }
}
