<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class DenormalizerRequestBodyParser implements RequestBodyParser
{
    private AssociativeArrayRequestBodyParser $associativeArrayRequestBodyParser;
    private DenormalizerInterface $denormalizer;
    private string $className;

    public function __construct(DenormalizerInterface $denormalizer, string $className)
    {
        $this->associativeArrayRequestBodyParser = new AssociativeArrayRequestBodyParser();
        $this->denormalizer = $denormalizer;
        $this->className = $className;
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $associativeData = $this->associativeArrayRequestBodyParser->parse($request)->getParsedBody();
        $denormalized = $this->denormalizer->denormalize($associativeData, $this->className);
        return $request->withParsedBody($denormalized);
    }
}
