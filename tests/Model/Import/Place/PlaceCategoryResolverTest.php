<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Place;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Offer\OfferFacilityResolverInterface;
use CultuurNet\UDB3\Offer\TypeResolverInterface;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PlaceCategoryResolverTest extends TestCase
{
    private PlaceCategoryResolver $placeCategoryResolver;

    /**
     * @var TypeResolverInterface&MockObject
     */
    private $typeResolver;

    /**
     * @var OfferFacilityResolverInterface&MockObject
     */
    private $facilityResolver;

    protected function setUp(): void
    {
        $this->typeResolver = $this->createMock(TypeResolverInterface::class);
        $this->facilityResolver = $this->createMock(OfferFacilityResolverInterface::class);

        $this->placeCategoryResolver = new PlaceCategoryResolver(
            $this->typeResolver,
            $this->facilityResolver
        );
    }

    /**
     * @test
     */
    public function it_returns_a_category_for_a_category_id_that_exists(): void
    {
        $id = '0.15.0.0.0';

        $expected = new Category(new CategoryID($id), new CategoryLabel('Natuurgebied of park'), CategoryDomain::eventType());
        $this->typeResolver->expects($this->once())
            ->method('byId')
            ->with($id)
            ->willReturn(
                new Category(
                    new CategoryID($id),
                    new CategoryLabel('Natuurgebied of park'),
                    CategoryDomain::eventType()
                )
            );

        $this->assertEquals($expected, $this->placeCategoryResolver->byId(new CategoryID($id)));
    }

    /**
     * @test
     */
    public function it_returns_a_category_for_a_category_id_that_exists_in_the_given_category_domain(): void
    {
        $id = '0.15.0.0.0';
        $expected = new Category(new CategoryID($id), new CategoryLabel('Natuurgebied of park'), CategoryDomain::eventType());
        $this->typeResolver->expects($this->once())
            ->method('byId')
            ->with($id)
            ->willReturn(
                new Category(
                    new CategoryID($id),
                    new CategoryLabel('Natuurgebied of park'),
                    CategoryDomain::eventType()
                )
            );
        $this->facilityResolver->expects($this->never())
            ->method('byId')
            ->with($id);

        $this->assertEquals(
            $expected,
            $this->placeCategoryResolver->byIdInDomain(new CategoryID($id), CategoryDomain::eventType())
        );
    }

    /**
     * @test
     */
    public function it_returns_null_for_a_category_id_that_does_not_exist_in_the_given_category_domain(): void
    {
        $id = '0.15.0.0.0';
        $this->typeResolver->expects($this->never())
            ->method('byId')
            ->with($id);
        $this->facilityResolver->expects($this->once())
            ->method('byId')
            ->with($id)
            ->willThrowException(new Exception('Unknown facility id: ' . $id));

        $this->assertNull(
            $this->placeCategoryResolver->byIdInDomain(new CategoryID($id), CategoryDomain::facility())
        );
    }

    /**
     * @test
     */
    public function it_returns_null_for_a_category_id_that_does_not_exist(): void
    {
        $id = 'foobar';

        $this->typeResolver->expects($this->once())
            ->method('byId')
            ->with($id)
            ->willThrowException(new Exception('Unknown event type id: ' . $id));
        $this->facilityResolver->expects($this->once())
            ->method('byId')
            ->with($id)
            ->willThrowException(new Exception('Unknown facility id: ' . $id));

        $this->assertNull($this->placeCategoryResolver->byId(new CategoryID($id)));
    }
}
