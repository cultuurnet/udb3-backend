<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use EasyRdf\GraphStore;
use Psr\Container\ContainerInterface;

final class RdfServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [MainLanguageRepository::class];
    }

    public function register(): void
    {
        $this->container->addShared(
            MainLanguageRepository::class,
            fn (): MainLanguageRepository => new CacheMainLanguageRepository(
                $this->container->get('cache')('rdf_main_language')
            )
        );
    }

    public static function createGraphStoreRepository(string $baseUri): GraphStoreRepository
    {
        return new GraphStoreRepository(new GraphStore(rtrim($baseUri, '/')));
    }

    public static function createIriGenerator(ContainerInterface $container, string $resourceType): IriGeneratorInterface
    {
        // Glues the RDF base URI (e.g. https://data.publiq.be for production), the resource type (e.g. "locaties") and
        // the resource id together. For example https://data.publiq.be/locaties/6bdd18f9-f09d-4380-9ee9-bcbd4c9f55ca
        // Note that it makes sure that there are no double slashes by trimming every part of any leading or trailing
        // slashes.
        return new CallableIriGenerator(
            fn (string $resourceId): string =>
                implode(
                    '/',
                    array_map(
                        fn (string $urlPart): string => trim($urlPart, '/'),
                        [
                            $container->get('config')['rdf']['resourceBaseUri'],
                            $resourceType,
                            $resourceId,
                        ]
                    )
                )
        );
    }
}
