<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

class ContentTypeLookup implements ContentTypeLookupInterface
{
    /**
     * @var string[]
     */
    protected array $payloadClassToContentTypeMap;

    public function __construct(array $mapping = [])
    {
        $this->payloadClassToContentTypeMap = $mapping;
    }

    public function withContentType(string $payloadClass, string $contentType): ContentTypeLookupInterface
    {
        $c = clone $this;
        $c->setContentType($payloadClass, $contentType);
        return $c;
    }

    private function setContentType(string $payloadClass, string $contentType): void
    {
        if (!is_string($payloadClass)) {
            throw new \InvalidArgumentException(
                'Value for argument payloadClass should be a string'
            );
        }

        if (!is_string($contentType)) {
            throw new \InvalidArgumentException(
                'Value for argument contentType should be a string'
            );
        }

        if (isset($this->payloadClassToContentTypeMap[$payloadClass])) {
            $currentContentType = $this->payloadClassToContentTypeMap[$payloadClass];
            throw new \InvalidArgumentException(
                'Content type for class ' . $payloadClass . ' was already set to ' . $currentContentType
            );
        }
        $this->payloadClassToContentTypeMap[$payloadClass] = $contentType;
    }

    public function getContentType(string $payloadClass): string
    {
        if (isset($this->payloadClassToContentTypeMap[$payloadClass])) {
            return $this->payloadClassToContentTypeMap[$payloadClass];
        }

        throw new \RuntimeException(
            'Unable to find the content type of ' . $payloadClass
        );
    }
}
