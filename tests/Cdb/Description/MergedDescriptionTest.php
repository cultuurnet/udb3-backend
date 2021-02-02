<?php

namespace CultuurNet\UDB3\Cdb\Description;

use PHPUnit\Framework\TestCase;

class MergedDescriptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider shortAndLongDescriptionDataProvider
     *
     * @param string $shortDescription
     * @param string $longDescription
     * @param string $expectedMergedDescription
     */
    public function it_should_merge_short_and_long_description_if_short_description_is_not_included_in_long_description(
        $shortDescription,
        $longDescription,
        $expectedMergedDescription
    ) {
        $shortDescription = new ShortDescription($shortDescription);
        $longDescription = new LongDescription($longDescription);

        $expectedMergedDescription = new MergedDescription($expectedMergedDescription);
        $actualMergedDescription = MergedDescription::merge($shortDescription, $longDescription);

        $this->assertEquals($expectedMergedDescription, $actualMergedDescription);
    }

    /**
     * @return array
     */
    public function shortAndLongDescriptionDataProvider()
    {
        /* @codingStandardsIgnoreStart */
        return [
            "it_should_concatenate_short_and_long_if_short_is_completely_different" => [
                "short" => "Korte beschrijving.",
                "long" => "Lange beschrijving.\n\nTweede paragraaf.\n\nDerde paragraaf.",
                'merged' => "Korte beschrijving.\n\nLange beschrijving.\n\nTweede paragraaf.\n\nDerde paragraaf.",
            ],
            "it_should_concatenate_short_and_long_even_if_short_has_an_ellipsis_but_is_completely_different" => [
                "short" => "Korte beschrijving ...",
                "long" => "Lange beschrijving.\n\nTweede paragraaf.\n\nDerde paragraaf.",
                'merged' => "Korte beschrijving ...\n\nLange beschrijving.\n\nTweede paragraaf.\n\nDerde paragraaf.",
            ],
            "it_should_keep_the_html_formatting_in_long_when_concatenating" => [
                "short" => "Korte beschrijving.",
                "long" => "Lange <b>beschrijving</b>.\n\nTweede <a href='#'>paragraaf</a>.\n\nDerde &amp; paragraaf.",
                'merged' => "Korte beschrijving.\n\nLange <b>beschrijving</b>.\n\nTweede <a href='#'>paragraaf</a>.\n\nDerde &amp; paragraaf.",
            ],
            "it_should_not_concatenate_if_short_description_is_exactly_the_start_of_long_description" => [
                "short" => "Korte beschrijving.",
                "long" => "Korte beschrijving.\n\nLange beschrijving.\n\nTweede paragraaf.\n\nDerde paragraaf.",
                'merged' => "Korte beschrijving.\n\nLange beschrijving.\n\nTweede paragraaf.\n\nDerde paragraaf.",
            ],
            "it_should_not_concatenate_if_short_description_minus_a_trailing_ellipsis_is_exactly_the_start_of_long_description" => [
                "short" => "Korte beschrijving...",
                "long" => "Korte beschrijving en lange beschrijving doorheen.",
                'merged' => "Korte beschrijving en lange beschrijving doorheen.",
            ],
            "it_should_not_concatenate_if_short_description_minus_a_trailing_ellipsis_or_trailing_whitespace_is_exactly_the_start_of_long_description" => [
                "short" => "Beschrijving ...",
                "long" => "Beschrijving.",
                'merged' => "Beschrijving.",
            ],
            "it_should_not_concatenate_if_short_description_is_the_start_of_the_long_description_but_with_weird_spacing_where_p_tags_used_to_be" => [
                "short" => "Lange beschrijving.Tweede paragraaf.Derde paragraaf.",
                "long" => "<p>Lange beschrijving.</p><p>Tweede paragraaf.</p><p>Derde paragraaf.</p>",
                'merged' => "<p>Lange beschrijving.</p><p>Tweede paragraaf.</p><p>Derde paragraaf.</p>",
            ],
            "it_should_not_concatenate_if_short_description_is_the_start_of_the_long_description_and_p_tags_have_been_replaced_correctly" => [
                "short" => "Lange beschrijving. Tweede paragraaf. Derde paragraaf.",
                "long" => "<p>Lange beschrijving.</p><p>Tweede paragraaf.</p><p>Derde paragraaf.</p>",
                'merged' => "<p>Lange beschrijving.</p><p>Tweede paragraaf.</p><p>Derde paragraaf.</p>",
            ],
        ];
        /* @codingStandardsIgnoreEnd */
    }
}
