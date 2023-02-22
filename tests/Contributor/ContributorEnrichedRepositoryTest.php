<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\StringLiteral;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ContributorEnrichedRepositoryTest extends TestCase
{
    /**
     * @var ContributorRepository|MockObject
     */
    private $contributorRepository;

    private InMemoryDocumentRepository $documentRepository;

    /**
     * @var PermissionVoter|MockObject;
     */
    private $permissionVoter;

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
     */
    public function it_add_contributors_if_user_has_permission(): void
    {
        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::aanbodBewerken(),
                new StringLiteral($this->offerId),
                new StringLiteral($this->currentUserId)
            )
            ->willReturn(true);

        $this->contributorRepository->expects($this->once())
            ->method('getContributors')
            ->with(new UUID($this->offerId))
            ->willReturn(
                EmailAddresses::fromArray([
                    new EmailAddress('info@example.com'),
                    new EmailAddress('contact@example.com'),
                ])
            );


        $jsonLd = new JsonDocument($this->offerId, json_encode(['@type' => 'Event']));
        $this->documentRepository->save($jsonLd);

        $fetchJsonLd = $this->contributorEnrichedRepository->fetch($this->offerId, false);

        $this->assertEquals(
            new JsonDocument(
                $this->offerId,
                json_encode([
                    '@type' => 'Event',
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
     */
    public function it_hides_contributors_if_user_has_no_permission(): void
    {
        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(
                Permission::aanbodBewerken(),
                new StringLiteral($this->offerId),
                new StringLiteral($this->currentUserId)
            )
            ->willReturn(false);

        $this->contributorRepository->expects($this->never())
            ->method('getContributors')
            ->with(new UUID($this->offerId));


        $jsonLd = new JsonDocument($this->offerId, json_encode(['@type' => 'Event']));
        $this->documentRepository->save($jsonLd);

        $fetchJsonLd = $this->contributorEnrichedRepository->fetch($this->offerId);

        $this->assertEquals(
            new JsonDocument(
                $this->offerId,
                json_encode([
                    '@type' => 'Event',
                ])
            ),
            $fetchJsonLd
        );
    }
}
