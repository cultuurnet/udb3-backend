<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class DeleteUiTPASPlaceVoterTest extends TestCase
{
    /** @var DocumentRepository&MockObject */
    private $documentRepository;
    private array $uitpasLabels;
    private string $userId;

    protected function setUp(): void
    {
        $this->documentRepository = $this->createMock(DocumentRepository::class);
        $this->uitpasLabels = ['UiTPAS', 'Paspartoe'];
        $this->userId = Uuid::uuid4()->toString();
    }

    public function test_deleting_a_place_without_UiTPAS_label_should_be_allowed(): void
    {
        $offerId = 'test-offer-id';
        $this->documentRepository
            ->method('fetch')
            ->with($offerId)
            ->willReturn(new JsonDocument($offerId, json_encode([
                'hiddenLabels' => ['not-an-UiTPAS-label'],
            ], JSON_THROW_ON_ERROR)));

        $validator = new DeleteUiTPASPlaceVoter($this->documentRepository, $this->uitpasLabels);

        $result = $validator->isAllowed(Permission::aanbodVerwijderen(), $offerId, $this->userId);

        $this->assertTrue($result);
    }

    public function test_deleting_a_place_with_an_UiTPAS_label_is_not_allowed(): void
    {
        $offerId = 'test-offer-id';

        $this->documentRepository
            ->method('fetch')
            ->with($offerId)
            ->willReturn(new JsonDocument($offerId, json_encode([
                'hiddenLabels' => ['UiTPAS'],
            ], JSON_THROW_ON_ERROR)));

        $validator = new DeleteUiTPASPlaceVoter($this->documentRepository, $this->uitpasLabels);

        $result = $validator->isAllowed(Permission::aanbodVerwijderen(), $offerId, $this->userId);

        $this->assertFalse($result);
    }

    public function test_should_be_valid_when_place_does_not_exist(): void
    {
        $offerId = 'non-existent-offer-id';

        $this->documentRepository
            ->method('fetch')
            ->with($offerId)
            ->willThrowException(new DocumentDoesNotExist());

        $validator = new DeleteUiTPASPlaceVoter($this->documentRepository, $this->uitpasLabels);

        $result = $validator->isAllowed(Permission::aanbodVerwijderen(), $offerId, $this->userId);

        $this->assertTrue($result);
    }
}
