<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use PHPUnit\Framework\TestCase;

class GetPermissionsRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private GetPermissionsRequestHandler $getPermissionsRequestHandler;

    protected function setUp(): void
    {
        $this->getPermissionsRequestHandler = new GetPermissionsRequestHandler();
    }

    /**
     * @test
     */
    public function it_throws_not_found_on_missing_role(): void
    {
        $request = (new Psr7RequestBuilder())
            ->build('GET');
        $response = $this->getPermissionsRequestHandler->handle($request);

        $permissions = [
            'AANBOD_BEWERKEN',
            'AANBOD_MODEREREN',
            'AANBOD_VERWIJDEREN',
            'AANBOD_HISTORIEK',
            'ORGANISATIES_BEHEREN',
            'ORGANISATIES_BEWERKEN',
            'GEBRUIKERS_BEHEREN',
            'LABELS_BEHEREN',
            'VOORZIENINGEN_BEWERKEN',
            'PRODUCTIES_AANMAKEN',
            'FILMS_AANMAKEN',
        ];

        $this->assertJsonResponse(new JsonResponse(
            $permissions,
            200
        ), $response);
    }
}
