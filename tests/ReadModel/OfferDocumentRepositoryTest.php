<?php declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OfferDocumentRepositoryTest extends TestCase
{
    /**
     * @var DocumentRepository|MockObject
     */
    private $eventDocumentRepository;

    /**
     * @var DocumentRepository|MockObject
     */
    private $placeDocumentRepository;

    /**
     * @var OfferDocumentRepository
     */
    private $offerDocumentRepository;

    protected function setUp(): void
    {
        $this->eventDocumentRepository = $this->createMock(DocumentRepository::class);
        $this->placeDocumentRepository = $this->createMock(DocumentRepository::class);

        $this->offerDocumentRepository = new OfferDocumentRepository(
            $this->eventDocumentRepository,
            $this->placeDocumentRepository
        );
    }

    /**
     * @test
     */
    public function it_fetches_from_event_repository(): void
    {
        $jsonDocument = new JsonDocument('43a79f1c-a720-4fd2-b762-6b46b7f16170');

        $this->eventDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with('43a79f1c-a720-4fd2-b762-6b46b7f16170', true)
            ->willReturn($jsonDocument);

        $actualDocument = $this->offerDocumentRepository->fetch('43a79f1c-a720-4fd2-b762-6b46b7f16170', true);

        $this->assertEquals($jsonDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_fetches_from_place_repository_when_not_inside_event_repository(): void
    {
        $jsonDocument = new JsonDocument('43a79f1c-a720-4fd2-b762-6b46b7f16170');

        $this->eventDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with('43a79f1c-a720-4fd2-b762-6b46b7f16170', true)
            ->willThrowException(DocumentDoesNotExist::notFound('43a79f1c-a720-4fd2-b762-6b46b7f16170'));

        $this->placeDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with('43a79f1c-a720-4fd2-b762-6b46b7f16170', true)
            ->willReturn($jsonDocument);

        $actualDocument = $this->offerDocumentRepository->fetch('43a79f1c-a720-4fd2-b762-6b46b7f16170', true);

        $this->assertEquals($jsonDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_throws_when_fetching_when_gone_from_event_repository(): void
    {
        $this->eventDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with('43a79f1c-a720-4fd2-b762-6b46b7f16170', true)
            ->willThrowException(DocumentDoesNotExist::gone('43a79f1c-a720-4fd2-b762-6b46b7f16170'));

        $this->expectException(DocumentDoesNotExist::class);

        $this->offerDocumentRepository->fetch('43a79f1c-a720-4fd2-b762-6b46b7f16170', true);
    }

    /**
     * @test
     */
    public function it_throws_when_fetching_and_not_inside_event_repository_and_not_inside_place_repository(): void
    {
        $this->eventDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with('43a79f1c-a720-4fd2-b762-6b46b7f16170', true)
            ->willThrowException(DocumentDoesNotExist::notFound('43a79f1c-a720-4fd2-b762-6b46b7f16170'));

        $this->placeDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with('43a79f1c-a720-4fd2-b762-6b46b7f16170', true)
            ->willThrowException(DocumentDoesNotExist::notFound('43a79f1c-a720-4fd2-b762-6b46b7f16170'));

        $this->expectException(DocumentDoesNotExist::class);

        $this->offerDocumentRepository->fetch('43a79f1c-a720-4fd2-b762-6b46b7f16170', true);
    }

    /**
     * @test
     */
    public function it_does_not_support_the_get_method(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->offerDocumentRepository->get('43a79f1c-a720-4fd2-b762-6b46b7f16170', true);
    }

    /**
     * @test
     */
    public function it_saves_document_with_event_context_to_event_repository(): void
    {
        $jsonDocument = new JsonDocument('43a79f1c-a720-4fd2-b762-6b46b7f16170');
        $jsonDocument = $jsonDocument->withAssocBody([
            '@context' => Context::EVENT,
        ]);

        $this->eventDocumentRepository->expects($this->once())
            ->method('save')
            ->with($jsonDocument);

        $this->offerDocumentRepository->save($jsonDocument);
    }

    /**
     * @test
     */
    public function it_saves_document_with_place_context_to_place_repository(): void
    {
        $jsonDocument = new JsonDocument('43a79f1c-a720-4fd2-b762-6b46b7f16170');
        $jsonDocument = $jsonDocument->withAssocBody([
            '@context' => Context::PLACE,
        ]);

        $this->placeDocumentRepository->expects($this->once())
            ->method('save')
            ->with($jsonDocument);

        $this->offerDocumentRepository->save($jsonDocument);
    }

    /**
     * @test
     */
    public function it_throws_when_saving_with_no_context(): void
    {
        $jsonDocument = new JsonDocument('43a79f1c-a720-4fd2-b762-6b46b7f16170');
        $jsonDocument = $jsonDocument->withAssocBody([
            'no_context' => 'Missing context',
        ]);

        $this->expectException(\RuntimeException::class);

        $this->offerDocumentRepository->save($jsonDocument);
    }

    /**
     * @test
     */
    public function it_throws_when_saving_with_no_event_or_place_context(): void
    {
        $jsonDocument = new JsonDocument('43a79f1c-a720-4fd2-b762-6b46b7f16170');
        $jsonDocument = $jsonDocument->withAssocBody([
            '@context' => 'Invalid context',
        ]);

        $this->expectException(\RuntimeException::class);

        $this->offerDocumentRepository->save($jsonDocument);
    }

    /**
     * @test
     */
    public function it_removes_from_event_repository(): void
    {
        $jsonDocument = new JsonDocument('43a79f1c-a720-4fd2-b762-6b46b7f16170');

        $this->eventDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with('43a79f1c-a720-4fd2-b762-6b46b7f16170', false)
            ->willReturn($jsonDocument);

        $this->eventDocumentRepository->expects($this->once())
            ->method('remove')
            ->with('43a79f1c-a720-4fd2-b762-6b46b7f16170');

        $this->offerDocumentRepository->remove('43a79f1c-a720-4fd2-b762-6b46b7f16170');
    }

    /**
     * @test
     */
    public function it_removes_from_place_repository_when_not_inside_event_repository(): void
    {
        $jsonDocument = new JsonDocument('43a79f1c-a720-4fd2-b762-6b46b7f16170');

        $this->eventDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with('43a79f1c-a720-4fd2-b762-6b46b7f16170', false)
            ->willThrowException(DocumentDoesNotExist::notFound('43a79f1c-a720-4fd2-b762-6b46b7f16170'));

        $this->placeDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with('43a79f1c-a720-4fd2-b762-6b46b7f16170', false)
            ->willReturn($jsonDocument);

        $this->placeDocumentRepository->expects($this->once())
            ->method('remove')
            ->with('43a79f1c-a720-4fd2-b762-6b46b7f16170');

        $this->offerDocumentRepository->remove('43a79f1c-a720-4fd2-b762-6b46b7f16170');
    }

    /**
     * @test
     */
    public function it_throws_when_removing_and_gone_from_event_repository(): void
    {
        $this->eventDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with('43a79f1c-a720-4fd2-b762-6b46b7f16170', false)
            ->willThrowException(DocumentDoesNotExist::gone('43a79f1c-a720-4fd2-b762-6b46b7f16170'));

        $this->expectException(DocumentDoesNotExist::class);

        $this->offerDocumentRepository->remove('43a79f1c-a720-4fd2-b762-6b46b7f16170');
    }

    /**
     * @test
     */
    public function it_throws_when_removing_and_not_inside_event_repository_or_place_repository(): void
    {
        $this->eventDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with('43a79f1c-a720-4fd2-b762-6b46b7f16170', false)
            ->willThrowException(DocumentDoesNotExist::notFound('43a79f1c-a720-4fd2-b762-6b46b7f16170'));

        $this->placeDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with('43a79f1c-a720-4fd2-b762-6b46b7f16170', false)
            ->willThrowException(DocumentDoesNotExist::notFound('43a79f1c-a720-4fd2-b762-6b46b7f16170'));

        $this->expectException(DocumentDoesNotExist::class);

        $this->offerDocumentRepository->remove('43a79f1c-a720-4fd2-b762-6b46b7f16170');
    }
}
