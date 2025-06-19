<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Keycloak;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CachedUserIdentityResolverTest extends TestCase
{
    private UserIdentityResolver&MockObject $fallbackUserIdentityResolver;

    private CachedUserIdentityResolver $cachedUserIdentityResolver;

    private UserIdentityDetails $uncachedUserIdentityDetails;

    private UserIdentityDetails $cachedUserIdentityDetails;

    protected function setUp(): void
    {
        $this->fallbackUserIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $cache = new ArrayAdapter();

        $this->cachedUserIdentityResolver = new CachedUserIdentityResolver(
            $this->fallbackUserIdentityResolver,
            $cache
        );

        $this->uncachedUserIdentityDetails = new UserIdentityDetails(
            '9f3e9228-4eca-40ad-982f-4420bf4bbf09',
            'John Doe',
            'john@anonymous.com'
        );

        $this->cachedUserIdentityDetails = new UserIdentityDetails(
            'd515f818-fe13-497d-abfa-c99be9a8ffae',
            'Jane Doe',
            'jane@anonymous.com'
        );

        $cache->get(
            'd515f818-fe13-497d-abfa-c99be9a8ffae_user_id',
            function () {
                return $this->cachedUserIdentityDetails->jsonSerialize();
            }
        );

        $cache->get(
            'jane_anonymous.com_email',
            function () {
                return $this->cachedUserIdentityDetails->jsonSerialize();
            }
        );

        $cache->get(
            'Jane Doe_nick',
            function () {
                return $this->cachedUserIdentityDetails->jsonSerialize();
            }
        );
    }

    /**
     * @test
     */
    public function it_can_get_an_uncached_user_by_id(): void
    {
        $this->fallbackUserIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with('9f3e9228-4eca-40ad-982f-4420bf4bbf09')
            ->willReturn($this->uncachedUserIdentityDetails);

        $this->assertEquals(
            $this->uncachedUserIdentityDetails,
            $this->cachedUserIdentityResolver->getUserById('9f3e9228-4eca-40ad-982f-4420bf4bbf09')
        );
    }

    /**
     * @test
     */
    public function it_can_get_a_cached_user_by_id(): void
    {
        $this->fallbackUserIdentityResolver->expects($this->never())
            ->method('getUserById');

        $this->assertEquals(
            $this->cachedUserIdentityDetails,
            $this->cachedUserIdentityResolver->getUserById('d515f818-fe13-497d-abfa-c99be9a8ffae')
        );
    }

    /**
     * @test
     */
    public function it_does_not_cache_when_id_is_null(): void
    {
        $this->fallbackUserIdentityResolver->expects($this->exactly(2))
            ->method('getUserById')
            ->with('null')
            ->willReturn(null);

        $this->cachedUserIdentityResolver->getUserById('null');
        $this->cachedUserIdentityResolver->getUserById('null');
    }

    /**
     * @test
     */
    public function it_can_get_an_uncached_user_by_email(): void
    {
        $this->fallbackUserIdentityResolver->expects($this->once())
            ->method('getUserByEmail')
            ->with(new EmailAddress('john@anonymous.com'))
            ->willReturn($this->uncachedUserIdentityDetails);

        $this->assertEquals(
            $this->uncachedUserIdentityDetails,
            $this->cachedUserIdentityResolver->getUserByEmail(new EmailAddress('john@anonymous.com'))
        );
    }

    /**
     * @test
     */
    public function it_can_get_a_cached_user_by_email(): void
    {
        $this->fallbackUserIdentityResolver->expects($this->never())
            ->method('getUserByEmail');

        $this->assertEquals(
            $this->cachedUserIdentityDetails,
            $this->cachedUserIdentityResolver->getUserByEmail(new EmailAddress('jane@anonymous.com'))
        );
    }

    /**
     * @test
     */
    public function it_does_not_cache_when_email_is_null(): void
    {
        $nonExistingUser = new EmailAddress('null@null.com');
        $this->fallbackUserIdentityResolver->expects($this->exactly(2))
            ->method('getUserByEmail')
            ->with($nonExistingUser)
            ->willReturn(null);

        $this->cachedUserIdentityResolver->getUserByEmail($nonExistingUser);
        $this->cachedUserIdentityResolver->getUserByEmail($nonExistingUser);
    }

    /**
     * @test
     */
    public function it_can_get_an_uncached_user_by_nick(): void
    {
        $this->fallbackUserIdentityResolver->expects($this->once())
            ->method('getUserByNick')
            ->with('John Doe')
            ->willReturn($this->uncachedUserIdentityDetails);

        $this->assertEquals(
            $this->uncachedUserIdentityDetails,
            $this->cachedUserIdentityResolver->getUserByNick('John Doe')
        );
    }

    /**
     * @test
     */
    public function it_can_get_a_cached_user_by_nick(): void
    {
        $this->fallbackUserIdentityResolver->expects($this->never())
            ->method('getUserByNick');

        $this->assertEquals(
            $this->cachedUserIdentityDetails,
            $this->cachedUserIdentityResolver->getUserByNick('Jane Doe')
        );
    }

    /**
     * @test
     */
    public function it_does_not_cache_when_nick_is_null(): void
    {
        $this->fallbackUserIdentityResolver->expects($this->exactly(2))
            ->method('getUserByNick')
            ->with('null')
            ->willReturn(null);

        $this->cachedUserIdentityResolver->getUserByNick('null');
        $this->cachedUserIdentityResolver->getUserByNick('null');
    }
}
