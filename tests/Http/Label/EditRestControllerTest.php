<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use CultuurNet\UDB3\Label\Services\WriteServiceInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

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
        $this->uuid = new UUID('3b76d445-5302-4c6a-9194-94632bc4d91f');

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
            'uuid' => $this->uuid->toString(),
        ];

        $jsonResponse = $this->editRestController->create($request);
        $actualJson = json_decode($jsonResponse->getContent(), true);

        $this->assertEquals($expectedJson, $actualJson);
    }

    /**
     * @test
     * @dataProvider patchProvider
     */
    public function it_handles_patch(
        array $contentAsArray,
        string $method
    ) {
        $request = $this->createRequestWithContent($contentAsArray);

        $this->writeService->expects($this->once())
            ->method($method);

        $response = $this->editRestController->patch($request, $this->uuid->toString());

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
            [['command' => 'MakePrivate'], 'makePrivate'],
        ];
    }

    /**
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
