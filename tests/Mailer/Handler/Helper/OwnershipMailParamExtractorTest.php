<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Handler\Helper;

use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\User\UserIdentityDetails;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OwnershipMailParamExtractorTest extends TestCase
{
    private const ID = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
    private const OWNER_ID = 'd6e21fa4-8d8d-4f23-b0cc-c63e34e43a01';
    private const ORGANIZER_ID = 'd146a8cb-14c8-4364-9207-9d32d36f6959';
    private DocumentRepository&MockObject $organizerRepository;
    private IriGeneratorInterface $organizerIriGenerator;
    private OwnershipMailParamExtractor $extractor;

    protected function setUp(): void
    {
        $this->organizerRepository = $this->createMock(DocumentRepository::class);
        $this->organizerIriGenerator = new CallableIriGenerator(
            fn (string $id) => 'http://localhost/organizers/' . $id . '/preview'
        );

        $this->extractor = new OwnershipMailParamExtractor(
            $this->organizerRepository,
            $this->organizerIriGenerator
        );
    }

    /** @test */
    public function it_returns_ownernship_mail_params(): void
    {
        $this->organizerRepository
            ->expects($this->once())
            ->method('fetch')
            ->with(self::ORGANIZER_ID)
            ->willReturn(new JsonDocument(self::ORGANIZER_ID, json_encode([
                'name' => ['nl' => 'Organisatie NL', 'en' => 'Organization EN'],
                'mainLanguage' => 'en',
            ], JSON_THROW_ON_ERROR)));

        $result = $this->extractor->fetchParams(
            $this->givenAnOwnershipItem(self::ID, self::ORGANIZER_ID, self::OWNER_ID),
            new UserIdentityDetails(self::OWNER_ID, 'John Smith', 'info@publiq.be')
        );

        $this->assertSame([
            'organisationName' => 'Organization EN',
            'firstName' => 'John Smith',
            'organisationUrl' => 'http://localhost/organizers/' . self::ORGANIZER_ID . '/preview',
        ], $result);
    }

    /** @test */
    public function it_returns_ownernship_mail_params_with_fallback_main_language(): void
    {
        $this->organizerRepository
            ->expects($this->once())
            ->method('fetch')
            ->with(self::ORGANIZER_ID)
            ->willReturn(new JsonDocument(self::ORGANIZER_ID, json_encode([
                'name' => ['nl' => 'Organisatie NL'],
                'mainLanguage' => 'fr',
            ], JSON_THROW_ON_ERROR)));

        $result = $this->extractor->fetchParams(
            $this->givenAnOwnershipItem(self::ID, self::ORGANIZER_ID, self::OWNER_ID),
            new UserIdentityDetails(self::OWNER_ID, 'John Smith', 'info@publiq.be')
        );

        $this->assertSame([
            'organisationName' => 'Organisatie NL',
            'firstName' => 'John Smith',
            'organisationUrl' => 'http://localhost/organizers/' . self::ORGANIZER_ID . '/preview',
        ], $result);
    }

    /** @test */
    public function it_throw_exception_when_organizer_not_found(): void
    {
        $this->expectException(DocumentDoesNotExist::class);

        $this->organizerRepository
            ->expects($this->once())
            ->method('fetch')
            ->with(self::ORGANIZER_ID)
            ->willThrowException(new DocumentDoesNotExist());

        $this->extractor->fetchParams(
            $this->givenAnOwnershipItem(self::ID, self::ORGANIZER_ID, self::OWNER_ID),
            new UserIdentityDetails(self::OWNER_ID, 'John Smith', 'info@publiq.be')
        );
    }

    private function givenAnOwnershipItem(string $id, string $organizerId, string $ownerId): OwnershipItem
    {
        return new OwnershipItem(
            $id,
            $organizerId,
            'organizer',
            $ownerId,
            'requested'
        );
    }
}
