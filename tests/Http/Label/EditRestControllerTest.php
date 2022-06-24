<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use CultuurNet\UDB3\Label\Services\WriteServiceInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class EditRestControllerTest extends TestCase
{
    private UUID $uuid;

    /**
     * @var WriteServiceInterface|MockObject
     */
    private $writeService;

    private EditRestController $editRestController;

    protected function setUp(): void
    {
        $this->uuid = new UUID('3b76d445-5302-4c6a-9194-94632bc4d91f');

        $this->writeService = $this->createMock(WriteServiceInterface::class);

        $this->editRestController = new EditRestController($this->writeService);
    }

    /**
     * @test
     */
    public function it_returns_json_response_for_create(): void
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
                new Visibility($contentAsArray['visibility']),
                new Privacy($contentAsArray['privacy'])
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
    ): void {
        $request = $this->createRequestWithContent($contentAsArray);

        $this->writeService->expects($this->once())
            ->method($method);

        $response = $this->editRestController->patch($request, $this->uuid->toString());

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function patchProvider(): array
    {
        return [
            [['command' => 'MakeVisible'], 'makeVisible'],
            [['command' => 'MakeInvisible'], 'makeInvisible'],
            [['command' => 'MakePublic'], 'makePublic'],
            [['command' => 'MakePrivate'], 'makePrivate'],
        ];
    }

    private function createRequestWithContent(array $contentAsArray): Request
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
