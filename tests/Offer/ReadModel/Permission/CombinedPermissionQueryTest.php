<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Permission;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class CombinedPermissionQueryTest extends TestCase
{
    /**
     * @var ResourceOwnerQueryInterface[]|MockObject[]
     */
    private $permissionQueries;

    /**
     * @var CombinedPermissionQuery
     */
    private $combinedPermissionQuery;

    protected function setUp()
    {
        $this->permissionQueries[] = $this->createPermissionQuery([
            new StringLiteral('offerId1'),
            new StringLiteral('offerId2'),
        ]);

        $this->permissionQueries[] = $this->createPermissionQuery([
            new StringLiteral('offerId3'),
        ]);

        $this->combinedPermissionQuery = new CombinedPermissionQuery(
            $this->permissionQueries
        );
    }

    /**
     * @test
     */
    public function it_calls_get_editable_offers_on_all_permission_queries()
    {
        foreach ($this->permissionQueries as $permissionQuery) {
            $permissionQuery->expects($this->once())
                ->method('getEditableResourceIds');
        }

        $this->combinedPermissionQuery->getEditableResourceIds(
            new StringLiteral('userId')
        );
    }

    /**
     * @test
     */
    public function it_returns_merged_array_from_all_permission_queries()
    {
        $editableOffers = $this->combinedPermissionQuery->getEditableResourceIds(
            new StringLiteral('userId')
        );

        $expectedEditableOffers = [
            new StringLiteral('offerId1'),
            new StringLiteral('offerId2'),
            new StringLiteral('offerId3'),
        ];

        $this->assertEquals($expectedEditableOffers, $editableOffers);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_queries_contain_no_editable_offers()
    {
        $permissionQueries[] = $this->createPermissionQuery([]);
        $permissionQueries[] = $this->createPermissionQuery([]);

        $combinedPermissionQuery = new CombinedPermissionQuery(
            $permissionQueries
        );

        $this->assertEmpty($combinedPermissionQuery->getEditableResourceIds(
            new StringLiteral('userId')
        ));
    }

    /**
     * @param StringLiteral[] $editableOffers
     * @return ResourceOwnerQueryInterface|MockObject
     */
    private function createPermissionQuery(array $editableOffers)
    {
        $permissionQuery = $this->createMock(ResourceOwnerQueryInterface::class);

        $permissionQuery->method('getEditableResourceIds')
            ->willReturn($editableOffers);

        return $permissionQuery;
    }
}
