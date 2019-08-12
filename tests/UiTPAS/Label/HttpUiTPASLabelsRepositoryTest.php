<?php

namespace CultuurNet\UDB3\UiTPAS\Label;

use CultuurNet\UDB3\Label;
use Guzzle\Http\Client;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use PHPUnit\Framework\TestCase;

class HttpUiTPASLabelsRepositoryTest extends TestCase
{
    /**
     * @var Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private $httpClient;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var HttpUiTPASLabelsRepository
     */
    private $repository;

    public function setUp()
    {
        $this->httpClient = $this->createMock(Client::class);
        $this->endpoint = 'http://uitpas.dev/labels';

        $this->repository = new HttpUiTPASLabelsRepository($this->httpClient, $this->endpoint);
    }

    /**
     * @test
     */
    public function it_should_return_an_array_of_uitpas_labels()
    {
        // Note that these are just example card system ids.
        $expected = [
            'c73d78b7-95a7-45b3-bde5-5b2ec7b13afa' => new Label('Paspartoe'),
            'ebd91df0-8ed7-4522-8401-ef5508ad1426' => new Label('UiTPAS'),
            'f23ccb75-190a-4814-945e-c95e83101cc5' => new Label('UiTPAS Gent'),
            '98ce6fbc-fb68-4efc-b8c7-95763cb967dd' => new Label('UiTPAS Oostende'),
            '68f849c0-bf55-4f73-b0f4-e0683bf0c807' => new Label('UiTPAS regio Aalst'),
            'cd6200cc-5b9d-43fd-9638-f6cc27f1c9b8' => new Label('UiTPAS Dender'),
            'd9cf96b6-1256-4760-b66b-1c31152d7db4' => new Label('UiTPAS Zuidwest'),
            'aaf3a58e-2aac-45b3-a9e9-3f3ebf467681' => new Label('UiTPAS Mechelen'),
            '47256d4c-47e8-4046-b9bb-acb166920f76' => new Label('UiTPAS Kempen'),
            '54b5273e-5e0b-4c1e-b33f-93eca55eb472' => new Label('UiTPAS Maasmechelen'),
        ];

        $json = '
        {
          "c73d78b7-95a7-45b3-bde5-5b2ec7b13afa": "Paspartoe",
          "ebd91df0-8ed7-4522-8401-ef5508ad1426": "UiTPAS",
          "f23ccb75-190a-4814-945e-c95e83101cc5": "UiTPAS Gent",
          "98ce6fbc-fb68-4efc-b8c7-95763cb967dd": "UiTPAS Oostende",
          "68f849c0-bf55-4f73-b0f4-e0683bf0c807": "UiTPAS regio Aalst",
          "cd6200cc-5b9d-43fd-9638-f6cc27f1c9b8": "UiTPAS Dender",
          "d9cf96b6-1256-4760-b66b-1c31152d7db4": "UiTPAS Zuidwest",
          "aaf3a58e-2aac-45b3-a9e9-3f3ebf467681": "UiTPAS Mechelen",
          "47256d4c-47e8-4046-b9bb-acb166920f76": "UiTPAS Kempen",
          "54b5273e-5e0b-4c1e-b33f-93eca55eb472": "UiTPAS Maasmechelen"
        }';

        $request = $this->createMock(Request::class);

        $response = $this->createMock(Response::class);

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with($this->endpoint)
            ->willReturn($request);

        $request->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($json);

        $actual = $this->repository->loadAll();

        $this->assertEquals($expected, $actual);
    }
}
