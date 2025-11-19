<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

use PHPUnit\Framework\TestCase;

class ItStripsTagsTest extends TestCase
{
    /**
     * @test
     * @dataProvider taggedDataProvider
     */
    public function it_should_strip_tags(string $input, string $expectedOutput): void
    {
        $mockStripTags = new MockStripTags($input);
        $this->assertEquals($expectedOutput, $mockStripTags->toString());
    }

    public function taggedDataProvider(): array
    {
        return [
            'no_tags' => [
                'original' => 'Just some text',
                'expected' => 'Just some text',
            ],
            'allowed_tags' => [
                'original' => '<p><ul><li><strong>Water</strong></li><li><em>Bier</em></li></ul><a href=\"https://menulijst.com\" target=\"_blank\">Menulijst</a></p>',
                'expected' => '<p><ul><li><strong>Water</strong></li><li><em>Bier</em></li></ul><a href=\"https://menulijst.com\" target=\"_blank\">Menulijst</a></p>',
            ],
            'disallowed_tags' => [
                'original' => '<script></script><img src=\"https://foobar.com/1f457.png\" alt=\":dress:\" style=\"height: ;width: \"/>',
                'expected' => '',
            ],
            'mixing_allowed_and_disallowed_tags' => [
                'original' => '<img src=\"https://foobar.com/1f457.png\" alt=\":dress:\" style=\"height: ;width: \"/><strong>Our latest dress</strong>',
                'expected' => '<strong>Our latest dress</strong>',
            ],
        ];
    }
}
