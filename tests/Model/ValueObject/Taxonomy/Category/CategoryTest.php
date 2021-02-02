<?php

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category;

use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_consist_of_an_id_and_label_and_domain()
    {
        $id = new CategoryID('0.50.4.0.0');
        $label = new CategoryLabel('concert');
        $domain = new CategoryDomain('eventtype');

        $category = new Category($id, $label, $domain);

        $this->assertEquals($id, $category->getId());
        $this->assertEquals($label, $category->getLabel());
        $this->assertEquals($domain, $category->getDomain());
    }
}
