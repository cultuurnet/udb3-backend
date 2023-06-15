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
    public function validateRegex(string $labelName, bool $matchesRegex): void
    {
        $this->assertEquals(
            $matchesRegex,
            preg_match(LabelName::REGEX, $labelName)
        );
    }

    /**
     * @test
     * @dataProvider labelNameDataProvider
     */
    public function validateSuggestionsRegex(string $labelName, bool $matchesRegex, bool $matchesSuggestionsRegex): void
    {
        $this->assertEquals(
            $matchesSuggestionsRegex,
            preg_match(LabelName::REGEX_SUGGESTIONS, $labelName)
        );
    }

    public function labelNameDataProvider(): array
    {
        return [
            [
                'labelName' => ';',
                'matchesRegex' => false,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => 'a',
                'matchesRegex' => false,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => '',
                'matchesRegex' => false,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => '   ',
                'matchesRegex' => false,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => ' a',
                'matchesRegex' => false,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => 'a ',
                'matchesRegex' => false,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => 'a;a',
                'matchesRegex' => false,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => str_repeat('abcde', 51) . 'f',
                'matchesRegex' => false,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => 'aa',
                'matchesRegex' => true,
                'matchesSuggestionsRegex' => true,
            ],
            [
                'labelName' => ' aa ',
                'matchesRegex' => true,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => '--',
                'matchesRegex' => true,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => "\r\n",
                'matchesRegex' => false,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => "A\n",
                'matchesRegex' => false,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => "Hard\r\nRock",
                'matchesRegex' => false,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => "\r\nTechno",
                'matchesRegex' => false,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => '#Hashtag',
                'matchesRegex' => true,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => 'Europese Thema\'s',
                'matchesRegex' => true,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => 'Yin & Yang',
                'matchesRegex' => true,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => '-MyLabel',
                'matchesRegex' => true,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => '_MyLabel',
                'matchesRegex' => true,
                'matchesSuggestionsRegex' => false,
            ],
            [
                'labelName' => 'My-Label',
                'matchesRegex' => true,
                'matchesSuggestionsRegex' => true,
            ],
            [
                'labelName' => 'My_Label',
                'matchesRegex' => true,
                'matchesSuggestionsRegex' => true,
            ],
            [
                'labelName' => 'één taalicoon',
                'matchesRegex' => true,
                'matchesSuggestionsRegex' => true,
            ],
            [
                'labelName' => 'Feest!',
                'matchesRegex' => true,
                'matchesSuggestionsRegex' => false,
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
    public function it_throws_on_newlines_in_label(): void
    {
        $newLineLabel = "New\nWave";
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'String \'' . $newLineLabel . '\' does not match regex pattern ' . LabelName::REGEX . '.'
        );
        new LabelName($newLineLabel);
    }
}
