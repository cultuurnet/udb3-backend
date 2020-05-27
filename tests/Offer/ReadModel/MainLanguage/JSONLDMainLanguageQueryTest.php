<?php

namespace CultuurNet\UDB3\Offer\ReadModel\MainLanguage;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JSONLDMainLanguageQueryTest extends TestCase
{
    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    private $documentRepository;

    /**
     * @var JSONLDMainLanguageQuery
     */
    private $query;

    public function setUp()
    {
        $this->documentRepository = $this->createMock(DocumentRepositoryInterface::class);
        $this->query = new JSONLDMainLanguageQuery($this->documentRepository, new Language('nl'));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_no_document_can_be_found_for_the_given_cdbid()
    {
        $this->expectException(EntityNotFoundException::class);
        $this->query->execute('03f5dfde-de64-426e-9a0f-5a2f249b0be5');
    }

    /**
     * @test
     */
    public function it_should_return_the_mainLanguage_defined_on_the_document_for_the_given_cdbid()
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
    public function it_should_return_the_fallback_language_if_the_document_for_the_given_cdbid_has_no_mainLanguage()
    {
        $cdbid = '24bc748b-f138-4512-a377-2fef5a6cc42f';
        $this->expectDocumentWithJsonLd($cdbid, []);

        $expected = new Language('nl');
        $actual = $this->query->execute($cdbid);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @param string $cdbid
     * @param array $data
     */
    private function expectDocumentWithJsonLd($cdbid, array $data)
    {
        $document = new JsonDocument($cdbid, json_encode($data));

        $this->documentRepository->expects($this->any())
            ->method('get')
            ->with($cdbid)
            ->willReturn($document);
    }
}
