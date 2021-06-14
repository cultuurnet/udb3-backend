<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class UserIdentificationTest extends TestCase
{
    /**
     * @var string
     */
    private $userId;

    /**
     * @var string[][]
     */
    private $permissionList;

    /**
     * @var UserIdentification
     */
    private $userIdentification;

    protected function setUp()
    {
        $this->userId = 'godUserId';

        $this->permissionList['allow_all'] = ['godUserId', 'otherGodUserId'];

        $this->userIdentification = new UserIdentification(
            $this->userId,
            $this->permissionList
        );
    }

    /**
     * @test
     */
    public function it_can_determine_if_a_user_is_a_god_user(): void
    {
        $this->assertTrue($this->userIdentification->isGodUser());
    }

    /**
     * @test
     */
    public function it_can_determine_if_a_user_is_not_a_god_user(): void
    {
        $cultureFeedUserIdentification = new UserIdentification(
            'normalUserId',
            $this->permissionList
        );

        $this->assertFalse($cultureFeedUserIdentification->isGodUser());
    }

    /**
     * @test
     */
    public function it_returns_the_id_of_a_user(): void
    {
        $this->assertEquals(
            new StringLiteral('godUserId'),
            $this->userIdentification->getId()
        );
    }
}
