<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD\FastRead;

use CultuurNet\UDB3\Contributor\ContributorEnrichedRepository;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Event\Productions\ProductionEnrichedEventRepository;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Event\Recommendations\DBALRecommendationsRepository;
use CultuurNet\UDB3\Event\Recommendations\RecommendationForEnrichedOfferRepository;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Media\MediaUrlMapping;
use CultuurNet\UDB3\Model\Serializer\Place\NilLocationNormalizer;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Offer\Popularity\PopularityEnrichedOfferRepository;
use CultuurNet\UDB3\Offer\Popularity\PopularityRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CuratorEnrichedOfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\EmbeddingRelatedResourcesOfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\MediaUrlOfferRepositoryDecorator;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\PropertyPolyfillOfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\TermLabelOfferRepositoryDecorator;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataEnrichedOfferRepository;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataRepository;
use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;
use CultuurNet\UDB3\Place\Canonical\DuplicatePlacesEnrichedPlaceRepository;
use CultuurNet\UDB3\Place\DummyPlaceProjectionEnricher;
use CultuurNet\UDB3\Place\NilLocationEnrichedPlaceRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\Term\TermRepository;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Reproduces the exact decorator chains built by EventJSONLDServiceProvider and
 * PlaceJSONLDServiceProvider, but wired directly with `new` instead of resolving
 * `event_jsonld_repository` / `place_jsonld_repository` from the container.
 *
 * Why this is faster: resolving the fully-decorated repository from the container
 * triggers ~194 nested League\Container::get() calls (measured via profiling), most of
 * it container resolution overhead rather than the decorators' own logic. Building the
 * same objects directly costs under a millisecond. All leaf dependencies below are still
 * themselves resolved from the container by the caller (see FastReadServiceProvider), so
 * this reuses the exact same repositories/config/connections as the normal path - only
 * the container's resolution machinery for the TOP-level 'event_jsonld_repository' /
 * 'place_jsonld_repository' services is skipped.
 *
 * `BroadcastingDocumentRepositoryDecorator` (the outermost decorator in both service
 * providers) is intentionally not reproduced here: it only does work on save()/remove(),
 * its fetch() is a pure passthrough, so omitting it changes nothing for reads.
 *
 * See claude/read-side-performance-investigation.md for the full investigation and
 * measurements this is based on.
 */
final class FastOfferJsonDocumentReader
{
    private DocumentRepository $eventCache;
    private DocumentRepository $placeCache;
    private DocumentRepository $organizerCache;
    private Connection $connection;
    private ProductionRepository $productionRepository;
    private OfferMetadataRepository $offerMetadataRepository;
    private PopularityRepository $popularityRepository;
    private ContributorRepository $contributorRepository;
    private PermissionVoter $permissionVoter;
    private ?string $currentUserId;
    private ReadRepositoryInterface $labelReadRepository;
    private TermRepository $termRepository;
    private MediaUrlMapping $mediaUrlMapping;
    private NewsArticleRepository $newsArticleRepository;
    private LoggerInterface $logger;
    private array $curatorLabels;
    private DuplicatePlaceRepository $duplicatePlaceRepository;
    private IriGeneratorInterface $eventIriGenerator;
    private IriGeneratorInterface $placeIriGenerator;
    private array $dummyPlaceIds;

    private ?DocumentRepository $eventReader = null;
    private ?DocumentRepository $placeReader = null;

    public function __construct(
        DocumentRepository $eventCache,
        DocumentRepository $placeCache,
        DocumentRepository $organizerCache,
        Connection $connection,
        ProductionRepository $productionRepository,
        OfferMetadataRepository $offerMetadataRepository,
        PopularityRepository $popularityRepository,
        ContributorRepository $contributorRepository,
        PermissionVoter $permissionVoter,
        ?string $currentUserId,
        ReadRepositoryInterface $labelReadRepository,
        TermRepository $termRepository,
        MediaUrlMapping $mediaUrlMapping,
        NewsArticleRepository $newsArticleRepository,
        LoggerInterface $logger,
        array $curatorLabels,
        DuplicatePlaceRepository $duplicatePlaceRepository,
        IriGeneratorInterface $eventIriGenerator,
        IriGeneratorInterface $placeIriGenerator,
        array $dummyPlaceIds
    ) {
        $this->eventCache = $eventCache;
        $this->placeCache = $placeCache;
        $this->organizerCache = $organizerCache;
        $this->connection = $connection;
        $this->productionRepository = $productionRepository;
        $this->offerMetadataRepository = $offerMetadataRepository;
        $this->popularityRepository = $popularityRepository;
        $this->contributorRepository = $contributorRepository;
        $this->permissionVoter = $permissionVoter;
        $this->currentUserId = $currentUserId;
        $this->labelReadRepository = $labelReadRepository;
        $this->termRepository = $termRepository;
        $this->mediaUrlMapping = $mediaUrlMapping;
        $this->newsArticleRepository = $newsArticleRepository;
        $this->logger = $logger;
        $this->curatorLabels = $curatorLabels;
        $this->duplicatePlaceRepository = $duplicatePlaceRepository;
        $this->eventIriGenerator = $eventIriGenerator;
        $this->placeIriGenerator = $placeIriGenerator;
        $this->dummyPlaceIds = $dummyPlaceIds;
    }

