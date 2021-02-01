<?php

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_category()
    {
        $id = new CategoryID('0.50.4.0.0');
        $label = new CategoryLabel('concert');
        $domain = new CategoryDomain('eventtype');

        $udb3ModelCategory = new Category($id, $label, $domain);

        $expected = new \CultuurNet\UDB3\Category(
            '0.50.4.0.0',
            'concert',
            'eventtype'
        );

        $actual = \CultuurNet\UDB3\Category::fromUdb3ModelCategory($udb3ModelCategory);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_the_given_udb3_model_category_has_no_label()
    {
        $this->expectException(\InvalidArgumentException::class);

        $id = new CategoryID('0.50.4.0.0');
        $domain = new CategoryDomain('eventtype');

        $udb3ModelCategory = new Category($id, null, $domain);

        \CultuurNet\UDB3\Category::fromUdb3ModelCategory($udb3ModelCategory);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_the_given_udb3_model_category_has_no_domain()
    {
        $this->expectException(\InvalidArgumentException::class);

        $id = new CategoryID('0.50.4.0.0');
        $label = new CategoryLabel('concert');

        $udb3ModelCategory = new Category($id, $label, null);

        \CultuurNet\UDB3\Category::fromUdb3ModelCategory($udb3ModelCategory);
    }
}
