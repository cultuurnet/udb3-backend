<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\UserEmailAddressRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ContributorVoterTest extends TestCase
{
    private ContributorVoter $contributorVoter;

    /**
     * @var UserEmailAddressRepository&MockObject
     */
    private $userEmailAddressRepository;

    /**
     * @var ContributorRepository&MockObject
     */
    private $contributorRepository;

    private string $userId;

    private string $itemId;

    private EmailAddress $email;

    public function setup(): void
    {
        $this->userEmailAddressRepository = $this->createMock(UserEmailAddressRepository::class);
        $this->contributorRepository = $this->createMock(ContributorRepository::class);
        $this->contributorVoter = new ContributorVoter(
            $this->userEmailAddressRepository,
            $this->contributorRepository
        );
        $this->userId = 'bd04e0a4-4f3c-4180-9011-afbc673f58be';
        $this->itemId = '71653d1f-5f6d-4f89-b654-1aed64d5c528';
        $this->email = new EmailAddress('somebody@mail.com');
    }

    /**
     * @test
     */
    public function it_gives_permission_if_a_user_is_a_contributor(): void
    {
        $this->userEmailAddressRepository->expects($this->once())
            ->method('getEmailForUserId')
            ->with($this->userId)
            ->willReturn($this->email);

        $this->contributorRepository->expects($this->once())
            ->method('isContributor')
            ->with(new UUID($this->itemId), $this->email)
            ->willReturn(true);

        $this->assertTrue(
            $this->contributorVoter->isAllowed(
                Permission::aanbodBewerken(),
                $this->itemId,
                $this->userId
            )
        );
    }

    /**
     * @test
     */
    public function it_gives_no_permission_if_a_user_is_not_a_contributor(): void
    {
        $this->userEmailAddressRepository->expects($this->once())
            ->method('getEmailForUserId')
            ->with($this->userId)
            ->willReturn($this->email);

        $this->contributorRepository->expects($this->once())
            ->method('isContributor')
            ->with(new UUID($this->itemId), $this->email)
            ->willReturn(false);

        $this->assertFalse(
            $this->contributorVoter->isAllowed(
                Permission::aanbodBewerken(),
                $this->itemId,
                $this->userId
            )
        );
    }

    /**
     * @test
     */
    public function it_gives_no_permission_if_a_user_has_no_email_mapping(): void
    {
        $this->userEmailAddressRepository->expects($this->once())
            ->method('getEmailForUserId')
            ->with($this->userId)
            ->willReturn(null);

        $this->contributorRepository->expects($this->never())
            ->method('isContributor');

        $this->assertFalse(
            $this->contributorVoter->isAllowed(
                Permission::aanbodBewerken(),
                $this->itemId,
                $this->userId
            )
        );
    }
}
