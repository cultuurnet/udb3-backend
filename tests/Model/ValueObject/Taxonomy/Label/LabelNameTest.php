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
    public function validateLegacyRegex(string $labelName, bool $matchesLegacyRegex): void
    {
        $this->assertEquals(
            $matchesLegacyRegex,
            preg_match(LabelName::LEGACY_REGEX, $labelName)
        );
    }

    /**
     * @test
     * @dataProvider labelNameDataProvider
     */
    public function validateRegex(string $labelName, bool $matchesLegacyRegex, bool $matchesRegex): void
    {
        $this->assertEquals(
            $matchesRegex,
            preg_match(LabelName::REGEX_SUGGESTIONS, $labelName)
        );
    }

    public function labelNameDataProvider(): array
    {
        return [
            [
                'labelName' => ';',
                'matchesLegacyRegex' => false,
                'matchesRegex' => false,
            ],
            [
                'labelName' => 'a',
                'matchesLegacyRegex' => false,
                'matchesRegex' => false,
            ],
            [
                'labelName' => '',
                'matchesLegacyRegex' => false,
                'matchesRegex' => false,
            ],
            [
                'labelName' => '   ',
                'matchesLegacyRegex' => false,
                'matchesRegex' => false,
            ],
            [
                'labelName' => ' a',
                'matchesLegacyRegex' => false,
                'matchesRegex' => false,
            ],
            [
                'labelName' => 'a ',
                'matchesLegacyRegex' => false,
                'matchesRegex' => false,
            ],
            [
                'labelName' => 'a;a',
                'matchesLegacyRegex' => false,
                'matchesRegex' => false,
            ],
            [
                'labelName' => str_repeat('abcde', 51) . 'f',
                'matchesLegacyRegex' => false,
                'matchesRegex' => false,
            ],
            [
                'labelName' => 'aa',
                'matchesLegacyRegex' => true,
                'matchesRegex' => true,
            ],
            [
                'labelName' => ' aa ',
                'matchesLegacyRegex' => true,
                'matchesRegex' => false,
            ],
            [
                'labelName' => '--',
                'matchesLegacyRegex' => true,
                'matchesRegex' => false,
            ],
            [
                'labelName' => "\r\n",
                'matchesLegacyRegex' => false,
                'matchesRegex' => false,
            ],
            [
                'labelName' => "A\n",
                'matchesLegacyRegex' => false,
                'matchesRegex' => false,
            ],
            [
                'labelName' => "Hard\r\nRock",
                'matchesLegacyRegex' => false,
                'matchesRegex' => false,
            ],
            [
                'labelName' => "\r\nTechno",
                'matchesLegacyRegex' => false,
                'matchesRegex' => false,
            ],
            [
                'labelName' => '#Hashtag',
                'matchesLegacyRegex' => true,
                'matchesRegex' => false,
            ],
            [
                'labelName' => 'Europese Thema\'s',
                'matchesLegacyRegex' => true,
                'matchesRegex' => false,
            ],
            [
                'labelName' => 'Yin & Yang',
                'matchesLegacyRegex' => true,
                'matchesRegex' => false,
            ],
            [
                'labelName' => '-MyLabel',
                'matchesLegacyRegex' => true,
                'matchesRegex' => false,
            ],
            [
                'labelName' => '_MyLabel',
                'matchesLegacyRegex' => true,
                'matchesRegex' => false,
            ],
            [
                'labelName' => 'My-Label',
                'matchesLegacyRegex' => true,
                'matchesRegex' => true,
            ],
            [
                'labelName' => 'My_Label',
                'matchesLegacyRegex' => true,
                'matchesRegex' => true,
            ],
            [
                'labelName' => 'één taalicoon',
                'matchesLegacyRegex' => true,
                'matchesRegex' => true,
            ],
            [
                'labelName' => 'Feest!',
                'matchesLegacyRegex' => true,
                'matchesRegex' => false,
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
        $this->expectExceptionMessage('String \'foo;bar\' does not match regex pattern ' . LabelName::LEGACY_REGEX . '.');
        new LabelName('foo;bar');
    }

    /**
     * @test
     */
    public function it_requires_labels_of_at_least_2_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('String \'f\' does not match regex pattern ' . LabelName::LEGACY_REGEX . '.');
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
            'String \'' . $longLabel . '\' does not match regex pattern ' . LabelName::LEGACY_REGEX . '.'
        );
        new LabelName($longLabel);
    }

    /**
     * @test
     */
    public function it_throws_on_newlines_in_label(): void
    {
        $newLineLabel = "New\nWave";
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'String \'' . $newLineLabel . '\' does not match regex pattern ' . LabelName::LEGACY_REGEX . '.'
        );
        new LabelName($newLineLabel);
    }
}
