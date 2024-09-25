<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Address\CultureFeed\CultureFeedAddressFactory;
use CultuurNet\UDB3\Cdb\CdbXMLToJsonLDLabelImporter;
use CultuurNet\UDB3\Completeness\CompletenessFromWeights;
use CultuurNet\UDB3\Completeness\Weights;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Contributor\ContributorEnrichedRepository;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\ImageNormalizer;
use CultuurNet\UDB3\Organizer\ReadModel\JSONLD\CdbXMLImporter;
use CultuurNet\UDB3\Organizer\ReadModel\JSONLD\EventFactory;
use CultuurNet\UDB3\Organizer\ReadModel\JSONLD\PropertyPolyfillRepository;
use CultuurNet\UDB3\Organizer\ReadModel\JSONLD\OrganizerJsonDocumentLanguageAnalyzer;
use CultuurNet\UDB3\ReadModel\BroadcastingDocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocumentLanguageEnricher;
use CultuurNet\UDB3\User\CurrentUser;

final class OrganizerJSONLDServiceProvider extends AbstractServiceProvider
{
    public const PROJECTOR = 'organizer_jsonld_projector';

    public const JSONLD_PROJECTED_EVENT_FACTORY = 'organizer_jsonld_projected_event_factory';

    protected function getProvidedServiceNames(): array
    {
        return [
            self::PROJECTOR,
            self::JSONLD_PROJECTED_EVENT_FACTORY,
            'organizer_jsonld_repository',
            'organizer_jsonld_cache',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            self::PROJECTOR,
            function () use ($container) {
                return new OrganizerLDProjector(
                    $container->get('organizer_jsonld_repository'),
                    $container->get('organizer_iri_generator'),
                    new JsonDocumentLanguageEnricher(
                        new OrganizerJsonDocumentLanguageAnalyzer()
                    ),
                    new ImageNormalizer(
                        $container->get('media_object_repository'),
                        $container->get('media_object_iri_generator')
                    ),
                    new CdbXMLImporter(
                        $container->get(CdbXMLToJsonLDLabelImporter::class),
                        new CultureFeedAddressFactory()
                    ),
                    new CompletenessFromWeights(
                        Weights::fromConfig($container->get('config')['completeness']['organizer'])
                    )
                );
            }
        );

        $container->addShared(
            self::JSONLD_PROJECTED_EVENT_FACTORY,
            function () use ($container) {
                return new EventFactory(
                    $container->get('organizer_iri_generator')
                );
            }
        );

        $container->addShared(
            'organizer_jsonld_repository',
            function () use ($container) {
                $repository = new CacheDocumentRepository($container->get('organizer_jsonld_cache'));
                $repository = new PropertyPolyfillRepository($repository, $container->get(LabelServiceProvider::JSON_READ_REPOSITORY));

                $repository = new ContributorEnrichedRepository(
                    $container->get(ContributorRepository::class),
                    $repository,
                    $container->get('organizer_permission_voter'),
                    $container->get(CurrentUser::class)->getId()
                );

                return new BroadcastingDocumentRepositoryDecorator(
                    $repository,
                    $container->get(EventBus::class),
                    $container->get(self::JSONLD_PROJECTED_EVENT_FACTORY)
                );
            }
        );

        $container->addShared(
            'organizer_jsonld_cache',
            function () use ($container) {
                return $container->get('cache')('organizer_jsonld');
            }
        );
    }
}
