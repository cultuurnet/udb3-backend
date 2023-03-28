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

    public static function createIriGenerator(string $baseUri): IriGeneratorInterface
    {
        return new CallableIriGenerator(
            fn (string $resourceId): string => rtrim($baseUri, '/') . '/' . $resourceId
        );
    }
}
