<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import;

use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class DecodedDocumentTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_have_an_id_and_an_array_as_a_body()
    {
        $id = '7b53e1cf-4407-4681-b059-ceffaaef2bf3';
        $body = [
            '@id' => 'http://io.uitdatabank.be/event/7b53e1cf-4407-4681-b059-ceffaaef2bf3',
            '@type' => 'Event',
        ];

        $document = new DecodedDocument($id, $body);

        $this->assertEquals($id, $document->getId());
        $this->assertEquals($body, $document->getBody());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_body()
    {
        $id = '7b53e1cf-4407-4681-b059-ceffaaef2bf3';
        $body = [
            '@id' => 'http://io.uitdatabank.be/event/7b53e1cf-4407-4681-b059-ceffaaef2bf3',
            '@type' => 'Event',
        ];

        $document = new DecodedDocument($id, $body);

        $updatedBody = [
            '@id' => 'http://io.uitdatabank.be/event/7b53e1cf-4407-4681-b059-ceffaaef2bf3',
            '@type' => 'Event',
            'name' => [
                'nl' => 'Voorbeeld titel',
            ],
        ];

        $updatedDocument = $document->withBody($updatedBody);

        $this->assertNotEquals($document, $updatedDocument);
        $this->assertEquals($body, $document->getBody());
        $this->assertEquals($updatedBody, $updatedDocument->getBody());
    }

    /**
     * @test
     */
    public function it_should_be_convertible_to_json_or_a_json_document()
    {
        $id = '7b53e1cf-4407-4681-b059-ceffaaef2bf3';
        $body = [
            '@id' => 'http://io.uitdatabank.be/event/7b53e1cf-4407-4681-b059-ceffaaef2bf3',
            '@type' => 'Event',
        ];

        $expectedJson = '{"@id":"http://io.uitdatabank.be/event/7b53e1cf-4407-4681-b059-ceffaaef2bf3","@type":"Event"}';
        $expectedJsonDocument = new JsonDocument($id, $expectedJson);

        $document = new DecodedDocument($id, $body);

        $json = $document->toJson();
        $jsonDocument = $document->toJsonDocument();

        $this->assertEquals($expectedJson, $json);
        $this->assertEquals($expectedJsonDocument, $jsonDocument);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_json_or_a_json_document()
    {
        $id = '7b53e1cf-4407-4681-b059-ceffaaef2bf3';
        $json = '{"@id":"http://io.uitdatabank.be/event/7b53e1cf-4407-4681-b059-ceffaaef2bf3","@type":"Event"}';
        $jsonDocument = new JsonDocument($id, $json);

        $expectedBody = [
            '@id' => 'http://io.uitdatabank.be/event/7b53e1cf-4407-4681-b059-ceffaaef2bf3',
            '@type' => 'Event',
        ];
        $expectedDocument = new DecodedDocument($id, $expectedBody);

        $fromJson = DecodedDocument::fromJson($id, $json);
        $fromJsonDocument = DecodedDocument::fromJsonDocument($jsonDocument);

        $this->assertEquals($expectedDocument, $fromJson);
        $this->assertEquals($expectedDocument, $fromJsonDocument);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_creating_from_invalid_json()
    {
        $id = '7b53e1cf-4407-4681-b059-ceffaaef2bf3';
        $json = '{';

        $this->expectException(\InvalidArgumentException::class);

        DecodedDocument::fromJson($id, $json);
    }
}
