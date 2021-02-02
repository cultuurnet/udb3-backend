<?php

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

class MediaObjectIDParserTest extends TestCase
{
    /**
     * @var MediaObjectIDParser
     */
    private $parser;

    public function setUp()
    {
        $this->parser = new MediaObjectIDParser();
    }

    /**
     * @test
     * @dataProvider mediaUrlDataProvider
     *
     * @param string $url
     * @param string $uuid
     */
    public function it_should_return_a_media_id_from_the_given_media_url($url, $uuid)
    {
        $url = new Url($url);
        $expected = new UUID($uuid);
        $actual = $this->parser->fromUrl($url);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function mediaUrlDataProvider()
    {
        return [
            'regular' => [
                'url' => 'http://io.uitdatabank.be/media/118353f3-dd1a-4c8f-845a-f0c625261332',
                'uuid' => '118353f3-dd1a-4c8f-845a-f0c625261332',
            ],
            'https' => [
                'url' => 'https://io.uitdatabank.be/media/118353f3-dd1a-4c8f-845a-f0c625261332',
                'uuid' => '118353f3-dd1a-4c8f-845a-f0c625261332',
            ],
            'different_base_url' => [
                'url' => 'http://io-test.uitdatabank.be/media/118353f3-dd1a-4c8f-845a-f0c625261332',
                'uuid' => '118353f3-dd1a-4c8f-845a-f0c625261332',
            ],
            'trailing_slash' => [
                'url' => 'http://io.uitdatabank.be/media/118353f3-dd1a-4c8f-845a-f0c625261332/',
                'uuid' => '118353f3-dd1a-4c8f-845a-f0c625261332',
            ],
            'image' => [
                'url' => 'http://io.uitdatabank.be/image/118353f3-dd1a-4c8f-845a-f0c625261332',
                'uuid' => '118353f3-dd1a-4c8f-845a-f0c625261332',
            ],
            'images' => [
                'url' => 'http://io.uitdatabank.be/images/118353f3-dd1a-4c8f-845a-f0c625261332',
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
    public function it_should_throw_an_exception_if_no_media_uuid_could_be_found_in_the_given_url()
    {
        $this->expectException(\InvalidArgumentException::class);

        $url = new Url('http://io.uitdatabank.be/event/0ccbbd06-44cf-47f2-9be3-e3c643d48484');
        $this->parser->fromUrl($url);
    }
}
