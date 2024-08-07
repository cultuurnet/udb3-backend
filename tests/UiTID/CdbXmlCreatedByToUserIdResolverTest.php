<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTID;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CdbXmlCreatedByToUserIdResolverTest extends TestCase
{
    /**
     * @var UserIdentityResolver&MockObject
     */
    private $users;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    private CdbXmlCreatedByToUserIdResolver $resolver;

    public function setUp(): void
    {
        $this->users = $this->createMock(UserIdentityResolver::class);
        $this->resolver = new CdbXmlCreatedByToUserIdResolver($this->users);

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->resolver->setLogger($this->logger);
    }

    /**
     * @test
     */
    public function it_first_tries_to_resolve_created_by_as_a_uuid(): void
    {
        $createdBy = '4eaf3516-342f-4c28-a2ce-80a0c6332f11';

        $actualUserId = $this->resolver->resolveCreatedByToUserId($createdBy);

        $this->assertEquals($createdBy, $actualUserId);
    }

    /**
     * @test
     */
    public function it_logs_when_created_by_is_not_a_uuid(): void
    {
        $createdBy = 'acf1c0f-30d-3ef-e7b-cd4b7676206';

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'The provided createdByIdentifier acf1c0f-30d-3ef-e7b-cd4b7676206 is not a UUID.',
                [
                    'exception' => new \InvalidArgumentException(
                        $createdBy . ' is not a valid uuid.'
                    ),
                ]
            );

        $actualUserId = $this->resolver->resolveCreatedByToUserId($createdBy);

        $this->assertNull($actualUserId);
    }

    /**
     * @test
     */
    public function it_then_tries_to_resolve_createdby_as_a_non_uuid_id(): void
    {
        $createdBy = 'auth0|c4ff15aa-a8d2-4952-b9eb-329d625b0d02';
        $userId = 'auth0|c4ff15aa-a8d2-4952-b9eb-329d625b0d02';

        $user = new UserIdentityDetails(
            $userId,
            'johndoe',
            'johndoe@example.com'
        );

        $this->users->expects($this->once())
            ->method('getUserById')
            ->with($createdBy)
            ->willReturn($user);


        $actualUserId = $this->resolver->resolveCreatedByToUserId($createdBy);

        $this->assertEquals($userId, $actualUserId);
    }

    /**
     * @test
     */
    public function it_then_tries_to_resolve_createdby_as_an_email_address(): void
    {
        $createdBy = 'johndoe@example.com';

        $userId = 'abc';

        $user = new UserIdentityDetails(
            $userId,
            'johndoe',
            'johndoe@example.com'
        );

        $this->users->expects($this->once())
            ->method('getUserById')
            ->with($createdBy)
            ->willReturn(null);

        $this->users->expects($this->once())
            ->method('getUserByEmail')
            ->with(new EmailAddress('johndoe@example.com'))
            ->willReturn($user);


        $actualUserId = $this->resolver->resolveCreatedByToUserId($createdBy);

        $this->assertEquals($userId, $actualUserId);
    }

    /**
     * @test
     */
    public function it_falls_back_to_resolving_createdby_as_a_nick_name_if_createdby_is_not_an_email_address(): void
    {
        $createdBy = 'johndoe';

        $userId = 'abc';

        $user = new UserIdentityDetails(
            $userId,
            'johndoe',
            'johndoe@example.com'
        );

        $this->users->expects($this->once())
            ->method('getUserById')
            ->with($createdBy)
            ->willReturn(null);

        $this->users->expects($this->never())
            ->method('getUserByEmail');

        $this->users->expects($this->once())
            ->method('getUserByNick')
            ->with($createdBy)
            ->willReturn($user);

        $actualUserId = $this->resolver->resolveCreatedByToUserId($createdBy);

        $this->assertEquals($userId, $actualUserId);
    }

    /**
     * @test
     */
    public function it_returns_null_when_user_id_not_resolved(): void
    {
        $createdBy = 'johndoe';

        $this->users->expects($this->once())
            ->method('getUserById')
            ->with($createdBy)
            ->willReturn(null);

        $this->users->expects($this->once())
            ->method('getUserByNick')
            ->willReturn(null);

        $userId = $this->resolver->resolveCreatedByToUserId($createdBy);

        $this->assertNull($userId);
    }
}
