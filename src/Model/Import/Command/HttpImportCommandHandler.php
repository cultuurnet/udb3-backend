<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Command;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use GuzzleHttp\Client;

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
     * @var Client
     */
    private $httpClient;

    public function __construct(
        string $commandClassName,
        IriGeneratorInterface $iriGenerator,
        Client $httpClient
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
                    'body' => $jsonLd,
                ]
            );
    }

    private function fetchJsonLd(string $url): string
    {
        $response = $this->httpClient->get($url);
        return (string) $response->getBody();
    }
}
