<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\FastRead;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Offer\Popularity\PopularityRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\FastRead\FastOfferJsonDocumentReader;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataRepository;
use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;
use CultuurNet\UDB3\Term\TermRepository;
use CultuurNet\UDB3\User\CurrentUser;

/**
 * Wires FastOfferJsonDocumentReader and FastReadMiddleware from real, already-registered
 * container services (the raw event/place/organizer caches, the DBAL connection, and the
 * same leaf repositories the normal decorator chain uses) rather than building anything
 * from scratch. See FastOfferJsonDocumentReader for why this is still fast despite reusing
 * container-resolved services: it deliberately avoids resolving 'event_jsonld_repository'
 * / 'place_jsonld_repository' themselves, which is where almost all the cost is.
 */
final class FastReadServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            FastOfferJsonDocumentReader::class,
            FastReadMiddleware::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            FastOfferJsonDocumentReader::class,
            fn () => new FastOfferJsonDocumentReader(
                $container->get('event_jsonld_cache'),
                $container->get('place_jsonld_cache'),
                $container->get('organizer_jsonld_cache'),
                $container->get('dbal_connection'),
                $container->get(ProductionRepository::class),
                $container->get(OfferMetadataRepository::class),
                $container->get(PopularityRepository::class),
                $container->get(ContributorRepository::class),
                $container->get('offer_permission_voter'),
                $container->get(CurrentUser::class)->getId(),
                $container->get(LabelServiceProvider::JSON_READ_REPOSITORY),
                $container->get(TermRepository::class),
                $container->get('media_url_mapping'),
                $container->get(NewsArticleRepository::class),
                LoggerFactory::create($container, LoggerName::forWeb()),
                $container->get('config')['curator_labels'],
                $container->get(DuplicatePlaceRepository::class),
                $container->get('event_iri_generator'),
                $container->get('place_iri_generator'),
                $container->get('config')['bookable_event']['dummy_place_ids'] ?? []
            )
        );

        $container->addShared(
            FastReadMiddleware::class,
            fn () => new FastReadMiddleware(
                $container->get(FastOfferJsonDocumentReader::class),
                LoggerFactory::create($container, LoggerName::forWeb())
            )
        );
    }
}
