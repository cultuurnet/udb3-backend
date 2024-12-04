<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Model\Import\Event\EventCategoryResolver;
use CultuurNet\UDB3\Model\Import\Place\PlaceCategoryResolver;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryNotFound;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryResolverInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Offer\Commands\UpdateFacilities;
use CultuurNet\UDB3\Offer\Offer;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\Place;
use RuntimeException;

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
        $facilityResolver = $this->getCategoryResolver($offer);

        $facilities = array_map(
            static function (string $facilityId) use ($facilityResolver, $offer): Category {
                $id = new CategoryID($facilityId);
                $domain = new CategoryDomain('facility');

                $category = $facilityResolver->byIdInDomain($id, $domain);

                if (!$category) {
                    throw CategoryNotFound::withIdInDomainForOfferType($id, $domain, $offer::getOfferType());
                }

                return $category;
            },
            $facilityIds
        );

        $offer->updateFacilities($facilities);
        $this->offerRepository->save($offer);
    }

    private function getCategoryResolver(Offer $offer): CategoryResolverInterface
    {
        if ($offer instanceof Event) {
            return new EventCategoryResolver();
        }
        if ($offer instanceof Place) {
            return new PlaceCategoryResolver();
        }
        throw new RuntimeException('No CategoryResolverInterface found for unknown type ' . $offer::getOfferType()->toString());
    }
}
