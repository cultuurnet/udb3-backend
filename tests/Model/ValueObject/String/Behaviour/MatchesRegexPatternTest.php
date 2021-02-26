<?php

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

class MatchesRegexPatternTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_throw_an_exception_when_trying_to_match_a_value_that_is_not_a_string()
    {
        $value = 10;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given value should be a string, got integer instead.');

        new MockDigitsRegexPattern($value);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_the_given_string_does_not_match_the_regex_pattern()
    {
        $value = 'ab10';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given value is not a digit.');

        new MockDigitsRegexPattern($value);
    }

    /**
     * @test
     */
    public function it_should_create_a_value_object_if_the_given_string_matches()
    {
        $value = '10';
        $vo = new MockDigitsRegexPattern($value);
        $this->assertEquals($value, $vo->toString());
    }
}
