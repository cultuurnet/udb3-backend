<?php

namespace CultuurNet\UDB3\Http\JSONLD;

use CultuurNet\UDB3\Http\JsonLdResponse;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class ContextController
{
    const DEFAULT_BASE_PATH = 'https://io.uitdatabank.be';

    /**
     * @var Url
     */
    protected $basePath;

    /**
     * @var StringLiteral
     */
    protected $fileDirectory;

    /**
     * ContextController constructor.
     * @param StringLiteral $fileDirectory
     */
    public function __construct(
        StringLiteral $fileDirectory
    ) {
        $this->fileDirectory = $fileDirectory;
    }

    /**
     * @param Url $basePath
     * @return ContextController
     */
    public function withCustomBasePath(Url $basePath)
    {
        $controller = clone $this;

        $controller->basePath = Url::fromNative(
            rtrim((string) $basePath, '/')
        );

        return $controller;
    }

    /**
     * @param string $entityName
     *
     * @return JsonLdResponse
     */
    public function get($entityName)
    {
        $entityType = EntityType::fromNative($entityName);
        return $this->getContext($entityType);
    }

    /**
     * @param EntityType $entityType
     *  The entity type that you want the context for.
     *
     * @return JsonLdResponse
     */
    private function getContext(EntityType $entityType)
    {
        $entityFilePath = $this->fileDirectory . $entityType->toNative() . '.jsonld';

        $jsonData = json_decode($this->getEntityFile($entityFilePath));

        if ($this->basePath) {
            $this->replaceJsonPropertyBasePath($jsonData, 'udb', $this->basePath);
            $this->replaceJsonPropertyBasePath($jsonData, '@base', $this->basePath);
        }

        return new JsonLdResponse($jsonData);
    }

    /**
     * @param object $jsonData
     *  The json object that should have its base path replaced.
     * @param string $propertyName
     *  The name of the property where you want to replace the base path.
     * @param Url $basePath
     *  The new base path.
     */
    private function replaceJsonPropertyBasePath($jsonData, $propertyName, Url $basePath)
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
