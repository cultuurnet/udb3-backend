<?php

namespace CultuurNet\UDB3\Http\Label;

use CultuurNet\UDB3\Label\Services\WriteServiceInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Http\HttpFoundation\NoContent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\Identity\UUID;

class EditRestController
{
    /**
     * @var WriteServiceInterface
     */
    private $writeService;

    /**
     * EditRestController constructor.
     * @param WriteServiceInterface $writeService
     */
    public function __construct(WriteServiceInterface $writeService)
    {
        $this->writeService = $writeService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        $bodyAsArray = json_decode($request->getContent(), true);

        $uuid = $this->writeService->create(
            new LabelName($bodyAsArray['name']),
            Visibility::fromNative($bodyAsArray['visibility']),
            Privacy::fromNative($bodyAsArray['privacy'])
        );

        return new JsonResponse(['uuid' => $uuid->toNative()], 201);
    }

    public function patch(Request $request, string $id): Response
    {
        $bodyAsArray = json_decode($request->getContent(), true);
        $commandType = CommandType::fromNative($bodyAsArray['command']);

        $id = new UUID($id);

        switch ($commandType) {
            case CommandType::MAKE_VISIBLE():
                $this->writeService->makeVisible($id);
                break;
            case CommandType::MAKE_INVISIBLE():
                $this->writeService->makeInvisible($id);
                break;
            case CommandType::MAKE_PUBLIC():
                $this->writeService->makePublic($id);
                break;
            case CommandType::MAKE_PRIVATE():
                $this->writeService->makePrivate($id);
                break;
        }

        return new NoContent();
    }
}
