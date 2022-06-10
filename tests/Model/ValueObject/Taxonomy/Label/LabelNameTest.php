<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label;

use PHPUnit\Framework\TestCase;

class LabelNameTest extends TestCase
{
    /**
     * @test
     * @dataProvider labelNameDataProvider
     */
    public function validateRegex(string $labelName, bool $valid): void
    {
        $this->assertEquals(
            $valid,
            preg_match(LabelName::REGEX, $labelName)
        );
    }

    public function labelNameDataProvider(): array
    {
        return [
            [
                ';',
                false,
            ],
            [
                'a',
                false,
            ],
            [
                '',
                false,
            ],
            [
                '   ',
                false,
            ],
            [
                ' a',
                false,
            ],
            [
                'a ',
                false,
            ],
            [
                'a;a',
                false,
            ],
            [
                str_repeat('abcde', 51) . 'f',
                false,
            ],
            [
                'aa',
                true,
            ],
            [
                ' aa ',
                true,
            ],
            [
                '--',
                true,
            ],
            [
                "\r\n",
                false,
            ],
            [
                "A\n",
                false,
            ],
            [
                "Hard\r\nRock",
                true,
            ],
            [
                "\r\nTechno",
                true,
            ],
        ];
    }

    /**
     * @test
     */
    public function it_stores_a_label_value(): void
    {
        $labelName = new LabelName('foo');
        $this->assertEquals('foo', $labelName->toString());
    }

    /**
     * @test
     */
    public function it_trims(): void
    {
        $labelName = new LabelName(' foo ');
        $this->assertEquals('foo', $labelName->toString());
    }

    /**
     * @test
     */
    public function it_does_not_support_semi_colons(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('String \'foo;bar\' does not match regex pattern ' . LabelName::REGEX . '.');
        new LabelName('foo;bar');
    }

    /**
     * @test
     */
    public function it_requires_labels_of_at_least_2_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('String \'f\' does not match regex pattern ' . LabelName::REGEX . '.');
        new LabelName('f');
    }

    /**
     * @test
     */
    public function it_requires_labels_of_at_most_255_characters(): void
    {
        $longLabel = 'abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz' .
            'abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz' .
            'abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz' .
            'abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz' .
            'abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz' .
            'abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz' .
            'abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz' .
            'abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz' .
            'abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'String \'' . $longLabel . '\' does not match regex pattern ' . LabelName::REGEX . '.'
        );
        new LabelName($longLabel);
    }

    /**
     * @test
     */
    public function it_stores_a_newline_label_value(): void
    {
        $labelName = new LabelName("New\nWave");
        $this->assertEquals("New\nWave", $labelName->toString());
    }
}
