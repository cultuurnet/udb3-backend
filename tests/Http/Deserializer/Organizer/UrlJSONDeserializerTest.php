<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Organizer;

use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

class UrlJSONDeserializerTest extends TestCase
{
    private UrlJSONDeserializer $urlJSONDeserializer;

    protected function setUp(): void
    {
        $this->urlJSONDeserializer = new UrlJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_can_serialize_a_valid_url(): void
    {
        $json = '{"url":"http://www.depot.be"}';

        $actual = $this->urlJSONDeserializer->deserialize($json);

        $this->assertEquals(
            new Url('http://www.depot.be'),
            $actual
        );
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_url_is_missing(): void
    {
        $json = '{"foo":"http://www.depot.be"}';

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('Missing value for "url".');

        $this->urlJSONDeserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_url_is_invalid(): void
    {
        $json = '{"url":"http:/www.depot.be"}';

        $this->expectException(\InvalidArgumentException::class);

        $this->urlJSONDeserializer->deserialize($json);
    }
}
