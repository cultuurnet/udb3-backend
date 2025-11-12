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

final class GivenUserHasPermissionRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd67e5cbc-c085-4ee0-a97b-c3795d480bd4';
    private const GIVEN_USER_ID = 'cd8d2005-e978-4f4c-9eb6-a0c0104fd8d0';

    private PermissionVoter&MockObject $voter;

    private GivenUserHasPermissionRequestHandler $givenUserHasPermissionRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    public function setUp(): void
    {
        $this->voter = $this->createMock(PermissionVoter::class);

        $this->givenUserHasPermissionRequestHandler = new GivenUserHasPermissionRequestHandler(
            Permission::aanbodBewerken(),
            $this->voter
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     * @dataProvider hasPermissionDataProvider
     */
    public function it_returns_if_the_given_user_has_permission(bool $hasPermission): void
    {
        $this->voter->method('isAllowed')->willReturn($hasPermission);

        $givenUserHasPermissionRequestHandler = $this->psr7RequestBuilder
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withRouteParameter('userId', self::GIVEN_USER_ID)
            ->build('GET');

        $response = $this->givenUserHasPermissionRequestHandler->handle($givenUserHasPermissionRequestHandler);

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
