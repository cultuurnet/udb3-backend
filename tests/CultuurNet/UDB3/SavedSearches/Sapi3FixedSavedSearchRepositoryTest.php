<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\Auth0ClientAccessToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\Auth0UserAccessToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\JwtProviderV1Token;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\MockTokenStringFactory;
use CultuurNet\UDB3\SavedSearches\Properties\CreatorQueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ValueObject\CreatedByQueryMode;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

class Sapi3FixedSavedSearchRepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_handles_query_mode_uuid(): void
    {
        $token = new JwtProviderV1Token(
            MockTokenStringFactory::createWithClaims(
                [
                    'uid' => 'my_user_id',
                    'nick' => 'foo',
                    'email' => 'mock@example.com',
                ]
            )
        );

        $sapi3FixedSavedSearchRepository = new Sapi3FixedSavedSearchRepository(
            $token,
            CreatedByQueryMode::UUID()
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
    public function it_handles_user_not_found_in_mixed_mode(): void
    {
        $token = new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'sub' => 'mock-client-id@clients',
                    'azp' => 'mock-client-id',
                    'gty' => 'client-credentials',
                ]
            )
        );

        $sapi3FixedSavedSearchRepository = new Sapi3FixedSavedSearchRepository(
            $token,
            CreatedByQueryMode::MIXED()
        );

        $savedSearches = $sapi3FixedSavedSearchRepository->ownedByCurrentUser();

        $this->assertEquals(
            [
                new SavedSearch(
                    new StringLiteral('Door mij ingevoerd'),
                    new CreatorQueryString('mock-client-id@clients')
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
        $token = new JwtProviderV1Token(
            MockTokenStringFactory::createWithClaims(
                [
                    'uid' => 'my_user_id',
                    'nick' => 'foo',
                    'email' => 'jane.doe@anonymous.com',
                ]
            )
        );

        $sapi3FixedSavedSearchRepository = new Sapi3FixedSavedSearchRepository(
            $token,
            CreatedByQueryMode::MIXED()
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
        $token = new JwtProviderV1Token(
            MockTokenStringFactory::createWithClaims(
                [
                    'uid' => 'my_user_id',
                    'nick' => 'foo',
                    'email' => 'jane.doe@anonymous.com',
                ]
            )
        );

        $sapi3FixedSavedSearchRepository = new Sapi3FixedSavedSearchRepository(
            $token,
            CreatedByQueryMode::EMAIL()
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
