<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category;

use CultuurNet\UDB3\Model\Import\Event\EventLegacyBridgeCategoryResolver;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\GroupedValidationException;

class CategoryExistsValidatorTest extends TestCase
{
    /**
     * @var CategoryExistsValidator
     */
    private $validator;

    public function setUp()
    {
        $this->validator = new CategoryExistsValidator(
            new EventLegacyBridgeCategoryResolver(),
            'Event'
        );
    }

    /**
     * @test
     */
    public function it_should_pass_if_the_given_id_exists_even_if_the_label_and_domain_do_not_match()
    {
        $category = [
            'id' => '0.3.1.0.0',
            'label' => 'foo',
            'domain' => 'bar',
        ];

        $this->assertTrue($this->validator->validate($category));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_the_given_id_does_not_exist()
    {
        $category = [
            'id' => '99.3.1.0.0',
            'label' => 'foo',
            'domain' => 'bar',
        ];

        $expected = [
            'term 99.3.1.0.0 does not exist or is not applicable for Event',
        ];

        try {
            $this->validator->assert($category);
            $errors = [];
        } catch (GroupedValidationException $e) {
            $errors = $e->getMessages();
        }

        $this->assertEquals($expected, $errors);
    }
}
