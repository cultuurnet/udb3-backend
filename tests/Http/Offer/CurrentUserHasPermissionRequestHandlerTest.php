<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CurrentUserHasPermissionRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd67e5cbc-c085-4ee0-a97b-c3795d480bd4';

    /**
     * @var PermissionVoter&MockObject
     */
    private $voter;

    private CurrentUserHasPermissionRequestHandler $currentUserHasPermissionRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    public function setUp(): void
    {
        $this->voter = $this->createMock(PermissionVoter::class);

        $this->currentUserHasPermissionRequestHandler = new CurrentUserHasPermissionRequestHandler(
            Permission::aanbodBewerken(),
            $this->voter,
            'cd8d2005-e978-4f4c-9eb6-a0c0104fd8d0'
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     * @dataProvider hasPermissionDataProvider
     */
    public function it_returns_if_the_current_user_has_permission(bool $hasPermission): void
    {
        $this->voter->method('isAllowed')->willReturn($hasPermission);

        $currentUserHasPermissionRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->build('GET');

        $response = $this->currentUserHasPermissionRequestHandler->handle($currentUserHasPermissionRequest);

        $this->assertJsonResponse(
            new JsonResponse([
                'hasPermission' => $hasPermission,
            ]),
            $response
        );
    }

    public function hasPermissionDataProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
