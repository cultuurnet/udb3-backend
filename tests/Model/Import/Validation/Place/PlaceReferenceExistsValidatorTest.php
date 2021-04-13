<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Validation\Place;

use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\GroupedValidationException;

class PlaceReferenceExistsValidatorTest extends TestCase
{
    /**
     * @var DocumentRepository|MockObject
     */
    private $repository;

    /**
     * @var PlaceReferenceExistsValidator
     */
    private $validator;

    public function setUp()
    {
        $this->repository = $this->createMock(DocumentRepository::class);

        $this->validator = new PlaceReferenceExistsValidator(
            new PlaceIDParser(),
            $this->repository
        );
    }

    /**
     * @test
     */
    public function it_should_pass_if_no_id_is_present()
    {
        // This is handled by another validator.
        $location = [];
        $this->assertTrue($this->validator->validate($location));
    }

    /**
     * @test
     */
    public function it_should_pass_if_the_id_is_invalid()
    {
        // This is handled by another validator.
        $location = ['@id' => 'foobar'];
        $this->assertTrue($this->validator->validate($location));
    }

    /**
     * @test
     */
    public function it_should_pass_if_the_a_place_document_exists_for_the_given_id()
    {
        $location = ['@id' => 'https://io.uitdatabank.be/places/b458d34c-af5c-462f-a004-85516c1b1e0a'];

        // Document contents of the place are irrelevant for this validator.
        $jsonDocument = new JsonDocument('b458d34c-af5c-462f-a004-85516c1b1e0a', '{}');

        $this->repository->expects($this->once())
            ->method('fetch')
            ->with('b458d34c-af5c-462f-a004-85516c1b1e0a')
            ->willReturn($jsonDocument);

        $this->assertTrue($this->validator->validate($location));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_no_place_document_exists_for_the_given_id()
    {
        $id = 'b458d34c-af5c-462f-a004-85516c1b1e0a';
        $location = ['@id' => 'https://io.uitdatabank.be/places/' . $id];

        $this->repository->expects($this->once())
            ->method('fetch')
            ->with($id)
            ->willThrowException(DocumentDoesNotExist::notFound($id));

        $expected = [
            'Location with id https://io.uitdatabank.be/places/b458d34c-af5c-462f-a004-85516c1b1e0a does not exist.',
        ];

        try {
            $this->validator->assert($location);
            $errors = [];
        } catch (GroupedValidationException $e) {
            $errors = $e->getMessages();
        }

        $this->assertEquals($expected, $errors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_the_place_document_for_the_given_id_was_deleted()
    {
        $id = 'b458d34c-af5c-462f-a004-85516c1b1e0a';
        $location = ['@id' => 'https://io.uitdatabank.be/places/' . $id];

        $this->repository->expects($this->once())
            ->method('fetch')
            ->with($id)
            ->willThrowException(DocumentDoesNotExist::gone($id));

        $expected = [
            'Location with id https://io.uitdatabank.be/places/b458d34c-af5c-462f-a004-85516c1b1e0a does not exist.',
        ];

        try {
            $this->validator->assert($location);
            $errors = [];
        } catch (GroupedValidationException $e) {
            $errors = $e->getMessages();
        }

        $this->assertEquals($expected, $errors);
    }
}
