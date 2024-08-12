<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Security\Permission\Sapi3RoleConstraintVoter;
use CultuurNet\UDB3\Role\ReadModel\Constraints\UserConstraintsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Http\Client\HttpClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class Sapi3RoleConstraintVoterTest extends TestCase
{
    /**
     * @var UserConstraintsReadRepositoryInterface&MockObject
     */
    private $userConstraintsReadRepository;

    /**
     * @var HttpClient&MockObject
     */
    private $httpClient;

    private Sapi3RoleConstraintVoter $roleConstraintVoter;

    protected function setUp(): void
    {
        $this->userConstraintsReadRepository = $this->createMock(
            UserConstraintsReadRepositoryInterface::class
        );

        $searchLocation =  new Uri('http://udb-search.dev/offers/');
        $this->httpClient = $this->createMock(HttpClient::class);
        $apiKey = 'cf462083-7bbd-46fc-95c3-6a0bc95918a5';
        $extraParameters = ['disableDefaultFilters' => true];

        $this->roleConstraintVoter = new Sapi3RoleConstraintVoter(
            $this->userConstraintsReadRepository,
            $searchLocation,
            $this->httpClient,
            $apiKey,
            $extraParameters
        );
    }

    /**
     * @test
     * @dataProvider totalItemsDataProvider()
     */
    public function it_does_match_offer_based_on_total_items_count_of_one(
        int $totalItems,
        bool $expected
    ): void {
        $userId = 'ff085fed-8500-4dd9-8ac0-459233c642f4';
        $permission = Permission::aanbodBewerken();
        $constraints = [
            'address.\*.postalCode:3000',
        ];
        $offerId = '625a4e74-a1ca-4bee-9e85-39869457d531';
        $query = '((address.\*.postalCode:3000) AND id:625a4e74-a1ca-4bee-9e85-39869457d531)';

        $this->userConstraintsReadRepository->expects($this->once())
            ->method('getByUserAndPermission')
            ->with(
                $userId,
                $permission
            )
            ->willReturn($constraints);

        $expectedRequest = new Request(
            'GET',
            'http://udb-search.dev/offers/?q=' . urlencode($query) . '&start=0&limit=1&disableDefaultFilters=1',
            ['X-Api-Key' => 'cf462083-7bbd-46fc-95c3-6a0bc95918a5']
        );

        $response = new Response(200, [], Json::encode(['totalItems' => $totalItems]));

        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($response);

        $this->assertEquals(
            $expected,
            $this->roleConstraintVoter->isAllowed(
                $permission,
                $offerId,
                $userId
            )
        );
    }

    public function totalItemsDataProvider(): array
    {
        return [
            [
                1,
                true,
            ],
            [
                0,
                false,
            ],
            [
                2,
                false,
            ],
        ];
    }

    /**
     * @test
     */
    public function it_does_not_match_offer_when_user_has_no_matching_constraints(): void
    {
        $userId = 'ff085fed-8500-4dd9-8ac0-459233c642f4';
        $permission = Permission::aanbodBewerken();
        $offerId = '625a4e74-a1ca-4bee-9e85-39869457d531';

        $this->userConstraintsReadRepository->expects($this->once())
            ->method('getByUserAndPermission')
            ->with(
                $userId,
                $permission
            )
            ->willReturn([]);

        $this->httpClient->expects($this->never())
            ->method('sendRequest');

        $this->assertFalse(
            $this->roleConstraintVoter->isAllowed(
                $permission,
                $offerId,
                $userId
            )
        );
    }
}