    public function fetch(OfferType $offerType, string $offerId, bool $includeMetadata): JsonDocument
    {
        if ($offerType->sameAs(OfferType::event())) {
            return $this->getEventReader()->fetch($offerId, $includeMetadata);
        }

        return $this->getPlaceReader()->fetch($offerId, $includeMetadata);
    }

    private function getEventReader(): DocumentRepository
    {
        if ($this->eventReader !== null) {
            return $this->eventReader;
        }

        $repository = EmbeddingRelatedResourcesOfferRepository::createForEventRepository(
            $this->eventCache,
            $this->getPlaceReader(),
            $this->organizerCache
        );

        $repository = new ProductionEnrichedEventRepository(
            $repository,
            $this->productionRepository,
            $this->eventIriGenerator
        );

        $repository = $this->wrapCommonDecorators($repository, OfferType::event(), $this->eventIriGenerator);

        $this->eventReader = new CuratorEnrichedOfferRepository(
            $repository,
            $this->newsArticleRepository,
            $this->logger,
            $this->curatorLabels
        );

        return $this->eventReader;
    }

    private function getPlaceReader(): DocumentRepository
    {
        if ($this->placeReader !== null) {
            return $this->placeReader;
        }

        $repository = new DummyPlaceProjectionEnricher($this->placeCache, $this->dummyPlaceIds);

        $repository = EmbeddingRelatedResourcesOfferRepository::createForPlaceRepository(
            $repository,
            $this->organizerCache
        );

        $repository = new NilLocationEnrichedPlaceRepository(
            new NilLocationNormalizer($this->placeIriGenerator),
            $repository
        );

        $repository = $this->wrapCommonDecorators($repository, OfferType::place(), $this->placeIriGenerator);

        // Events additionally get RecommendationForEnrichedOfferRepository and
        // CuratorEnrichedOfferRepository (see getEventReader()) - places don't.
        $this->placeReader = $repository;

        return $this->placeReader;
    }

    /**
     * The part of the chain that's identical (in shape) between events and places:
     * metadata -> popularity -> contributor -> [duplicate-places OR recommendations] -> polyfill -> term label -> media url.
     */
    private function wrapCommonDecorators(
        DocumentRepository $repository,
        OfferType $offerType,
        IriGeneratorInterface $iriGenerator
    ): DocumentRepository {
        $repository = new OfferMetadataEnrichedOfferRepository(
            $this->offerMetadataRepository,
            $repository
        );

        $repository = new PopularityEnrichedOfferRepository(
            $this->popularityRepository,
            $repository
        );

        $repository = new ContributorEnrichedRepository(
            $this->contributorRepository,
            $repository,
            $this->permissionVoter,
            $this->currentUserId
        );

        if ($offerType->sameAs(OfferType::place())) {
            $repository = new DuplicatePlacesEnrichedPlaceRepository(
                $this->duplicatePlaceRepository,
                $iriGenerator,
                $repository
            );
        } else {
            $repository = new RecommendationForEnrichedOfferRepository(
                new DBALRecommendationsRepository($this->connection),
                $iriGenerator,
                $repository
            );
        }

        $repository = new PropertyPolyfillOfferRepository(
            $repository,
            $this->labelReadRepository,
            $offerType
        );

        $repository = new TermLabelOfferRepositoryDecorator($repository, $this->termRepository);

        return new MediaUrlOfferRepositoryDecorator($repository, $this->mediaUrlMapping);
    }
}
