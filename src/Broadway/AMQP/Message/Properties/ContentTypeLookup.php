<?php

namespace CultuurNet\BroadwayAMQP\Message\Properties;

class ContentTypeLookup implements ContentTypeLookupInterface
{
    /**
     * @var string[]
     */
    protected $payloadClassToContentTypeMap;

    public function __construct(array $mapping = [])
    {
        $this->payloadClassToContentTypeMap = $mapping;
    }

    /**
     * @inheritdoc
     */
    public function withContentType($payloadClass, $contentType)
    {
        $c = clone $this;
        $c->setContentType($payloadClass, $contentType);
        return $c;
    }

    /**
     * @param string $payloadClass
     * @param string $contentType
     */
    private function setContentType($payloadClass, $contentType)
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

    /**
     * @inheritdoc
     */
    public function getContentType($payloadClass)
    {
        if (isset($this->payloadClassToContentTypeMap[$payloadClass])) {
            return $this->payloadClassToContentTypeMap[$payloadClass];
        }

        throw new \RuntimeException(
            'Unable to find the content type of ' . $payloadClass
        );
    }
}
