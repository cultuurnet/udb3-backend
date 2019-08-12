<?php

namespace CultuurNet\UDB3\Http\Label;

use CultuurNet\UDB3\Label\Services\WriteResult;
use CultuurNet\UDB3\Label\Services\WriteServiceInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\Identity\UUID;

class EditRestControllerTest extends TestCase
{
    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var WriteServiceInterface|MockObject
     */
    private $writeService;

    /**
     * @var EditRestController
     */
    private $editRestController;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->writeService = $this->createMock(WriteServiceInterface::class);

        $this->editRestController = new EditRestController($this->writeService);
    }

    /**
     * @test
     */
    public function it_returns_json_response_for_create()
    {
        $contentAsArray = [
            'name' => 'labelName',
            'visibility' => 'invisible',
            'privacy' => 'private',
        ];

        $request = $this->createRequestWithContent($contentAsArray);

        $this->writeService->expects($this->once())
            ->method('create')
            ->with(
                new LabelName($contentAsArray['name']),
                Visibility::fromNative($contentAsArray['visibility']),
                Privacy::fromNative($contentAsArray['privacy'])
            )
            ->willReturn($this->uuid);

        $expectedJson = [
            'uuid' => $this->uuid->toNative()
        ];

        $jsonResponse = $this->editRestController->create($request);
        $actualJson = json_decode($jsonResponse->getContent(), true);

        $this->assertEquals($expectedJson, $actualJson);
    }

    /**
     * @test
     * @dataProvider patchProvider
     * @param array $contentAsArray
     * @param string $method
     */
    public function it_handles_patch(
        array $contentAsArray,
        string $method
    ) {
        $request = $this->createRequestWithContent($contentAsArray);

        $this->writeService->expects($this->once())
            ->method($method);

        $response = $this->editRestController->patch($request, $this->uuid);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @return array
     */
    public function patchProvider()
    {
        return [
            [['command' => 'MakeVisible'], 'makeVisible'],
            [['command' => 'MakeInvisible'], 'makeInvisible'],
            [['command' => 'MakePublic'], 'makePublic'],
            [['command' => 'MakePrivate'], 'makePrivate']
        ];
    }

    /**
     * @param array $contentAsArray
     * @return Request
     */
    private function createRequestWithContent(array $contentAsArray)
    {
        return new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode($contentAsArray)
        );
    }
}
