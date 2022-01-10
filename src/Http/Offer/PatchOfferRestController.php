<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\HttpFoundation\Response\NoContent;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\StringLiteral\StringLiteral;

class PatchOfferRestController
{
    public const DOMAIN_MODEL_REGEX = '/.*domain-model=([a-zA-Z]*)/';


    private CommandBus $commandBus;


    private OfferType $offerType;

    /**
     * PatchOfferRestController constructor.
     */
    public function __construct(
        OfferType $offerType,
        CommandBus $commandBus
    ) {
        $this->offerType = $offerType;
        $this->commandBus = $commandBus;
    }

    public function handle(Request $request, string $cdbid): Response
    {
        $domainModel = $this->parseDomainModelNameFromRequest($request);
        $commandClass = 'CultuurNet\UDB3\\' . $this->offerType->toString() . '\Commands\Moderation\\' . $domainModel;

        if (!class_exists($commandClass)) {
            throw new \InvalidArgumentException('The command in content-type is not supported.');
        }

        if ($domainModel === 'Reject') {
            $content = json_decode($request->getContent());
            $reason = new StringLiteral($content->reason);

            $command = new $commandClass($cdbid, $reason);
        } elseif ($domainModel === 'Publish') {
            $publicationDate = $this->getPublicationDate($request);

            $command = new $commandClass($cdbid, $publicationDate);
        } else {
            $command = new $commandClass($cdbid);
        }

        $this->commandBus->dispatch($command);

        return new NoContent();
    }

    /**
     * @throws \Exception
     */
    private function parseDomainModelNameFromRequest(Request $request): string
    {
        $contentType = $request->headers->get('Content-Type');
        preg_match(self::DOMAIN_MODEL_REGEX, $contentType, $matches);

        if (!is_array($matches) || !array_key_exists(1, $matches)) {
            throw new \Exception('Unable to determine domain-model');
        }

        return $matches[1];
    }

    private function getPublicationDate(Request $request): ?DateTime
    {
        $content = json_decode($request->getContent());

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
