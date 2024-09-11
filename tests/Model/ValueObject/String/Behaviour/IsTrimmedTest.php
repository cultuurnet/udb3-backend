<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

class IsTrimmedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider trimDataProvider
     *
     * @param string $original
     * @param string $expected
     */
    public function it_should_trim_both_sides($original, $expected): void
    {
        $trimmed = new MockTrimmed($original);
        $this->assertEquals($expected, $trimmed->toString());
    }

    public function trimDataProvider(): array
    {
        return [
            'unmodified' => [
                'original' => 'foo',
                'expected' => 'foo',
            ],
            'unmodified_with_spaces_in_between' => [
                'original' => 'foo bar',
                'expected' => 'foo bar',
            ],
            'trimmed_suffix' => [
                'original' => 'foo   ',
                'expected' => 'foo',
            ],
            'trimmed_prefix' => [
                'original' => '    foo',
                'expected' => 'foo',
            ],
            'trimmed_both' => [
                'original' => '    foo    ',
                'expected' => 'foo',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider trimLeftToRightDataProvider
     *
     * @param string $original
     * @param string $expected
     */
    public function it_should_trim_left_to_right($original, $expected): void
    {
        $trimmed = new MockTrimmedLeft($original);
        $this->assertEquals($expected, $trimmed->toString());
    }

    public function trimLeftToRightDataProvider(): array
    {
        return [
            'unmodified' => [
                'original' => 'foo',
                'expected' => 'foo',
            ],
            'unmodified_with_spaces_in_between' => [
                'original' => 'foo bar',
                'expected' => 'foo bar',
            ],
            'unmodified_spaces_in_suffix' => [
                'original' => 'foo   ',
                'expected' => 'foo   ',
            ],
            'trimmed_prefix' => [
                'original' => '    foo',
                'expected' => 'foo',
            ],
            'trimmed_prefix_and_unmodified_suffix' => [
                'original' => '    foo    ',
                'expected' => 'foo    ',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider trimRightToLeftDataProvider
     *
     * @param string $original
     * @param string $expected
     */
    public function it_should_trim_right_to_left($original, $expected): void
    {
        $trimmed = new MockTrimmedRight($original);
        $this->assertEquals($expected, $trimmed->toString());
    }

    public function trimRightToLeftDataProvider(): array
    {
        return [
            'unmodified' => [
                'original' => 'foo',
                'expected' => 'foo',
            ],
            'unmodified_with_spaces_in_between' => [
                'original' => 'foo bar',
                'expected' => 'foo bar',
            ],
            'trimmed_suffix' => [
                'original' => 'foo   ',
                'expected' => 'foo',
            ],
            'unmodified_spaces_in_prefix' => [
                'original' => '    foo',
                'expected' => '    foo',
            ],
            'trimmed_suffix_and_unmodified_prefix' => [
                'original' => '    foo    ',
                'expected' => '    foo',
            ],
        ];
    }
}
