<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Import;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class ImportPriceInfoRequestBodyParser implements RequestBodyParser
{
    private array $basePriceNames;

    public function __construct(array $basePriceNames)
    {
        $this->basePriceNames = $basePriceNames;
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $json = $request->getParsedBody();

        if (isset($json->priceInfo) && is_array($json->priceInfo)) {
            $json->priceInfo = array_map(
                function ($priceInfo) {
                    if (is_object($priceInfo) && isset($priceInfo->category) && $priceInfo->category === 'base') {
                        $priceInfo->name = (object) $this->basePriceNames;
                    }
                    return $priceInfo;
                },
                $json->priceInfo
            );
        }

        return $request->withParsedBody($json);
    }
}
