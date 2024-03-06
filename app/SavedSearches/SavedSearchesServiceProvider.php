<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\Http\SavedSearches\CreateSavedSearchRequestHandler;
use CultuurNet\UDB3\Http\SavedSearches\DeleteSavedSearchRequestHandler;
use CultuurNet\UDB3\Http\SavedSearches\ReadSavedSearchesRequestHandler;
use CultuurNet\UDB3\Http\SavedSearches\UpdateSavedSearchRequestHandler;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use CultuurNet\UDB3\SavedSearches\ValueObject\CreatedByQueryMode;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use CultuurNet\UDB3\User\CurrentUser;
use League\Container\DefinitionContainerInterface;

final class SavedSearchesServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'udb3_saved_searches_repo_sapi3',
            SavedSearchRepositoryInterface::class,
            'saved_searches_command_handler',
            ReadSavedSearchesRequestHandler::class,
            CreateSavedSearchRequestHandler::class,
            DeleteSavedSearchRequestHandler::class,
            UpdateSavedSearchRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'udb3_saved_searches_repo_sapi3',
            function () use ($container) {
                return new UDB3SavedSearchRepository(
                    $container->get('dbal_connection'),
                    'saved_searches_sapi3',
                    new Version4Generator(),
                    $container->get(CurrentUser::class)->getId()
                );
            }
        );

        $container->addShared(
            SavedSearchRepositoryInterface::class,
            function () use ($container) {
                return new CombinedSavedSearchRepository(
                    new Sapi3FixedSavedSearchRepository(
                        $container->get(JsonWebToken::class),
                        $container->get(Auth0UserIdentityResolver::class),
                        $this->getCreatedByQueryMode($container)
                    ),
                    $container->get('udb3_saved_searches_repo_sapi3')
                );
            }
        );

        $container->addShared(
            'saved_searches_command_handler',
            function () use ($container) {
                return new UDB3SavedSearchesCommandHandler(
                    $container->get('udb3_saved_searches_repo_sapi3')
                );
            }
        );

        $container->addShared(
            ReadSavedSearchesRequestHandler::class,
            function () use ($container) {
                return new ReadSavedSearchesRequestHandler(
                    $container->get(SavedSearchRepositoryInterface::class)
                );
            }
        );

        $container->addShared(
            CreateSavedSearchRequestHandler::class,
            function () use ($container) {
                return new CreateSavedSearchRequestHandler(
                    $container->get(CurrentUser::class)->getId() ?? '',
                    $container->get('event_command_bus')
                );
            }
        );

        $container->addShared(
            UpdateSavedSearchRequestHandler::class,
            function () use ($container) {
                return new UpdateSavedSearchRequestHandler(
                    $container->get(CurrentUser::class)->getId() ?? '',
                    $container->get('event_command_bus')
                );
            }
        );

        $container->addShared(
            DeleteSavedSearchRequestHandler::class,
            function () use ($container) {
                return new DeleteSavedSearchRequestHandler(
                    $container->get(CurrentUser::class)->getId(),
                    $container->get('event_command_bus')
                );
            }
        );
    }

    private function getCreatedByQueryMode(DefinitionContainerInterface $container): CreatedByQueryMode
    {
        $createdByQueryMode = CreatedByQueryMode::uuid();
        if (!empty($container->get('config')['created_by_query_mode'])) {
            $createdByQueryMode = new CreatedByQueryMode(
                $container->get('config')['created_by_query_mode']
            );
        }

        return $createdByQueryMode;
    }
}
