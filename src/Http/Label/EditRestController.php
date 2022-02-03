<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\Services\WriteServiceInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\HttpFoundation\Response\NoContent;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EditRestController
{
    private WriteServiceInterface $writeService;

    /**
     * EditRestController constructor.
     */
    public function __construct(WriteServiceInterface $writeService)
    {
        $this->writeService = $writeService;
    }

    public function create(Request $request): JsonResponse
    {
        $bodyAsArray = Json::decodeAssociatively($request->getContent());

        $uuid = $this->writeService->create(
            new LabelName($bodyAsArray['name']),
            Visibility::fromNative($bodyAsArray['visibility']),
            Privacy::fromNative($bodyAsArray['privacy'])
        );

        return new JsonResponse(['uuid' => $uuid->toNative()], 201);
    }

    public function patch(Request $request, string $id): Response
    {
        $bodyAsArray = Json::decodeAssociatively($request->getContent());
        $commandType = new CommandType($bodyAsArray['command']);

        $uuid = new UUID($id);

        switch ($commandType) {
            case CommandType::makeVisible():
                $this->writeService->makeVisible($uuid);
                break;
            case CommandType::makeInvisible():
                $this->writeService->makeInvisible($uuid);
                break;
            case CommandType::makePublic():
                $this->writeService->makePublic($uuid);
                break;
            case CommandType::makePrivate():
                $this->writeService->makePrivate($uuid);
                break;
        }

        return new NoContent();
    }
}
