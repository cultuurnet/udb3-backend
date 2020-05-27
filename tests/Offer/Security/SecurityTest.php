<?php

namespace CultuurNet\UDB3\Offer\Security;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Offer\ReadModel\Permission\PermissionQueryInterface;
use CultuurNet\UDB3\Offer\Security\Permission\CompositeVoter;
use CultuurNet\UDB3\Offer\Security\Permission\GodUserVoter;
use CultuurNet\UDB3\Offer\Security\Permission\OwnerVoter;
use CultuurNet\UDB3\Offer\Security\Permission\RoleConstraintVoter;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\UserIdentificationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class SecurityTest extends TestCase
{
    /**
     * @var UserIdentificationInterface|MockObject
     */
    private $userIdentification;

    /**
     * @var string
     */
    private $godUserId;

    /**
     * @var string
     */
    private $ownerUserId;

    /**
     * @var string
     */
    private $roleUserId;

    /**
     * @var string
     */
    private $notAllowedUserId;

    /**
     * @var PermissionQueryInterface|MockObject
     */
    private $permissionRepository;

    /**
     * @var UserPermissionMatcherInterface|MockObject
     */
    private $userPermissionMatcher;

    /**
     * @var CompositeVoter
     */
    private $permissionVoter;

    /**
     * @var Security
     */
    private $security;

    protected function setUp()
    {
        $this->userIdentification = $this->createMock(
            UserIdentificationInterface::class
        );

        $this->godUserId = 'bb0bf2b3-49ba-4f2a-a1e4-ce7ec93a5ea0';
        $this->ownerUserId = '9cb28282-30a1-4afc-aa23-fc825c7d8ac3';
        $this->roleUserId = 'a8ae681a-3945-4fce-9ec1-aee09e8d0234';
        $this->notAllowedUserId = '4b7d9a94-e4ff-4840-92b2-2f3f37ee99d4';

        $this->permissionRepository = $this->createMock(
            PermissionQueryInterface::class
        );

        $this->userPermissionMatcher = $this->createMock(
            UserPermissionMatcherInterface::class
        );

        $this->permissionVoter = new CompositeVoter(
            new GodUserVoter([$this->godUserId]),
            new OwnerVoter($this->permissionRepository),
            new RoleConstraintVoter($this->userPermissionMatcher)
        );

        $this->security = new Security(
            $this->userIdentification,
            $this->permissionVoter
        );
    }

    /**
     * @test
     */
    public function it_returns_false_for_user_with_missing_id()
    {
        $this->mockGetId();

        $offerId = new StringLiteral('offerId');
        $allowsUpdate = $this->security->allowsUpdateWithCdbXml($offerId);

        $this->assertFalse($allowsUpdate);
    }

    /**
     * @test
     */
    public function it_returns_true_for_god_user()
    {
        $this->mockGetId(new StringLiteral($this->godUserId));

        $offerId = new StringLiteral('offerId');
        $allowsUpdate = $this->security->allowsUpdateWithCdbXml($offerId);

        $this->assertTrue($allowsUpdate);
    }

    /**
     * @test
     */
    public function it_returns_true_for_own_offer()
    {
        $this->mockGetId(new StringLiteral($this->ownerUserId));

        $this->mockGetEditableOffers(['offerId', 'otherOfferId']);

        $offerId = new StringLiteral('offerId');
        $allowsUpdate = $this->security->allowsUpdateWithCdbXml($offerId);

        $this->assertTrue($allowsUpdate);
    }

    /**
     * @test
     */
    public function it_returns_false_when_not_own_offer_and_not_matching_user_permission()
    {
        $this->mockGetId(new StringLiteral('userId'));

        $this->mockGetEditableOffers(['otherOfferId', 'andOtherOfferId']);

        $this->mockItMatchesOffer(false);

        $offerId = new StringLiteral('offerId');
        $allowsUpdate = $this->security->allowsUpdateWithCdbXml($offerId);

        $this->assertFalse($allowsUpdate);
    }

    /**
     * @test
     */
    public function it_returns_true_when_not_own_offer_but_matching_user_permission()
    {
        $this->mockGetId(new StringLiteral($this->roleUserId));

        $this->mockGetEditableOffers(['otherOfferId', 'andOtherOfferId']);

        $this->mockItMatchesOffer(true);

        $offerId = new StringLiteral('offerId');
        $allowsUpdate = $this->security->allowsUpdateWithCdbXml($offerId);

        $this->assertTrue($allowsUpdate);
    }

    /**
     * @test
     */
    public function it_also_handles_authorizable_command()
    {
        $this->mockGetId(new StringLiteral($this->godUserId));

        /** @var AuthorizableCommandInterface|MockObject $authorizableCommand */
        $authorizableCommand = $this->createMock(AuthorizableCommandInterface::class);

        $authorizableCommand->method('getItemId')
            ->willReturn('offerId');

        $authorizableCommand->method('getPermission')
            ->willReturn(Permission::AANBOD_BEWERKEN());

        $allowsUpdate = $this->security->isAuthorized($authorizableCommand);

        $this->assertTrue($allowsUpdate);
    }

    /**
     * @param StringLiteral|null $userId
     */
    private function mockGetId(StringLiteral $userId = null)
    {
        $this->userIdentification->method('getId')
            ->willReturn($userId);
    }

    /**
     * @param string[] $editableOffers
     */
    private function mockGetEditableOffers($editableOffers)
    {
        $this->permissionRepository->method('getEditableOffers')
            ->willReturn($editableOffers);
    }

    /**
     * @param bool $matches
     */
    private function mockItMatchesOffer($matches)
    {
        $this->userPermissionMatcher->method('itMatchesOffer')
            ->willReturn($matches);
    }
}
