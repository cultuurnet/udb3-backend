<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\MainLanguage;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JSONLDMainLanguageQueryTest extends TestCase
{
    /**
     * @var DocumentRepository&MockObject
     */
    private $documentRepository;

    private JSONLDMainLanguageQuery $query;

    public function setUp(): void
    {
        $this->documentRepository = $this->createMock(DocumentRepository::class);
        $this->query = new JSONLDMainLanguageQuery($this->documentRepository, new Language('nl'));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_no_document_can_be_found_for_the_given_cdbid(): void
    {
        $id = '03f5dfde-de64-426e-9a0f-5a2f249b0be5';
        $this->documentRepository
            ->expects($this->once())
            ->method('fetch')
            ->willThrowException(DocumentDoesNotExist::withId($id));

        $this->expectException(EntityNotFoundException::class);
        $this->query->execute($id);
    }

    /**
     * @test
     */
    public function it_should_return_the_mainLanguage_defined_on_the_document_for_the_given_cdbid(): void
    {
        $cdbid = '24bc748b-f138-4512-a377-2fef5a6cc42f';
        $this->expectDocumentWithJsonLd($cdbid, ['mainLanguage' => 'en']);

        $expected = new Language('en');
        $actual = $this->query->execute($cdbid);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_the_fallback_language_if_the_document_for_the_given_cdbid_has_no_mainLanguage(): void
    {
        $cdbid = '24bc748b-f138-4512-a377-2fef5a6cc42f';
        $this->expectDocumentWithJsonLd($cdbid, []);

        $expected = new Language('nl');
        $actual = $this->query->execute($cdbid);

        $this->assertEquals($expected, $actual);
    }

    private function expectDocumentWithJsonLd(string $cdbid, array $data): void
    {
        $document = new JsonDocument($cdbid, Json::encode($data));

        $this->documentRepository->expects($this->any())
            ->method('fetch')
            ->with($cdbid)
            ->willReturn($document);
    }
}
