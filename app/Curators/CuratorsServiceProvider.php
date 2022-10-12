<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Http\Curators\CreateNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\DeleteNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\GetNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\GetNewsArticlesRequestHandler;
use CultuurNet\UDB3\Http\Curators\UpdateNewsArticleRequestHandler;

final class CuratorsServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            NewsArticleRepository::class,
            GetNewsArticleRequestHandler::class,
            GetNewsArticlesRequestHandler::class,
            CreateNewsArticleRequestHandler::class,
            UpdateNewsArticleRequestHandler::class,
            DeleteNewsArticleRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            NewsArticleRepository::class,
            function () use ($container): NewsArticleRepository {
                return new DBALNewsArticleRepository($container->get('dbal_connection'));
            }
        );

        $container->addShared(
            GetNewsArticleRequestHandler::class,
            function () use ($container): GetNewsArticleRequestHandler {
                return new GetNewsArticleRequestHandler($container->get(NewsArticleRepository::class));
            }
        );

        $container->addShared(
            GetNewsArticlesRequestHandler::class,
            function () use ($container): GetNewsArticlesRequestHandler {
                return new GetNewsArticlesRequestHandler($container->get(NewsArticleRepository::class));
            }
        );

        $container->addShared(
            CreateNewsArticleRequestHandler::class,
            function () use ($container): CreateNewsArticleRequestHandler {
                return new CreateNewsArticleRequestHandler(
                    $container->get(NewsArticleRepository::class),
                    new Version4Generator(),
                );
            }
        );

        $container->addShared(
            UpdateNewsArticleRequestHandler::class,
            function () use ($container): UpdateNewsArticleRequestHandler {
                return new UpdateNewsArticleRequestHandler($container->get(NewsArticleRepository::class));
            }
        );

        $container->addShared(
            DeleteNewsArticleRequestHandler::class,
            function () use ($container): DeleteNewsArticleRequestHandler {
                return new DeleteNewsArticleRequestHandler($container->get(NewsArticleRepository::class));
            }
        );
    }
}
