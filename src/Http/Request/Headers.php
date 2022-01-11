<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use Negotiation\Accept;
use Negotiation\Exception\Exception as NegotiationException;
use Negotiation\Negotiator;
use Psr\Http\Message\ServerRequestInterface;

final class Headers
{
    private ServerRequestInterface $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Parses the Accept header of the request and determines the best Content-Type to use in the response based on a
     * list of possible Content-Types.
     *
     * If there are one or more Accept lines and none matches the possible Content-Types, a 406 ApiProblem will be
     * thrown.
     *
     * @param string[] $possibleContentTypes
     * @return string
     *
     * @throws ApiProblem
     */
    public function determineResponseContentType(array $possibleContentTypes): string
    {
        $acceptHeader = $this->request->getHeaderLine('accept');
        if ($acceptHeader === '' || $acceptHeader === '*') {
            return $possibleContentTypes[0];
        }

        $negotiator = new Negotiator();

        $notAcceptable = ApiProblem::notAcceptable('Acceptable media types are: ' . implode(', ', $possibleContentTypes));

        try {
            /** @var Accept|null $mediaType */
            $mediaType = $negotiator->getBest($acceptHeader, $possibleContentTypes);
        } catch (NegotiationException $e) {
            throw $notAcceptable;
        }

        if ($mediaType === null) {
            throw $notAcceptable;
        }

        return $mediaType->getValue();
    }
}
