<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Validation\Event;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelRelationsRepository;
use CultuurNet\UDB3\Model\Validation\Event\EventValidator;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\AllOfException;

class EventImportValidatorTest extends TestCase
{
    /**
     * @var InMemoryDocumentRepository
     */
    private $placeRepository;

    /**
     * @var UUIDParser|MockObject
     */
    private $uuidParser;

    /**
     * @var LabelsRepository|MockObject
     */
    private $labelsRepository;

    /**
     * @var LabelRelationsRepository|MockObject
     */
    private $labelRelationsRepository;

    protected function setUp()
    {
        $this->placeRepository = new InMemoryDocumentRepository();

        $this->uuidParser = $this->createMock(UUIDParser::class);

        $this->labelsRepository = $this->createMock(LabelsRepository::class);

        $this->labelRelationsRepository = $this->createMock(LabelRelationsRepository::class);
    }

    /**
     * @test
     */
    public function it_throws_if_a_referenced_place_does_not_exist(): void
    {
        $eventDocumentValidator = new EventImportValidator(
            $this->placeRepository,
            $this->uuidParser,
            'user_id',
            $this->labelsRepository,
            $this->labelRelationsRepository
        );

        $document = [
            '@id' => 'https://io-acc.uitdatabank.be/events/43171ff2-574c-4659-943a-d8f81049f544',
            'mainLanguage' => 'nl',
            'name' => ['nl' => 'Test'],
            'calendarType' => 'permanent',
            'terms' => [['id' => '0.7.0.0.0', 'domain' => 'eventtype']],
            'location' => [
                '@id' => 'https://io-acc.uitdatabank.be/places/305fcdb2-e551-4e22-8fa3-672747d66169',
            ],
        ];

        try {
            $eventDocumentValidator->assert($document);
            $this->fail('AllOfException expected but not thrown.');
        } catch (AllOfException $e) {
            $this->assertContains(
                'Location with id https://io-acc.uitdatabank.be/places/305fcdb2-e551-4e22-8fa3-672747d66169 does not exist.',
                $e->getMessages()
            );
        }
    }

    /**
     * @test
     */
    public function it_does_not_throw_for_a_valid_event_with_the_minimum_required_fields(): void
    {
        $eventDocumentValidator = new EventImportValidator(
            $this->placeRepository,
            $this->uuidParser,
            'user_id',
            $this->labelsRepository,
            $this->labelRelationsRepository
        );

        $this->placeRepository->save(new JsonDocument('5f3f89a0-1738-41b8-aad5-c449f3af19cc', '{}'));

        $document = [
            '@id' => 'https://io-acc.uitdatabank.be/events/43171ff2-574c-4659-943a-d8f81049f544',
            'mainLanguage' => 'nl',
            'name' => ['nl' => 'Test'],
            'calendarType' => 'permanent',
            'terms' => [['id' => '0.7.0.0.0', 'domain' => 'eventtype']],
            'location' => [
                '@id' => 'https://io-acc.uitdatabank.be/places/5f3f89a0-1738-41b8-aad5-c449f3af19cc',
            ],
        ];

        $eventDocumentValidator->assert($document);
        $this->addToAssertionCount(1);
    }
}
