<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebTokenFactory;
use CultuurNet\UDB3\SavedSearches\Properties\CreatorQueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ValueObject\CreatedByQueryMode;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

class Sapi3FixedSavedSearchRepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_handles_query_mode_uuid(): void
    {
        $token = JsonWebTokenFactory::createWithClaims(
            [
                'uid' => 'my_user_id',
                'nick' => 'my_name',
                'email' => 'jane.doe@anonymous.com',
            ]
        );

        $userIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $userIdentityResolver->expects($this->never())
            ->method('getUserById');

        $sapi3FixedSavedSearchRepository = new Sapi3FixedSavedSearchRepository(
            $token,
            $userIdentityResolver,
            CreatedByQueryMode::uuid()
        );

        $savedSearches = $sapi3FixedSavedSearchRepository->ownedByCurrentUser();

        $this->assertEquals(
            [
                new SavedSearch(
                    new StringLiteral('Door mij ingevoerd'),
                    new CreatorQueryString('my_user_id')
                ),
            ],
            $savedSearches
        );
    }

    /**
     * @test
     */
    public function it_handles_user_not_found(): void
    {
        $token = JsonWebTokenFactory::createWithClaims(
            [
                'sub' => 'my_user_id',
                'azp' => 'mock-client-id',
            ]
        );

        $userIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with(new StringLiteral('my_user_id'))
            ->willReturn(null);

        $sapi3FixedSavedSearchRepository = new Sapi3FixedSavedSearchRepository(
            $token,
            $userIdentityResolver,
            CreatedByQueryMode::mixed()
        );

        $savedSearches = $sapi3FixedSavedSearchRepository->ownedByCurrentUser();

        $this->assertEquals(
            [
                new SavedSearch(
                    new StringLiteral('Door mij ingevoerd'),
                    new CreatorQueryString('my_user_id')
                ),
            ],
            $savedSearches
        );
    }

    /**
     * @test
     */
    public function it_handles_mixed_mode(): void
    {
        $token = JsonWebTokenFactory::createWithClaims(
            [
                'uid' => 'my_user_id',
                'nick' => 'my_name',
                'email' => 'jane.doe@anonymous.com',
            ]
        );

        $userIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $userIdentityResolver->expects($this->never())
            ->method('getUserById');

        $sapi3FixedSavedSearchRepository = new Sapi3FixedSavedSearchRepository(
            $token,
            $userIdentityResolver,
            CreatedByQueryMode::mixed()
        );

        $savedSearches = $sapi3FixedSavedSearchRepository->ownedByCurrentUser();

        $this->assertEquals(
            [
                new SavedSearch(
                    new StringLiteral('Door mij ingevoerd'),
                    new CreatorQueryString('jane.doe@anonymous.com', 'my_user_id')
                ),
            ],
            $savedSearches
        );
    }

    /**
     * @test
     */
    public function it_handles_email_mode(): void
    {
        $token = JsonWebTokenFactory::createWithClaims(
            [
                'uid' => 'my_user_id',
                'nick' => 'my_name',
                'email' => 'jane.doe@anonymous.com',
            ]
        );

        $userIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $userIdentityResolver->expects($this->never())
            ->method('getUserById');

        $sapi3FixedSavedSearchRepository = new Sapi3FixedSavedSearchRepository(
            $token,
            $userIdentityResolver,
            CreatedByQueryMode::email()
        );

        $savedSearches = $sapi3FixedSavedSearchRepository->ownedByCurrentUser();

        $this->assertEquals(
            [
                new SavedSearch(
                    new StringLiteral('Door mij ingevoerd'),
                    new CreatorQueryString('jane.doe@anonymous.com')
                ),
            ],
            $savedSearches
        );
    }
}
