<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Http\Psr7FactoryInterface;
use CultuurNet\UDB3\Http\Psr7RequestAuthorizerInterface;
use CultuurNet\UDB3\Label;
use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class DefaultExternalOfferEditingService implements ExternalOfferEditingServiceInterface
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var Psr7FactoryInterface
     */
    private $psr7Factory;

    /**
     * @var Psr7RequestAuthorizerInterface
     */
    private $psr7RequestAuthorizer;

    /**
     * @param HttpClient $httpClient
     * @param Psr7FactoryInterface $psr7Factory
     * @param Psr7RequestAuthorizerInterface $psr7RequestAuthorizer
     */
    public function __construct(
        HttpClient $httpClient,
        Psr7FactoryInterface $psr7Factory,
        Psr7RequestAuthorizerInterface $psr7RequestAuthorizer
    ) {
        $this->httpClient = $httpClient;
        $this->psr7Factory = $psr7Factory;
        $this->psr7RequestAuthorizer = $psr7RequestAuthorizer;
    }

    /**
     * @param IriOfferIdentifier $identifier
     * @param Label $label
     */
    public function addLabel(IriOfferIdentifier $identifier, Label $label)
    {
        $uri = $this->createUri(
            (string) $identifier->getIri(),
            'labels'
        );

        $data = [
            'label' => (string) $label,
        ];

        $request = $this->createJsonPostRequest($uri, $data)->withHeader('Accept', 'application/json');
        $this->httpClient->sendRequest($request);
    }

    /**
     * @param string $iri
     * @param string $path
     * @return UriInterface
     */
    private function createUri($iri, $path)
    {
        return $this->psr7Factory->createUri(
            rtrim($iri, '/') . '/' . $path
        );
    }

    /**
     * @param UriInterface $uri
     * @param array $data
     * @return RequestInterface
     */
    private function createJsonPostRequest(UriInterface $uri, array $data)
    {
        /* @var RequestInterface $request */
        $request = $this->psr7Factory->createRequest('POST', $uri)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(
                $this->psr7Factory->createContentStream(
                    json_encode($data)
                )
            );

        return $this->psr7RequestAuthorizer->authorize($request);
    }
}
