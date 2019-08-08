<?php

namespace CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category;

use CultuurNet\UDB3\Model\Import\Event\EventLegacyBridgeCategoryResolver;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\GroupedValidationException;

class CategoriesExistValidatorTest extends TestCase
{
    /**
     * @var CategoryExistsValidator
     */
    private $validator;

    public function setUp()
    {
        $this->validator = new CategoriesExistValidator(
            new EventLegacyBridgeCategoryResolver(),
            'Event'
        );
    }

    /**
     * @test
     */
    public function it_should_pass_if_the_given_ids_exist_even_if_the_labels_and_domains_do_not_match()
    {
        $categories = [
            [
                'id' => '0.3.1.0.0',
                'label' => 'foo',
                'domain' => 'bar',
            ],
            [
                'id' => '0.54.0.0.0',
                'label' => 'foo',
                'domain' => 'bar',
            ],
        ];

        $this->assertTrue($this->validator->validate($categories));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_one_ore_more_ids_do_not_exist()
    {
        $category = [
            [
                'id' => '0.3.1.0.0',
                'label' => 'foo',
                'domain' => 'bar',
            ],
            [
                'id' => '99.3.1.0.0',
                'label' => 'foo',
                'domain' => 'bar',
            ],
            [
                'id' => '0.54.0.0.0',
                'label' => 'foo',
                'domain' => 'bar',
            ],
            [
                'id' => '100.54.0.0.0',
                'label' => 'foo',
                'domain' => 'bar',
            ],
        ];

        $expected = [
            'Each item in terms must be valid',
            'term 99.3.1.0.0 does not exist or is not applicable for Event',
            'term 100.54.0.0.0 does not exist or is not applicable for Event',
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
