<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\EventFacilityResolver;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Offer\Commands\UpdateFacilities;
use CultuurNet\UDB3\Offer\Offer;
use CultuurNet\UDB3\Offer\OfferFacilityResolverInterface;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\Place;
use CultuurNet\UDB3\Place\PlaceFacilityResolver;
use Exception;
use RuntimeException;
use ValueObjects\StringLiteral\StringLiteral;

final class UpdateFacilitiesHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    public function __construct(OfferRepository $offer)
    {
        $this->offerRepository = $offer;
    }

    public function handle($command): void
    {
        if (!($command instanceof UpdateFacilities)) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());

        $facilityIds = $command->getFacilityIds();
        $facilityResolver = $this->getFacilityResolver($offer);

        $facilities = array_map(
            static function (string $facilityId) use ($facilityResolver, $offer) {
                try {
                    return $facilityResolver->byId(new StringLiteral($facilityId));
                } catch (Exception $e) {
                    throw ApiProblem::bodyInvalidDataWithDetail(
                        sprintf(
                            'Facility id "%s" is invalid or not applicable to %s.',
                            $facilityId,
                            $offer::getOfferType()->toNative()
                        )
                    );
                }
            },
            $facilityIds
        );

        $offer->updateFacilities($facilities);
        $this->offerRepository->save($offer);
    }

    private function getFacilityResolver(Offer $offer): OfferFacilityResolverInterface
    {
        if ($offer instanceof Event) {
            return new EventFacilityResolver();
        }
        if ($offer instanceof Place) {
            return new PlaceFacilityResolver();
        }
        throw new RuntimeException('No OfferFacilityResolverInterface found for unknown type ' . $offer::getOfferType()->toNative());
    }
}
