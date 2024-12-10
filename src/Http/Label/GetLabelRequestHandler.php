<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetLabelRequestHandler implements RequestHandlerInterface
{
    private ReadRepositoryInterface $labelRepository;

    public function __construct(ReadRepositoryInterface $labelRepository)
    {
        $this->labelRepository = $labelRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $labelId = (new RouteParameters($request))->getLabelId();

        try {
            $entity = $this->labelRepository->getByUuid(new UUID($labelId));
        } catch (\InvalidArgumentException $exception) {
            $entity = $this->labelRepository->getByName($labelId);
        }

        if (!$entity) {
            throw ApiProblem::labelNotFound($labelId);
        }

        return new JsonResponse($entity);
    }
}
