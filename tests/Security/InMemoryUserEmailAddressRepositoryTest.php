<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use PHPUnit\Framework\TestCase;

final class InMemoryUserEmailAddressRepositoryTest extends TestCase
{
    private UserEmailAddressRepository $userEmailAddressRepository;

    protected function setUp(): void
    {
        $this->userEmailAddressRepository = new InMemoryUserEmailAddressRepository();
    }

    /**
     * @test
     */
    public function it_can_make_a_user_email_mapping(): void
    {
        $mappedEmail = new EmailAddress('somebody@mail.com');
        $userId = 'e7497a8b-dd4a-44dc-bfc4-ac405d66ec39';
        $this->userEmailAddressRepository::addUserEmail($userId, $mappedEmail);

        $this->assertEquals($mappedEmail, $this->userEmailAddressRepository->getEmailForUserId($userId));
    }

    /**
     * @test
     */
    public function it_returns_null_if_no_mapping_is_found(): void
    {
        $this->assertNull($this->userEmailAddressRepository->getEmailForUserId('nobody@mail.com'));
    }
}
