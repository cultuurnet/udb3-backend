<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebTokenFactory;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use PHPUnit\Framework\TestCase;

final class InMemoryUserEmailAddressRepositoryTest extends TestCase
{
    private UserEmailAddressRepository $userEmailAddressRepository;

    private string $existingUserId;

    private string $otherUserId;

    private string $existingUserMail;

    protected function setUp(): void
    {
        $this->existingUserId = 'e7497a8b-dd4a-44dc-bfc4-ac405d66ec39';
        $this->otherUserId = 'c25a053a-bab1-428b-90fc-c30f9cb6c323';
        $this->existingUserMail = 'somebody@mail.com';

        $this->userEmailAddressRepository = new InMemoryUserEmailAddressRepository(
            JsonWebTokenFactory::createWithClaims(
                [
                    'https://publiq.be/uitidv1id' => $this->existingUserId,
                    'https://publiq.be/email' => $this->existingUserMail,
                ]
            )
        );
    }

    /**
     * @test
     */
    public function it_can_make_get_an_email_from_a_user_id(): void
    {
        $mappedEmail = new EmailAddress($this->existingUserMail);
        $this->assertEquals($mappedEmail, $this->userEmailAddressRepository->getEmailForUserId($this->existingUserId));
    }

    /**
     * @test
     */
    public function it_returns_null_if_no_mapping_is_found(): void
    {
        $this->assertNull($this->userEmailAddressRepository->getEmailForUserId($this->otherUserId));
    }
}
