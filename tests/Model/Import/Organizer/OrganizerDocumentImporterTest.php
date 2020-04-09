<?php

namespace CultuurNet\UDB3\Model\Import\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\Model\Import\Taxonomy\Label\LockedLabelRepository;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Organizer\Commands\ImportLabels;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Organizer\Commands\RemoveAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Commands\UpdateTitle;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use CultuurNet\UDB3\Organizer\Organizer;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use CultuurNet\UDB3\Model\Serializer\Organizer\OrganizerDenormalizer;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;
use ValueObjects\Web\Url;

class OrganizerDocumentImporterTest extends TestCase
{
    /**
     * @var RepositoryInterface|MockObject
     */
    private $repository;

    /**
     * @var OrganizerDenormalizer
     */
    private $denormalizer;

    /**
     * @var TraceableCommandBus
     */
    private $commandBus;

    /**
     * @var LockedLabelRepository|MockObject
     */
    private $lockedLabelRepository;

    /**
     * @var OrganizerDocumentImporter
     */
    private $organizerDocumentImporter;

    /**
     * @var DocumentImporterInterface
     */
    private $importer;

    public function setUp()
    {
        $this->repository = $this->createMock(RepositoryInterface::class);
        $this->denormalizer = new OrganizerDenormalizer();
        $this->commandBus = new TraceableCommandBus();
        $this->lockedLabelRepository = $this->createMock(LockedLabelRepository::class);

        $this->organizerDocumentImporter = new OrganizerDocumentImporter(
            $this->repository,
            $this->denormalizer,
            $this->commandBus,
            $this->lockedLabelRepository
        );

        $this->importer = $this->organizerDocumentImporter;
    }

    /**
     * @test
     */
    public function it_should_create_a_new_organizer_and_publish_it_if_no_aggregate_exists_for_the_given_id()
    {
        $document = $this->getOrganizerDocument();
        $id = $document->getId();

        $this->expectOrganizerDoesNotExist($id);
        $this->expectCreateOrganizer(
            Organizer::create(
                $id,
                new Language('nl'),
                Url::fromNative('https://www.publiq.be'),
                new Title('Voorbeeld naam')
            )
        );
        $this->expectNoLockedLabels();

        $this->commandBus->record();

        $this->importer->import($document);

        $expectedCommands = [
            new UpdateContactPoint($id, new ContactPoint()),
            new RemoveAddress($id),
            new UpdateTitle($id, new Title('Nom example'), new Language('fr')),
            new UpdateTitle($id, new Title('Example name'), new Language('en')),
            new ImportLabels($id, new Labels()),
        ];

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals($expectedCommands, $recordedCommands);
    }

    /**
     * @test
     */
    public function it_should_update_an_existing_organizer_if_an_aggregate_exists_for_the_given_id()
    {
        $document = $this->getOrganizerDocument();
        $id = $document->getId();

        $this->expectOrganizerExists($id);
        $this->expectNoLockedLabels();

        $this->commandBus->record();

        $this->importer->import($document);

        $expectedCommands = [
            new UpdateTitle($id, new Title('Voorbeeld naam'), new Language('nl')),
            new UpdateWebsite($id, Url::fromNative('https://www.publiq.be')),
            new UpdateContactPoint($id, new ContactPoint()),
            new RemoveAddress($id),
            new UpdateTitle($id, new Title('Nom example'), new Language('fr')),
            new UpdateTitle($id, new Title('Example name'), new Language('en')),
            new ImportLabels($id, new Labels()),
        ];

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals($expectedCommands, $recordedCommands);
    }

    /**
     * @test
     */
    public function it_should_update_an_existing_organizer_with_labels()
    {
        $document = $this->getOrganizerDocumentWithLabels();
        $id = $document->getId();

        $this->expectOrganizerExists($id);

        $lockedLabels = new Labels(
            new Label(new LabelName('locked1')),
            new Label(new LabelName('locked2'))
        );
        $this->lockedLabelRepository->expects($this->once())
            ->method('getLockedLabelsForItem')
            ->with($id)
            ->willReturn($lockedLabels);

        $this->commandBus->record();

        $this->importer->import($document);

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertContainsObject(
            (
            new ImportLabels(
                $this->getOrganizerId(),
                new Labels(
                    new Label(new LabelName('foo'), true),
                    new Label(new LabelName('bar'), true),
                    new Label(new LabelName('lorem'), false),
                    new Label(new LabelName('ipsum'), false)
                )
            )
            )->withLabelsToKeepIfAlreadyOnOrganizer($lockedLabels),
            $recordedCommands
        );
    }

