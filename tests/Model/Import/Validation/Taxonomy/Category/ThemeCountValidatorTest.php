<?php

namespace CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category;

use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\GroupedValidationException;

class ThemeCountValidatorTest extends TestCase
{
    /**
     * @var ThemeCountValidator
     */
    private $validator;

    public function setUp()
    {
        $this->validator = new ThemeCountValidator();
    }

    /**
     * @test
     */
    public function it_should_pass_if_the_categories_contain_no_theme()
    {
        $categories = [
            [
                'id' => '0.54.0.0.0',
                'label' => 'Dansvoorstelling',
                'domain' => 'eventtype',
            ],
            [
                'id' => '3.23.2.0.0',
                'label' => 'Assistentie',
                'domain' => 'facility',
            ],
        ];

        $this->assertTrue($this->validator->validate($categories));
    }

    /**
     * @test
     */
    public function it_should_pass_if_the_categories_have_one_theme()
    {
        $categories = [
            [
                'id' => '1.2.1.0.0',
                'label' => 'Architectuur',
                'domain' => 'theme',
            ],
            [
                'id' => '0.54.0.0.0',
                'label' => 'Dansvoorstelling',
                'domain' => 'eventtype',
            ],
            [
                'id' => '3.23.2.0.0',
                'label' => 'Assistentie',
                'domain' => 'facility',
            ],
        ];

        $this->assertTrue($this->validator->validate($categories));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_the_categories_have_more_than_one_theme()
    {
        $categories = [
            [
                'id' => '1.2.1.0.0',
                'label' => 'Architectuur',
                'domain' => 'theme',
            ],
            [
                'id' => '0.54.0.0.0',
                'label' => 'Dansvoorstelling',
                'domain' => 'eventtype',
            ],
            [
                'id' => '3.23.2.0.0',
                'label' => 'Assistentie',
                'domain' => 'facility',
            ],
            [
                'id' => '1.11.0.0.0',
                'label' => 'Geschiedenis',
                'domain' => 'theme',
            ],
        ];

        $expected = [
            'terms must contain at most 1 item(s) with domain theme.',
        ];

        try {
            $this->validator->assert($categories);
            $errors = [];
        } catch (GroupedValidationException $e) {
            $errors = $e->getMessages();
        }

        $this->assertEquals($expected, $errors);
    }
}
