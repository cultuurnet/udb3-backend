<?php

namespace CultuurNet\UDB3\Model\Import\Command;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use Guzzle\Http\ClientInterface;

/**
 * Handles incoming import commands by sending them to the HTTP import API.
 */
class HttpImportCommandHandler implements CommandHandler
{
    /**
     * @var string
     */
    private $commandClassName;

    /**
     * @var IriGeneratorInterface
     */
    private $iriGenerator;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @param string $commandClassName
     * @param IriGeneratorInterface $iriGenerator
     * @param ClientInterface $httpClient
     */
    public function __construct(
        $commandClassName,
        IriGeneratorInterface $iriGenerator,
        ClientInterface $httpClient
    ) {
        if (!is_subclass_of($commandClassName, ImportDocument::class)) {
            throw new \InvalidArgumentException(
                'HttpImportCommandHandler only supports commands extending ImportDocument.'
            );
        }

        $this->commandClassName = $commandClassName;
        $this->iriGenerator = $iriGenerator;
        $this->httpClient = $httpClient;
    }

    /**
     * @param ImportDocument $command
     */
    public function handle($command)
    {
        if (get_class($command) !== $this->commandClassName) {
            return;
        }

        $documentId = $command->getDocumentId();
        $documentUrl = $command->getDocumentUrl();

        $putUrl = $this->iriGenerator->iri($documentId);
        $jsonLd = $this->fetchJsonLd($documentUrl);

        $this->httpClient
            ->put(
                $putUrl,
                [
                    'Authorization' => "Bearer {$command->getJwt()}",
                    'X-Api-Key' => $command->getApiKey(),
                ],
                $jsonLd
            )
            ->send();
    }

    /**
     * @param string $url
     * @return string
     */
    private function fetchJsonLd($url)
    {
        $response = $this->httpClient->get($url)->send();
        return $response->getBody(true);
    }
}
