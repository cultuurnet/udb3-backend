<?php

declare(strict_types=1);

/** @deprecated */

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use CultuurNet\UDB3\StringLiteral;

class OfferPermissionControllerTest extends TestCase
{
    private Permission $permission;

    /**
     * @var PermissionVoter|MockObject
     */
    private $voter;

    private StringLiteral $currentUserId;

    private OfferPermissionController $controllerWithUser;

    private OfferPermissionController $controllerWithoutUser;

    public function setUp(): void
    {
        $this->permission = Permission::aanbodBewerken();
        $this->voter = $this->createMock(PermissionVoter::class);

        $this->currentUserId = new StringLiteral('07e4d93e-3b0a-4b04-b37b-204a82b9c4d2');

        $this->controllerWithUser = new OfferPermissionController(
            $this->permission,
            $this->voter,
            $this->currentUserId
        );

        $this->controllerWithoutUser = new OfferPermissionController(
            $this->permission,
            $this->voter,
            null
        );
    }

    /**
     * @test
     * @dataProvider hasPermissionDataProvider
     */
    public function it_checks_if_the_current_user_has_permission(bool $hasPermission): void
    {
        $offerId = new StringLiteral('235344aa-fb9a-4fa8-bcaa-85a8b19657c7');

        $this->voter->expects($this->once())
            ->method('isAllowed')
            ->with(
                $this->permission,
                $offerId,
                $this->currentUserId
            )
            ->willReturn($hasPermission);

        $expectedResponse = JsonResponse::create(['hasPermission' => $hasPermission]);

        $actualResponse = $this->controllerWithUser->currentUserHasPermission($offerId);

        $this->assertEquals($expectedResponse->getContent(), $actualResponse->getContent());
    }

    /**
     * @test
     * @dataProvider hasPermissionDataProvider
     */
    public function it_checks_if_a_given_user_has_permission_if_no_user_is_authenticated(bool $hasPermission): void
    {
        $offerId = new StringLiteral('235344aa-fb9a-4fa8-bcaa-85a8b19657c7');
        $givenUserId = new StringLiteral('08289d30-8f7d-4012-9170-9dc8911c7ba2');

        $this->voter->expects($this->once())
            ->method('isAllowed')
            ->with(
                $this->permission,
                $offerId,
                $givenUserId
            )
            ->willReturn($hasPermission);

        $expectedResponse = JsonResponse::create(['hasPermission' => $hasPermission]);

        $actualResponse = $this->controllerWithoutUser->givenUserHasPermission($offerId, $givenUserId);

        $this->assertEquals($expectedResponse->getContent(), $actualResponse->getContent());
    }

    /**
     * @test
     * @dataProvider hasPermissionDataProvider
     */
    public function it_checks_if_a_given_user_has_permission_even_if_a_user_is_authenticated(bool $hasPermission): void
    {
        $offerId = new StringLiteral('235344aa-fb9a-4fa8-bcaa-85a8b19657c7');
        $givenUserId = new StringLiteral('08289d30-8f7d-4012-9170-9dc8911c7ba2');

        $this->voter->expects($this->once())
            ->method('isAllowed')
            ->with(
                $this->permission,
                $offerId,
                $givenUserId
            )
            ->willReturn($hasPermission);

        $expectedResponse = JsonResponse::create(['hasPermission' => $hasPermission]);

        $actualResponse = $this->controllerWithUser->givenUserHasPermission($offerId, $givenUserId);

        $this->assertEquals($expectedResponse->getContent(), $actualResponse->getContent());
    }

    /**
     * @test
     */
    public function it_always_returns_false_if_there_is_no_given_or_current_user(): void
    {
        $offerId = new StringLiteral('235344aa-fb9a-4fa8-bcaa-85a8b19657c7');

        $this->voter->expects($this->never())
            ->method('isAllowed');

        $expectedResponse = JsonResponse::create(['hasPermission' => false]);

        $actualResponse = $this->controllerWithoutUser->currentUserHasPermission($offerId);

        $this->assertEquals($expectedResponse->getContent(), $actualResponse->getContent());
    }

    public function hasPermissionDataProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
