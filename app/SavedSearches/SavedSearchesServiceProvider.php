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
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchesOwnedByCurrentUser;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchReadRepository;
use CultuurNet\UDB3\SavedSearches\ValueObject\CreatedByQueryMode;
use CultuurNet\UDB3\User\CurrentUser;
use CultuurNet\UDB3\User\UserIdentityResolver;
use League\Container\DefinitionContainerInterface;

final class SavedSearchesServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'udb3_saved_searches_repo_sapi3',
            SavedSearchesOwnedByCurrentUser::class,
            'saved_searches_command_handler',
            ReadSavedSearchesRequestHandler::class,
            CreateSavedSearchRequestHandler::class,
            DeleteSavedSearchRequestHandler::class,
            UpdateSavedSearchRequestHandler::class,
            SavedSearchReadRepository::class,
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
                    $container->get(CurrentUser::class)->getId()
                );
            }
        );

        $container->addShared(
            SavedSearchesOwnedByCurrentUser::class,
            function () use ($container) {
                return new CombinedSavedSearchRepository(
                    new Sapi3FixedSavedSearchRepository(
                        $container->get(JsonWebToken::class),
                        $container->get(UserIdentityResolver::class),
                        $this->getCreatedByQueryMode($container)
                    ),
                    $container->get('udb3_saved_searches_repo_sapi3'),
                    new OwnershipSavedSearchRepository(
                        $container->get(JsonWebToken::class),
                        $container->get('organizer_jsonld_repository'),
                        $container->get(OwnershipSearchRepository::class)
                    ),
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
                    $container->get(SavedSearchesOwnedByCurrentUser::class)
                );
            }
        );

        $container->addShared(
            CreateSavedSearchRequestHandler::class,
            function () use ($container) {
                return new CreateSavedSearchRequestHandler(
                    $container->get(CurrentUser::class)->getId(),
                    $container->get('event_command_bus'),
                    new Version4Generator(),
                );
            }
        );

        $container->addShared(
            UpdateSavedSearchRequestHandler::class,
            function () use ($container) {
                return new UpdateSavedSearchRequestHandler(
                    $container->get(CurrentUser::class)->getId() ?? '',
                    $container->get('event_command_bus'),
                    $container->get(SavedSearchReadRepository::class)
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

        $container->addShared(
            SavedSearchReadRepository::class,
            function () use ($container) {
                return new SavedSearchReadRepository(
                    $container->get('dbal_connection'),
                    'saved_searches_sapi3'
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