    /**
     * @test
     */
    public function it_should_update_the_address()
    {
        $document = $this->getOrganizerDocument();
        $body = $document->getBody();
        $body['address'] = [
            'nl' => [
                'streetAddress' => 'Henegouwenkaai 41-43',
                'postalCode' => '1080',
                'addressLocality' => 'Brussel',
                'addressCountry' => 'BE',
            ],
            'fr' => [
                'streetAddress' => 'Quai du Hainaut 41-43',
                'postalCode' => '1080',
                'addressLocality' => 'Bruxelles',
                'addressCountry' => 'BE',
            ],
        ];
        $document = $document->withBody($body);
        $id = $document->getId();

        $this->expectOrganizerExists($id);
        $this->expectNoLockedLabels();

        $this->commandBus->record();

        $this->importer->import($document);

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertContainsObject(
            new UpdateAddress(
                $id,
                new Address(
                    new Street('Henegouwenkaai 41-43'),
                    new PostalCode('1080'),
                    new Locality('Brussel'),
                    Country::fromNative('BE')
                ),
                new Language('nl')
            ),
            $recordedCommands
        );
        $this->assertContainsObject(
            new UpdateAddress(
                $id,
                new Address(
                    new Street('Quai du Hainaut 41-43'),
                    new PostalCode('1080'),
                    new Locality('Bruxelles'),
                    Country::fromNative('BE')
                ),
                new Language('fr')
            ),
            $recordedCommands
        );
    }

    /**
     * @test
     */
    public function it_should_remove_address()
    {
        $document = $this->getOrganizerDocument();
        $body = $document->getBody();

        $document = $document->withBody($body);
        $id = $document->getId();

        $this->expectOrganizerExists($id);
        $this->expectNoLockedLabels();

        $this->commandBus->record();

        $this->importer->import($document);

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertContainsObject(
            new RemoveAddress(
                $id
            ),
            $recordedCommands
        );
    }

    /**
     * @return string
     */
    private function getOrganizerId()
    {
        return 'f3277646-1cc8-4af9-b6d5-a47f3c4f2ac0';
    }

    /**
     * @return array
     */
    private function getOrganizerData()
    {
        return [
            '@id' => 'https://io.uitdatabank.be/organizers/f3277646-1cc8-4af9-b6d5-a47f3c4f2ac0',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Voorbeeld naam',
                'fr' => 'Nom example',
                'en' => 'Example name',
            ],
            'url' => 'https://www.publiq.be',
        ];
    }

    /**
     * @return array
     */
    private function getOrganizerDataWithLabels()
    {
        return $this->getOrganizerData() +
            [
                'labels' => [
                    'foo',
                    'bar',
                ]
            ]
            +
            [
                'hiddenLabels' => [
                    'lorem',
                    'ipsum',
                ]
            ];
    }

    /**
     * @return DecodedDocument
     */
    private function getOrganizerDocument()
    {
        return new DecodedDocument($this->getOrganizerId(), $this->getOrganizerData());
    }

    /**
     * @return DecodedDocument
     */
    private function getOrganizerDocumentWithLabels()
    {
        return new DecodedDocument($this->getOrganizerId(), $this->getOrganizerDataWithLabels());
    }

    /**
     * @param string $organizerId
     */
    private function expectOrganizerExists($organizerId)
    {
        $this->repository->expects($this->once())
            ->method('load')
            ->with($organizerId)
            ->willReturn($this->createMock(Organizer::class));
    }

    private function expectOrganizerDoesNotExist($organizerId)
    {
        $this->repository->expects($this->once())
            ->method('load')
            ->with($organizerId)
            ->willThrowException(new AggregateNotFoundException());
    }

    private function expectCreateOrganizer(Organizer $expectedOrganizer)
    {
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Organizer $organizer) use ($expectedOrganizer) {
                return $expectedOrganizer->getAggregateRootId() === $organizer->getAggregateRootId();
            }));
    }

    private function expectNoLockedLabels()
    {
        $this->lockedLabelRepository->expects($this->any())
            ->method('getLockedLabelsForItem')
            ->willReturn(new Labels());
    }

    private function assertContainsObject($needle, array $haystack)
    {
        $this->assertContains(
            $needle,
            $haystack,
            '',
            false,
            false
        );
    }
}
