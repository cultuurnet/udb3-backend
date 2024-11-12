<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Validator;

use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PreventDeleteUitpasPlaceTest extends TestCase
{
    /** @var DocumentRepository&MockObject */
    private $documentRepository;
    private array $uitpasLabels;

    protected function setUp(): void
    {
        $this->documentRepository = $this->createMock(DocumentRepository::class);
        $this->uitpasLabels = ['UiTPAS', 'Paspartoe'];
    }

    public function test_is_valid_success(): void
    {
        $offerId = 'test-offer-id';
        $this->documentRepository
            ->method('fetch')
            ->with($offerId)
            ->willReturn(new JsonDocument($offerId, json_encode([
                'hiddenLabels' => ['not-an-UiTPAS-label'],
            ], JSON_THROW_ON_ERROR)));

        $validator = new PreventDeleteUitpasPlace($this->documentRepository, $this->uitpasLabels);

        $result = $validator->isValid($offerId);

        $this->assertTrue($result);
    }

    public function test_is_valid_failure(): void
    {
        $offerId = 'test-offer-id';

        $this->documentRepository
            ->method('fetch')
            ->with($offerId)
            ->willReturn(new JsonDocument($offerId, json_encode([
                'hiddenLabels' => ['UiTPAS'],
            ], JSON_THROW_ON_ERROR)));

        $validator = new PreventDeleteUitpasPlace($this->documentRepository, $this->uitpasLabels);

        $result = $validator->isValid($offerId);

        $this->assertFalse($result);
    }

    public function test_is_valid_document_does_not_exist(): void
    {
        $offerId = 'non-existent-offer-id';

        $this->documentRepository
            ->method('fetch')
            ->with($offerId)
            ->willThrowException(new DocumentDoesNotExist());

        $validator = new PreventDeleteUitpasPlace($this->documentRepository, $this->uitpasLabels);

        $result = $validator->isValid($offerId);

        $this->assertTrue($result);
    }
}
