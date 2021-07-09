<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

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
        $userIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $userIdentityResolver->expects($this->never())
            ->method('getUserById');

        $sapi3FixedSavedSearchRepository = new Sapi3FixedSavedSearchRepository(
            'my_user_id',
            $userIdentityResolver,
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
    public function it_handles_user_not_found(): void
    {
        $userIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with(new StringLiteral('my_user_id'))
            ->willReturn(null);

        $sapi3FixedSavedSearchRepository = new Sapi3FixedSavedSearchRepository(
            'my_user_id',
            $userIdentityResolver,
            CreatedByQueryMode::MIXED()
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
        $user = new UserIdentityDetails(
            new StringLiteral('my_user_id'),
            new StringLiteral('my_name'),
            new EmailAddress('jane.doe@anonymous.com')
        );

        $userIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with(new StringLiteral('my_user_id'))
            ->willReturn($user);

        $sapi3FixedSavedSearchRepository = new Sapi3FixedSavedSearchRepository(
            'my_user_id',
            $userIdentityResolver,
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
}
