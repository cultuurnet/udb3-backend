<?php

declare(strict_types=1);

/** @deprecated */

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Http\Assert\JsonEquals;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use CultuurNet\UDB3\StringLiteral;

class OfferPermissionsControllerTest extends TestCase
{
    private array $permissions;

    /**
     * @var PermissionVoter|MockObject
     */
    private $voter;

    private StringLiteral $currentUserId;

    private OfferPermissionsController $controllerWithUser;

    private OfferPermissionsController $controllerWithoutUser;

    private JsonEquals $jsonEquals;

    public function setUp(): void
    {
        $permissionsToCheck = [
            Permission::aanbodBewerken(),
            Permission::aanbodModereren(),
            Permission::aanbodVerwijderen(),
        ];
        $this->permissions = $permissionsToCheck;
        $this->voter = $this->createMock(PermissionVoter::class);

        $this->currentUserId = new StringLiteral('cd8d2005-e978-4f4c-9eb6-a0c0104fd8d0');

        $this->controllerWithUser = new OfferPermissionsController(
            $this->permissions,
            $this->voter,
            $this->currentUserId
        );

        $this->controllerWithoutUser = new OfferPermissionsController(
            $this->permissions,
            $this->voter,
            null
        );

        $this->jsonEquals = new JsonEquals($this);
    }

    /**
     * @test
     */
    public function it_returns_an_array_of_permissions_for_the_current_user(): void
    {
        $this->voter->method('isAllowed')->willReturn(true);

        $actualResponse = $this->controllerWithUser
            ->getPermissionsForCurrentUser('b06a4ab4-a75b-49d1-b4ab-1992c1db908a');
        $actualResponseJson = $actualResponse->getContent();

        $expectedPermissions = [
            'Aanbod bewerken',
            'Aanbod modereren',
            'Aanbod verwijderen',
        ];
        $expectedResponseJson = json_encode(['permissions' => $expectedPermissions]);

        $this->jsonEquals->assert($expectedResponseJson, $actualResponseJson);
    }

    /**
     * @test
     */
    public function it_returns_an_array_of_permissions_for_a_given_user(): void
    {
        $this->voter->method('isAllowed')->willReturn(true);

        $actualResponse = $this->controllerWithUser
            ->getPermissionsForGivenUser(
                'b06a4ab4-a75b-49d1-b4ab-1992c1db908a',
                '0d7019dc-d31e-4735-b9d1-5e782af97387'
            );
        $actualResponseJson = $actualResponse->getContent();

        $expectedPermissions = [
            'Aanbod bewerken',
            'Aanbod modereren',
            'Aanbod verwijderen',
        ];
        $expectedResponseJson = json_encode(['permissions' => $expectedPermissions]);

        $this->jsonEquals->assert($expectedResponseJson, $actualResponseJson);
    }

    /**
     * @test
     */
    public function it_always_returns_empty_array_if_there_is_no_given_or_current_user(): void
    {
        $offerId = new StringLiteral('235344aa-fb9a-4fa8-bcaa-85a8b19657c7');

        $this->voter->expects($this->never())
            ->method('isAllowed');

        $expectedResponse = JsonResponse::create(['permissions' => []]);

        $actualResponse = $this->controllerWithoutUser->getPermissionsForCurrentUser($offerId);

        $this->assertEquals($expectedResponse->getContent(), $actualResponse->getContent());
    }
}
