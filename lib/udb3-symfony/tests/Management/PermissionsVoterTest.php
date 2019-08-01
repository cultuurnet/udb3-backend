<?php

namespace CultuurNet\UDB3\Symfony\Management;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PermissionsVoterTest extends PHPUnit_Framework_TestCase
{
    use TokenMockingTrait;

    /**
     * @var UserPermissionsVoter
     */
    private $voter;

    /**
     * @var string
     */
    private $kingId;

    /**
     * @var string
     */
    private $peasantId;

    public function setUp()
    {
        $this->kingId = '613158E3-F711-4AD1-9528-EDE00505A34A';
        $this->peasantId = '0813D61B-C1BB-4F71-A7B0-27F6757B06CB';

        $permissionsList = [
            'allow_all' => [
                $this->kingId
            ]
        ];

        $this->voter = new PermissionsVoter($permissionsList);
    }

    /**
     * @test
     */
    public function it_should_give_all_permissions_to_a_white_listed_user()
    {
        $kingToken = $this->createMockToken($this->kingId);

        $request = $this->createMock(Request::class);
        $access = $this->voter->vote($kingToken, $request, Permission::getConstants());

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $access);
    }

    /**
     * @test
     */
    public function it_should_not_give_any_permissions_to_an_unlisted_user()
    {
        $peasantToken = $this->createMockToken($this->peasantId);

        $request = $this->createMock(Request::class);
        $access = $this->voter->vote($peasantToken, $request, Permission::getConstants());

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $access);
    }
}
