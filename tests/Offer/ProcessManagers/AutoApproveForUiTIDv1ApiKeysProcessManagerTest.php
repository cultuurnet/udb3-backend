<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ProcessManagers;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\InMemoryConsumerRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerIsInPermissionGroup;
use CultuurNet\UDB3\ApiGuard\CultureFeed\CultureFeedConsumerAdapter;
use CultuurNet\UDB3\Offer\Offer;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\Events\Moderation\Published;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AutoApproveForUiTIDv1ApiKeysProcessManagerTest extends TestCase
{
    private const AUTO_APPROVE_GROUP_ID = '123';

    private MockObject $offerRepository;
    private InMemoryConsumerRepository $consumerRepository;
    private AutoApproveForUiTIDv1ApiKeysProcessManager $autoApproveForUiTIDv1ApiKeysProcessManager;

    protected function setUp(): void
    {
        $this->offerRepository = $this->createMock(OfferRepository::class);
        $this->consumerRepository = new InMemoryConsumerRepository();

        $this->autoApproveForUiTIDv1ApiKeysProcessManager = new AutoApproveForUiTIDv1ApiKeysProcessManager(
            $this->offerRepository,
            $this->consumerRepository,
            new ConsumerIsInPermissionGroup(self::AUTO_APPROVE_GROUP_ID)
        );
    }

    /**
     * @test
     */
    public function it_should_ignore_the_published_offer_if_there_is_no_api_key_in_the_metadata(): void
    {
        $id = 'df9dd502-b1bf-4d3d-aeab-f9155c98de42';
        $date = new DateTimeImmutable('2024-01-01T16:00:00+01:00');
        $domainMessage = DomainMessage::recordNow(1, 0, new Metadata([]), new Published($id, $date));

        $this->offerRepository->expects($this->never())
            ->method('load');

        $this->offerRepository->expects($this->never())
            ->method('save');

        $this->autoApproveForUiTIDv1ApiKeysProcessManager->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_should_ignore_the_published_offer_if_there_is_no_consumer_for_the_api_key(): void
    {
        $id = 'df9dd502-b1bf-4d3d-aeab-f9155c98de42';
        $date = new DateTimeImmutable('2024-01-01T16:00:00+01:00');
        $metadata = ['auth_api_key' => '8af5f13f-a80f-4642-bdb6-bdc323dcb7eb'];
        $domainMessage = DomainMessage::recordNow(1, 0, new Metadata($metadata), new Published($id, $date));

        $this->offerRepository->expects($this->never())
            ->method('load');

        $this->offerRepository->expects($this->never())
            ->method('save');

        $this->autoApproveForUiTIDv1ApiKeysProcessManager->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_should_ignore_the_published_offer_if_the_consumer_is_not_in_the_auto_approve_group(): void
    {
        $id = 'df9dd502-b1bf-4d3d-aeab-f9155c98de42';
        $date = new DateTimeImmutable('2024-01-01T16:00:00+01:00');
        $metadata = ['auth_api_key' => '8af5f13f-a80f-4642-bdb6-bdc323dcb7eb'];
        $domainMessage = DomainMessage::recordNow(1, 0, new Metadata($metadata), new Published($id, $date));

        $cfConsumer = new \CultureFeed_Consumer();
        $cfConsumer->apiKeySapi3 = '8af5f13f-a80f-4642-bdb6-bdc323dcb7eb';
        $cfConsumer->group = ['456'];
        $consumer = new CultureFeedConsumerAdapter($cfConsumer);

        $this->consumerRepository->setConsumer(new ApiKey('8af5f13f-a80f-4642-bdb6-bdc323dcb7eb'), $consumer);

        $this->offerRepository->expects($this->never())
            ->method('load');

        $this->offerRepository->expects($this->never())
            ->method('save');

        $this->autoApproveForUiTIDv1ApiKeysProcessManager->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_should_approve_the_published_offer_if_the_consumer_is_in_the_auto_approve_group(): void
    {
        $id = 'df9dd502-b1bf-4d3d-aeab-f9155c98de42';
        $date = new DateTimeImmutable('2024-01-01T16:00:00+01:00');
        $metadata = ['auth_api_key' => '8af5f13f-a80f-4642-bdb6-bdc323dcb7eb'];
        $domainMessage = DomainMessage::recordNow(1, 0, new Metadata($metadata), new Published($id, $date));

        $cfConsumer = new \CultureFeed_Consumer();
        $cfConsumer->apiKeySapi3 = '8af5f13f-a80f-4642-bdb6-bdc323dcb7eb';
        $cfConsumer->group = [self::AUTO_APPROVE_GROUP_ID, '456'];
        $consumer = new CultureFeedConsumerAdapter($cfConsumer);

        $this->consumerRepository->setConsumer(new ApiKey('8af5f13f-a80f-4642-bdb6-bdc323dcb7eb'), $consumer);

        $offer = $this->createMock(Offer::class);

        $this->offerRepository->expects($this->once())
            ->method('load')
            ->with($id)
            ->willReturn($offer);

        $offer->expects($this->once())
            ->method('approve');

        $this->offerRepository->expects($this->once())
            ->method('save')
            ->with($offer);

        $this->autoApproveForUiTIDv1ApiKeysProcessManager->handle($domainMessage);
    }
}
