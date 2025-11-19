<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

use PHPUnit\Framework\TestCase;

class IsSanitizesTest extends TestCase
{
    /**
     * @test
     * @dataProvider sanitizeDataProvider
     */
    public function it_should_sanitize(string $input, string $expectedOutput): void
    {
        $sanitized = new MockSanitized($input);
        $this->assertEquals($expectedOutput, $sanitized->toString());
    }

    public function sanitizeDataProvider(): array
    {
        return [
            'no_tags' => [
                'original' => 'Just some text',
                'expected' => 'Just some text',
            ],
            'allowed_tags' => [
                'original' => '<p><ul><li><strong>Water</strong></li><li><em>Bier</em></li><li><em>Bier</em></li></ul><a href=\"https://menulijst.com\" target=\"_blank\">Menulijst</a></p>',
                'expected' => '<p><ul><li><strong>Water</strong></li><li><em>Bier</em></li><li><em>Bier</em></li></ul><a href=\"https://menulijst.com\" target=\"_blank\">Menulijst</a></p>',
            ],
            'disallowed_tags' => [
                'original' => '<script></script><img src=\"https://foobar.com/1f457.png\" alt=\":dress:\" style=\"height: ;width: \"/>',
                'expected' => '',
            ],
            'mixing_allowed_and_disallowed_tags' => [
                'original' => '<img src=\"https://foobar.com/1f457.png\" alt=\":dress:\" style=\"height: ;width: \"/><strong>Out latest dress</strong>',
                'expected' => '<strong>Out latest dress</strong>',
            ],
        ];
    }
}
