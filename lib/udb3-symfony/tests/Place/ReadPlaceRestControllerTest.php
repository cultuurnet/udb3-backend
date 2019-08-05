<?php

namespace CultuurNet\UDB3\Symfony\Place;

use CultuurNet\SearchV3\Serializer\SerializerInterface;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use CultuurNet\SearchV3\ValueObjects\Place;

class ReadPlaceRestControllerTest extends \PHPUnit_Framework_TestCase
{
    const EXISTING_ID = 'existingId';
    const NON_EXISTING_ID = 'nonExistingId';
    const REMOVED_ID = 'removedId';

    /**
     * @var ReadPlaceRestController
     */
    private $placeRestController;

    /**
     * @var JsonDocument
     */
    private $jsonDocument;

    /**
     * @var string
     */
    private $calSum;

    /**
     * @var Place
     */
    private $place;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->jsonDocument = new JsonDocument('id', 'place');
        $this->calSum = 'zondag 7 oktober 2018 van 12:15 tot 18:00';

        $this->place = new Place();
        $this->place->setStartDate(new \DateTime('2018-10-07 12:15:00'));
        $this->place->setEndDate(new \DateTime('2018-10-07 18:00:00'));
        $this->place->setCalendarType('single');

        $entityServiceInterface = $this->createMock(EntityServiceInterface::class);

        $serializerInterface = $this->createMock(SerializerInterface::class);

        $entityServiceInterface->method('getEntity')
            ->willReturnCallback(
                function ($id) {
                    switch ($id) {
                        case self::EXISTING_ID:
                            return $this->jsonDocument->getRawBody();
                        case self::REMOVED_ID:
                            throw new DocumentGoneException();
                        default:
                            return null;
                    }
                }
            );

        $serializerInterface->method('deserialize')
            ->willReturnCallback(
                function () {
                    return $this->place;
                }
            );

        /**
         * @var EntityServiceInterface $entityServiceInterface
         * @var SerializerInterface $serializerInterface
         */
        $this->placeRestController = new ReadPlaceRestController(
            $entityServiceInterface,
            $serializerInterface
        );
    }

    /**
     * @test
     */
    public function it_returns_a_http_response_with_json_get_for_an_event()
    {
        $jsonResponse = $this->placeRestController->get(self::EXISTING_ID);

        $this->assertEquals(Response::HTTP_OK, $jsonResponse->getStatusCode());
        $this->assertEquals($this->jsonDocument->getRawBody(), $jsonResponse->getContent());
    }

    /**
     * @test
     */
    public function it_returns_a_http_response_with_error_NOT_FOUND_for_getting_a_non_existing_event()
    {
        $jsonResponse = $this->placeRestController->get(self::NON_EXISTING_ID);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $jsonResponse->getStatusCode());
    }

    /**
     * @test
     */
    public function it_returns_a_http_response_with_a_calendar_summary_for_a_place()
    {
        $request = new Request(array('style' => 'text', 'format' => 'lg'));
        $calSumResponse = $this->placeRestController->getCalendarSummary(self::EXISTING_ID, $request);

        $this->assertEquals($this->calSum, $calSumResponse);
    }
}
