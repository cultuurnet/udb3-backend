<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Note: Currently this request handler can only change the workflowStatus of an Offer, based on a specific custom
 * mime-type being included in the content-type header.
 * This functionality has been superseded by UpdateWorkflowStatusRequestHandler.
 */
final class PatchOfferRequestHandler implements RequestHandlerInterface
{
    public const DOMAIN_MODEL_REGEX = '/.*domain-model=([a-zA-Z]*)/';

    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerType = $routeParameters->getOfferType();
        $offerId = $routeParameters->getOfferId();

        $domainModel = $this->parseDomainModelNameFromRequest($request);
        $commandClass = 'CultuurNet\UDB3\\' . $offerType->toString() . '\Commands\Moderation\\' . $domainModel;

        if (!class_exists($commandClass)) {
            throw new \InvalidArgumentException('The command in content-type is not supported.');
        }

        if ($domainModel === 'Reject') {
            $content = Json::decode($request->getBody()->getContents());
            $command = new $commandClass($offerId, $content->reason);
        } elseif ($domainModel === 'Publish') {
            $publicationDate = $this->getPublicationDate($request);

            $command = new $commandClass($offerId, $publicationDate);
        } else {
            $command = new $commandClass($offerId);
        }

        $this->commandBus->dispatch($command);

        return new NoContentResponse();
    }

    private function parseDomainModelNameFromRequest(ServerRequestInterface $request): string
    {
        $contentType = $request->getHeaderLine('Content-Type');
        preg_match(self::DOMAIN_MODEL_REGEX, $contentType, $matches);

        if (!is_array($matches) || !array_key_exists(1, $matches)) {
            throw new \Exception('Unable to determine domain-model');
        }

        return $matches[1];
    }

    private function getPublicationDate(ServerRequestInterface $request): ?DateTime
    {
        $content = $request->getBody()->getContents();
        if (empty($content)) {
            return null;
        }

        $content = Json::decode($content);

        if (!isset($content->publicationDate)) {
            return null;
        }

        try {
            $publicationDate = new DateTime($content->publicationDate);
        } catch (\Exception $exp) {
            throw new \InvalidArgumentException('The publication date is not a valid date format.');
        }

        return $publicationDate;
    }
}
