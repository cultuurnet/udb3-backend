<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Model\Import\Event\EventCategoryResolver;
use CultuurNet\UDB3\Model\Import\Place\PlaceCategoryResolver;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryNotFound;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryResolverInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Offer\Commands\UpdateType;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Offer\OfferType;
use RuntimeException;

final class UpdateTypeHandler implements CommandHandler
{
    private OfferRepository $offerRepository;
    private CategoryResolverInterface $eventCategoryResolver;
    private CategoryResolverInterface $placeCategoryResolver;

    public function __construct(OfferRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
        $this->eventCategoryResolver = new EventCategoryResolver();
        $this->placeCategoryResolver = new PlaceCategoryResolver();
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateType) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offerType = $offer::getOfferType();

        // By only having an id in the UpdateType command and only resolving it in the command handler, we can better
        // make sure that the type is actually in the eventtype domain and valid for the given offer id. Otherwise some
        // code can just dispatch an UpdateType command with a random Category without any validation.
        $categoryResolver = $this->getCategoryResolver($offerType);
        $id = new CategoryID($command->getTypeId());
        $domain = new CategoryDomain('eventtype');
        $category = $categoryResolver->byIdInDomain($id, $domain);
        if (!$category) {
            throw CategoryNotFound::withIdInDomainForOfferType($id, $domain, $offerType);
        }

        $offer->updateType(EventType::fromUdb3ModelCategory($category));

        $this->offerRepository->save($offer);
    }

    private function getCategoryResolver(OfferType $offerType): CategoryResolverInterface
    {
        if ($offerType->sameValueAs(OfferType::EVENT())) {
            return $this->eventCategoryResolver;
        }
        if ($offerType->sameValueAs(OfferType::PLACE())) {
            return $this->placeCategoryResolver;
        }
        throw new RuntimeException('No CategoryResolver found for ' . strtolower($offerType->toNative()));
    }
}
