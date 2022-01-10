<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\JSONLD;

use CultuurNet\UDB3\HttpFoundation\Response\JsonLdResponse;
use CultuurNet\UDB3\Json;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class ContextController
{
    public const DEFAULT_BASE_PATH = 'https://io.uitdatabank.be';

    private ?Url $basePath = null;

    private StringLiteral $fileDirectory;

    public function __construct(StringLiteral $fileDirectory)
    {
        $this->fileDirectory = $fileDirectory;
    }

    public function withCustomBasePath(Url $basePath): ContextController
    {
        $controller = clone $this;

        $controller->basePath = Url::fromNative(
            rtrim((string) $basePath, '/')
        );

        return $controller;
    }

    public function get(string $entityName): JsonLdResponse
    {
        $entityType = new EntityType($entityName);
        return $this->getContext($entityType);
    }

    private function getContext(EntityType $entityType): JsonLdResponse
    {
        $entityFilePath = $this->fileDirectory . $entityType->toString() . '.jsonld';

        $jsonData = Json::decode($this->getEntityFile($entityFilePath));

        if ($this->basePath) {
            $this->replaceJsonPropertyBasePath($jsonData, 'udb', $this->basePath);
            $this->replaceJsonPropertyBasePath($jsonData, '@base', $this->basePath);
        }

        return new JsonLdResponse($jsonData);
    }

    private function replaceJsonPropertyBasePath(object $jsonData, string $propertyName, Url $basePath): void
    {
        $jsonData
            ->{'@context'}
            ->{$propertyName} = str_replace(
                self::DEFAULT_BASE_PATH,
                (string) $basePath,
                $jsonData->{'@context'}->{$propertyName}
            );
    }

    private function getEntityFile(string $path): string
    {
        return file_get_contents($path);
    }
}
