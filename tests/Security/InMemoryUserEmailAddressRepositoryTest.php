<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebTokenFactory;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use PHPUnit\Framework\TestCase;

final class InMemoryUserEmailAddressRepositoryTest extends TestCase
{
    private UserEmailAddressRepository $userEmailAddressRepository;

    protected function setUp(): void
    {
    }

    /**
     * @test
     */
    public function it_can_make_get_an_email_from_a_user_id(): void
    {
        $existingUserId = 'e7497a8b-dd4a-44dc-bfc4-ac405d66ec39';
        $existingUserMail = 'somebody@mail.com';

        $this->userEmailAddressRepository = new InMemoryUserEmailAddressRepository(
            JsonWebTokenFactory::createWithClaims(
                [
                    'https://publiq.be/uitidv1id' => $existingUserId,
                    'https://publiq.be/email' => $existingUserMail,
                ]
            )
        );

        $mappedEmail = new EmailAddress($existingUserMail);
        $this->assertEquals($mappedEmail, $this->userEmailAddressRepository->getEmailForUserId($existingUserId));
    }

    /**
     * @test
     */
    public function it_returns_null_if_no_mapping_is_found(): void
    {
        $this->userEmailAddressRepository = new InMemoryUserEmailAddressRepository(
            JsonWebTokenFactory::createWithClaims([])
        );

        $this->assertNull($this->userEmailAddressRepository->getEmailForUserId('c25a053a-bab1-428b-90fc-c30f9cb6c323'));
    }
}
