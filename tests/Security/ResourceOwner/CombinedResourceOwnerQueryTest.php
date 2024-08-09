<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CombinedResourceOwnerQueryTest extends TestCase
{
    /**
     * @var ResourceOwnerQuery[]&MockObject[]
     */
    private $permissionQueries;

    private CombinedResourceOwnerQuery $combinedPermissionQuery;

    protected function setUp(): void
    {
        $this->permissionQueries[] = $this->createPermissionQuery([
            'offerId1',
            'offerId2',
        ]);

        $this->permissionQueries[] = $this->createPermissionQuery([
            'offerId3',
        ]);

        $this->combinedPermissionQuery = new CombinedResourceOwnerQuery(
            $this->permissionQueries
        );
    }

    /**
     * @test
     */
    public function it_calls_get_editable_offers_on_all_permission_queries(): void
    {
        foreach ($this->permissionQueries as $permissionQuery) {
            $permissionQuery->expects($this->once())
                ->method('getEditableResourceIds');
        }

        $this->combinedPermissionQuery->getEditableResourceIds(
            'userId'
        );
    }

    /**
     * @test
     */
    public function it_returns_merged_array_from_all_permission_queries(): void
    {
        $editableOffers = $this->combinedPermissionQuery->getEditableResourceIds('userId');

        $expectedEditableOffers = [
            'offerId1',
            'offerId2',
            'offerId3',
        ];

        $this->assertEquals($expectedEditableOffers, $editableOffers);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_queries_contain_no_editable_offers(): void
    {
        $permissionQueries[] = $this->createPermissionQuery([]);
        $permissionQueries[] = $this->createPermissionQuery([]);

        $combinedPermissionQuery = new CombinedResourceOwnerQuery(
            $permissionQueries
        );

        $this->assertEmpty($combinedPermissionQuery->getEditableResourceIds('userId'));
    }

    /**
     * @param string[] $editableOffers
     * @return ResourceOwnerQuery&MockObject
     */
    private function createPermissionQuery(array $editableOffers)
    {
        $permissionQuery = $this->createMock(ResourceOwnerQuery::class);

        $permissionQuery->method('getEditableResourceIds')
            ->willReturn($editableOffers);

        return $permissionQuery;
    }
}
