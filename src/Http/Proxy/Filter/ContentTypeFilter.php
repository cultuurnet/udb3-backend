<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy\Filter;

use Psr\Http\Message\RequestInterface;
use CultuurNet\UDB3\StringLiteral;

class ContentTypeFilter implements FilterInterface
{
    public const CONTENT_TYPE = 'Content-Type';
    /**
     * @var StringLiteral
     */
    private $contentType;

    public function __construct(StringLiteral $contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * @inheritdoc
     */
    public function matches(RequestInterface $request)
    {
        $contentType = $request->getHeaderLine(self::CONTENT_TYPE);
        return ($contentType === $this->contentType->toNative());
    }
}
