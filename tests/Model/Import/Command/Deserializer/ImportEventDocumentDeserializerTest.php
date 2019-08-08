<?php

namespace CultuurNet\UDB3\Model\Import\Command\Deserializer;

use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\UDB3\Model\Import\Command\ImportEventDocument;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class ImportEventDocumentDeserializerTest extends TestCase
{
    /**
     * @var ImportEventDocumentDeserializer
     */
    private $deserializer;

    public function setUp()
    {
        $this->deserializer = new ImportEventDocumentDeserializer();
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_id_is_missing()
    {
        $json = json_encode(
            [
                'url' => 'http://io.uitdatabank.be/events/681dc7f1-86a1-43ec-8fa5-09ce28e5a05e',
                'jwt' => 'foo.bar.acme',
                'apiKey' => 'ea450545-1822-4efd-ba2b-34f65c83c439',
            ]
        );

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('id is missing');

        $this->deserializer->deserialize(new StringLiteral($json));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_url_is_missing()
    {
        $json = json_encode(
            [
                'id' => '681dc7f1-86a1-43ec-8fa5-09ce28e5a05e',
                'jwt' => 'foo.bar.acme',
                'apiKey' => 'ea450545-1822-4efd-ba2b-34f65c83c439',
            ]
        );

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('url is missing');

        $this->deserializer->deserialize(new StringLiteral($json));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_jwt_is_missing()
    {
        $json = json_encode(
            [
                'id' => '681dc7f1-86a1-43ec-8fa5-09ce28e5a05e',
                'url' => 'http://io.uitdatabank.be/events/681dc7f1-86a1-43ec-8fa5-09ce28e5a05e',
                'apiKey' => 'ea450545-1822-4efd-ba2b-34f65c83c439',
            ]
        );

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('jwt is missing');

        $this->deserializer->deserialize(new StringLiteral($json));
    }

    /**
     * @test
     */
    public function it_should_create_an_import_command()
    {
        $json = json_encode(
            [
                'id' => '681dc7f1-86a1-43ec-8fa5-09ce28e5a05e',
                'url' => 'http://io.uitdatabank.be/events/681dc7f1-86a1-43ec-8fa5-09ce28e5a05e',
                'jwt' => 'foo.bar.acme',
                'apiKey' => 'ea450545-1822-4efd-ba2b-34f65c83c439',
            ]
        );

        $expected = new ImportEventDocument(
            '681dc7f1-86a1-43ec-8fa5-09ce28e5a05e',
            'http://io.uitdatabank.be/events/681dc7f1-86a1-43ec-8fa5-09ce28e5a05e',
            'foo.bar.acme',
            'ea450545-1822-4efd-ba2b-34f65c83c439'
        );

        $actual = $this->deserializer->deserialize(new StringLiteral($json));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_create_an_import_command_without_api_key()
    {
        $json = json_encode(
            [
                'id' => '681dc7f1-86a1-43ec-8fa5-09ce28e5a05e',
                'url' => 'http://io.uitdatabank.be/events/681dc7f1-86a1-43ec-8fa5-09ce28e5a05e',
                'jwt' => 'foo.bar.acme',
            ]
        );

        $expected = new ImportEventDocument(
            '681dc7f1-86a1-43ec-8fa5-09ce28e5a05e',
            'http://io.uitdatabank.be/events/681dc7f1-86a1-43ec-8fa5-09ce28e5a05e',
            'foo.bar.acme'
        );

        $actual = $this->deserializer->deserialize(new StringLiteral($json));

        $this->assertEquals($expected, $actual);
    }
}
