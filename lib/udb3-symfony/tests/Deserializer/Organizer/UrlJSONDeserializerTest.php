<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Organizer;

use CultuurNet\Deserializer\MissingValueException;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class UrlJSONDeserializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UrlJSONDeserializer
     */
    private $urlJSONDeserializer;

    protected function setUp()
    {
        $this->urlJSONDeserializer = new UrlJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_can_serialize_a_valid_url()
    {
        $json = new StringLiteral('{"url":"http://www.depot.be"}');

        $actual = $this->urlJSONDeserializer->deserialize($json);

        $this->assertEquals(
            Url::fromNative('http://www.depot.be'),
            $actual
        );
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_url_is_missing()
    {
        $json = new StringLiteral('{"foo":"http://www.depot.be"}');

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('Missing value for "url".');

        $this->urlJSONDeserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_url_is_invalid()
    {
        $json = new StringLiteral('{"url":"http:/www.depot.be"}');

        $this->expectException(\InvalidArgumentException::class);

        $this->urlJSONDeserializer->deserialize($json);
    }
}
