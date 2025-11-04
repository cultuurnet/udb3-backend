<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\UWP\Exception\UwpApiFailure;
use CultuurNet\UDB3\UWP\UwpApiConnector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetVerenigingsloketRequestHandler implements RequestHandlerInterface
{
    public function __construct(private UwpApiConnector $uwpApiConnector)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $organizerId = $routeParameters->getOrganizerId();

        try {
            $result = $this->uwpApiConnector->fetchVerenigingsloketConnectionForOrganizer(new Uuid($organizerId));
        } catch (UwpApiFailure) {
            throw ApiProblem::uwpApiFailure();
        }

        if ($result === null) {
            throw ApiProblem::verenigingsloketMatchNotFound($organizerId);
        }

        return new JsonResponse([
            'vcode' => $result->getVcode(),
            'url' => $result->getUrl(),
        ]);
    }
}
