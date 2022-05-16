<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Price\PriceInfoDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Offer\Item\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\PriceInfo\PriceInfo as LegacyPriceInfo;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UpdatePriceInfoRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();
        $offerType = $routeParameters->getOfferType();

        $parser = RequestBodyParserFactory::createBaseParser(
            new PriceInfoValidatingRequestBodyParser(),
            new DenormalizingRequestBodyParser(
                new PriceInfoDenormalizer(),
                PriceInfo::class
            )
        );

        try {
            /** @var PriceInfo $priceInfo */
            $priceInfo = $parser->parse($request)->getParsedBody();
            $this->commandBus->dispatch(
                new UpdatePriceInfo(
                    $offerId,
                    LegacyPriceInfo::fromUdb3ModelPriceInfo($priceInfo)
                )
            );
        } catch (\Exception $e) {
            throw ApiProblem::bodyInvalidDataWithDetail($e->getMessage());
        }

        return new NoContentResponse();
    }
}
