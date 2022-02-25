<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Organizer;

use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

class UrlJSONDeserializerTest extends TestCase
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
            new Url('http://www.depot.be'),
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
