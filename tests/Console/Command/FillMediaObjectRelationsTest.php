<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\SimpleInterfaceSerializer;
use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Event\Events\ImageAdded as EventImageAdded;
use CultuurNet\UDB3\Event\Events\ImageRemoved as EventImageRemoved;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Media\ReadModel\Relations\DBALMediaObjectRelationsRepository;
use CultuurNet\UDB3\Media\ReadModel\Relations\MediaObjectRelation;
use CultuurNet\UDB3\Media\ReadModel\Relations\MediaObjectRelationsProjector;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Organizer\Events\ImageAdded as OrganizerImageAdded;
use CultuurNet\UDB3\Organizer\Events\WebsiteUpdated;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class FillMediaObjectRelationsTest extends TestCase
{
    use DBALTestConnectionTrait;

    private const IMAGE_ID = 'c263ce95-44b4-41b0-916f-ce72063b929b';
    private const OTHER_IMAGE_ID = 'e717473d-609d-4bf1-a008-c935c8836335';
    private const EVENT_ID = '1d4f5a1c-6f1a-4a7c-9a3e-2d1b0c3e8f01';
    private const ORGANIZER_ID = '3f6b7c3e-8b3c-4c9e-9c5a-4f3d2e5a0b03';

    private DBALMediaObjectRelationsRepository $repository;

    private QuestionHelper&MockObject $questionHelper;

    private FillMediaObjectRelations $command;

    private int $playhead = 0;

    protected function setUp(): void
    {
        $this->setUpDatabase();

        $this->repository = new DBALMediaObjectRelationsRepository($this->getConnection());

        $this->command = new FillMediaObjectRelations(
            $this->getConnection(),
            new SimpleInterfaceSerializer(),
            new MediaObjectRelationsProjector($this->repository)
        );

        $this->questionHelper = $this->createMock(QuestionHelper::class);
        $this->command->setHelperSet(new HelperSet(['question' => $this->questionHelper]));
    }

    /**
     * @test
     */
    public function it_fills_the_relations_from_the_image_events_in_order(): void
    {
        $this->storeEvent(self::EVENT_ID, new EventImageAdded(self::EVENT_ID, $this->image(self::IMAGE_ID)));
        $this->storeEvent(self::EVENT_ID, new EventImageAdded(self::EVENT_ID, $this->image(self::OTHER_IMAGE_ID)));
        $this->storeEvent(self::EVENT_ID, new EventImageRemoved(self::EVENT_ID, $this->image(self::OTHER_IMAGE_ID)));
        $this->storeEvent(
            self::ORGANIZER_ID,
            new OrganizerImageAdded(self::ORGANIZER_ID, self::IMAGE_ID, 'nl', 'Beschrijving', 'Rechtenhouder')
        );
        $this->storeEvent(self::ORGANIZER_ID, new WebsiteUpdated(self::ORGANIZER_ID, 'https://www.publiq.be'));

        $this->questionHelper->method('ask')->willReturn(true);

        $output = new BufferedOutput();
        $exitCode = $this->command->run(new ArrayInput([]), $output);

        $this->assertSame(0, $exitCode);
        $this->assertEquals(
            [
                new MediaObjectRelation(ItemType::event(), self::EVENT_ID),
                new MediaObjectRelation(ItemType::organizer(), self::ORGANIZER_ID),
            ],
            $this->repository->getRelations(self::IMAGE_ID)
        );
        $this->assertEquals([], $this->repository->getRelations(self::OTHER_IMAGE_ID));
    }

    /**
     * @test
     */
    public function it_does_nothing_when_not_confirmed(): void
    {
        $this->storeEvent(self::EVENT_ID, new EventImageAdded(self::EVENT_ID, $this->image(self::IMAGE_ID)));

        $this->questionHelper->method('ask')->willReturn(false);

        $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals([], $this->repository->getRelations(self::IMAGE_ID));
    }

    private function storeEvent(string $aggregateId, object $event): void
    {
        $domainMessage = new DomainMessage($aggregateId, $this->playhead++, new Metadata(), $event, DateTime::now());

        $this->getConnection()->insert('event_store', [
            'uuid' => $aggregateId,
            'playhead' => $domainMessage->getPlayhead(),
            'metadata' => Json::encode((new SimpleInterfaceSerializer())->serialize(new Metadata())),
            'payload' => Json::encode((new SimpleInterfaceSerializer())->serialize($event)),
            'recorded_on' => $domainMessage->getRecordedOn()->toString(),
            'type' => $domainMessage->getType(),
            'aggregate_type' => 'test',
        ]);
    }

    private function image(string $id): Image
    {
        return new Image(
            new Uuid($id),
            new MIMEType('image/png'),
            new Description('Beschrijving'),
            new CopyrightHolder('Rechtenhouder'),
            new Url('https://images.uitdatabank.local/' . $id . '.png'),
            new Language('nl')
        );
    }
}
