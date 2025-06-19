<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ContributorEnrichedRepositoryTest extends TestCase
{
    private ContributorRepository&MockObject $contributorRepository;

    private InMemoryDocumentRepository $documentRepository;

    /**
     * @var PermissionVoter&MockObject;
     */
    private PermissionVoter&MockObject $permissionVoter;

    private ?string $currentUserId;

    private ContributorEnrichedRepository $contributorEnrichedRepository;

    private string $offerId;

    protected function setUp(): void
    {
        $this->contributorRepository = $this->createMock(ContributorRepository::class);

        $this->documentRepository = new InMemoryDocumentRepository();

        $this->permissionVoter = $this->createMock(PermissionVoter::class);

        $this->currentUserId = '123';

        $this->contributorEnrichedRepository = new ContributorEnrichedRepository(
            $this->contributorRepository,
            $this->documentRepository,
            $this->permissionVoter,
            $this->currentUserId
        );

        $this->offerId = '4ff559bd-9543-4ae2-900f-fe6d32fd019b';
    }

    /**
     * @test
     * @dataProvider itemTypeDataProvider
     */
    public function it_add_contributors_if_user_has_permission(string $itemType): void
    {
        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::aanbodBewerken(),
                $this->offerId,
                $this->currentUserId
            )
            ->willReturn(true);

        $this->contributorRepository->expects($this->once())
            ->method('getContributors')
            ->with(new Uuid($this->offerId))
            ->willReturn(
                EmailAddresses::fromArray([
                    new EmailAddress('info@example.com'),
                    new EmailAddress('contact@example.com'),
                ])
            );


        $jsonLd = new JsonDocument($this->offerId, Json::encode(['@type' => $itemType]));
        $this->documentRepository->save($jsonLd);

        $fetchJsonLd = $this->contributorEnrichedRepository->fetch($this->offerId, false);

        $this->assertEquals(
            new JsonDocument(
                $this->offerId,
                Json::encode([
                    '@type' => $itemType,
                    'contributors' => [
                        'info@example.com',
                        'contact@example.com',
                    ],
                ])
            ),
            $fetchJsonLd
        );
    }

    /**
     * @test
     * @dataProvider itemTypeDataProvider
     */
    public function it_does_not_add_contributors_if_there_are_none(string $itemType): void
    {
        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::aanbodBewerken(),
                $this->offerId,
                $this->currentUserId
            )
            ->willReturn(true);

        $this->contributorRepository->expects($this->once())
            ->method('getContributors')
            ->with(new Uuid($this->offerId))
            ->willReturn(
                EmailAddresses::fromArray([])
            );


        $jsonLd = new JsonDocument($this->offerId, Json::encode(['@type' => $itemType]));
        $this->documentRepository->save($jsonLd);

        $fetchJsonLd = $this->contributorEnrichedRepository->fetch($this->offerId, false);

        $this->assertEquals(
            new JsonDocument(
                $this->offerId,
                Json::encode([
                    '@type' => $itemType,
                ])
            ),
            $fetchJsonLd
        );
    }

    /**
     * @test
     * @dataProvider itemTypeDataProvider
     */
    public function it_hides_contributors_if_user_has_no_permission(string $itemType): void
    {
        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::aanbodBewerken(),
                $this->offerId,
                $this->currentUserId
            )
            ->willReturn(false);

        $this->contributorRepository->expects($this->never())
            ->method('getContributors')
            ->with(new Uuid($this->offerId));


        $jsonLd = new JsonDocument($this->offerId, Json::encode(['@type' => $itemType]));
        $this->documentRepository->save($jsonLd);

        $fetchJsonLd = $this->contributorEnrichedRepository->fetch($this->offerId);

        $this->assertEquals(
            new JsonDocument(
                $this->offerId,
                Json::encode([
                    '@type' => $itemType,
                ])
            ),
            $fetchJsonLd
        );
    }

    /**
     * @test
     * @dataProvider itemTypeDataProvider
     */
    public function it_does_not_save_contributors(string $itemType): void
    {
        $this->contributorEnrichedRepository->save(
            new JsonDocument(
                $this->offerId,
                Json::encode([
                    '@type' => $itemType,
                    'contributors' => [
                        'info@example.com',
                        'contact@example.com',
                    ],
                ])
            )
        );

        $this->assertEquals(
            new JsonDocument(
                $this->offerId,
                Json::encode(['@type' => $itemType])
            ),
            $this->contributorEnrichedRepository->fetch($this->offerId)
        );
    }

    public function itemTypeDataProvider(): array
    {
        return [
            ['Event'],
            ['Place'],
            ['Organizer'],
        ];
    }
}
