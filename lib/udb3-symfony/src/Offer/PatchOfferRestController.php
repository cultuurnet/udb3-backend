<?php

namespace CultuurNet\UDB3\Symfony\Offer;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Symfony\HttpFoundation\NoContent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\StringLiteral\StringLiteral;

class PatchOfferRestController
{
    const DOMAIN_MODEL_REGEX = '/.*domain-model=([a-zA-Z]*)/';

    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var OfferType
     */
    private $offerType;

    /**
     * PatchOfferRestController constructor.
     * @param OfferType $offerType
     * @param CommandBusInterface $commandBus
     */
    public function __construct(
        OfferType $offerType,
        CommandBusInterface $commandBus
    ) {
        $this->offerType = $offerType;
        $this->commandBus = $commandBus;
    }

    public function handle(Request $request, string $cdbid): Response
    {
        $domainModel = $this->parseDomainModelNameFromRequest($request);
        $commandClass = 'CultuurNet\UDB3\\' . $this->offerType->getValue() . '\Commands\Moderation\\' . $domainModel;

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
     * @param Request $request
     * @return string
     * @throws \Exception
     */
    private function parseDomainModelNameFromRequest(Request $request)
    {
        $contentType = $request->headers->get('Content-Type');
        preg_match(self::DOMAIN_MODEL_REGEX, $contentType, $matches);

        if (!is_array($matches) || !array_key_exists(1, $matches)) {
            throw new \Exception('Unable to determine domain-model');
        }

        return $matches[1];
    }

    /**
     * @param Request $request
     * @return \DateTimeInterface
     */
    private function getPublicationDate(Request $request)
    {
        $content = json_decode($request->getContent());

        if (!isset($content->publicationDate)) {
            return null;
        }

        try {
            $publicationDate = new \DateTime($content->publicationDate);
        } catch (\Exception $exp) {
            throw new \InvalidArgumentException('The publication date is not a valid date format.');
        }

        return $publicationDate;
    }
}
