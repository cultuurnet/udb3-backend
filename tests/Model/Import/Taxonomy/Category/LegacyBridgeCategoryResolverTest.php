<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Taxonomy\Category;

use CultuurNet\UDB3\Model\Import\Event\EventCategoryResolver;
use CultuurNet\UDB3\Model\Import\Place\PlaceCategoryResolver;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use PHPUnit\Framework\TestCase;

class LegacyBridgeCategoryResolverTest extends TestCase
{
    private LegacyBridgeCategoryResolver $legacyBridgeCategoryResolver;

    protected function setUp(): void
    {
        $this->legacyBridgeCategoryResolver = new EventCategoryResolver();
    }

    /**
     * @test
     */
    public function it_returns_a_category_for_a_category_id_that_exists(): void
    {
        $id = new CategoryID('0.7.0.0.0');

        $expected = new Category($id, new CategoryLabel('Begeleide rondleiding'), new CategoryDomain('eventtype'));
        $actual = $this->legacyBridgeCategoryResolver->byId($id);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_returns_a_category_for_a_category_id_that_exists_in_the_given_category_domain(): void
    {
        $id = new CategoryID('0.7.0.0.0');
        $domain = new CategoryDomain('eventtype');

        $expected = new Category($id, new CategoryLabel('Begeleide rondleiding'), $domain);
        $actual = $this->legacyBridgeCategoryResolver->byIdInDomain($id, $domain);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_returns_null_for_a_category_id_that_does_not_exist_in_the_given_category_domain(): void
    {
        $id = new CategoryID('0.7.0.0.0');
        $domain = new CategoryDomain('theme');

        $actual = $this->legacyBridgeCategoryResolver->byIdInDomain($id, $domain);

        $this->assertNull($actual);
    }

    /**
     * @test
     */
    public function it_returns_null_for_a_category_id_that_does_not_exist(): void
    {
        $id = new CategoryID('foobar');

        $this->assertNull($this->legacyBridgeCategoryResolver->byId($id));
        $this->assertNull($this->legacyBridgeCategoryResolver->byIdInDomain($id, new CategoryDomain('eventtype')));
    }

    /**
     * @test
     */
    public function it_works_without_theme_resolver(): void
    {
        // "Actie en avontuur" theme.
        $id = new CategoryID('1.7.2.0.0');

        // Not found in PlaceCategoryResolver
        $resolver = new PlaceCategoryResolver();
        $this->assertNull($resolver->byId($id));
        $this->assertNull($resolver->byIdInDomain($id, new CategoryDomain('theme')));

        // Found in EventCategoryResolver that uses a ThemeResolver
        $this->assertInstanceOf(Category::class, $this->legacyBridgeCategoryResolver->byId($id));
        $this->assertInstanceOf(Category::class, $this->legacyBridgeCategoryResolver->byIdInDomain($id, new CategoryDomain('theme')));
    }
}
