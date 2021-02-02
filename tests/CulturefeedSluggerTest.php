<?php
/**
 * @file
 */

namespace CultuurNet\UDB3;

use PHPUnit\Framework\TestCase;

class CulturefeedSluggerTest extends TestCase
{
    /**
     * @var CulturefeedSlugger
     */
    protected $slugger;

    public function setUp()
    {
        $this->slugger = new CulturefeedSlugger();
    }

    public function slugsProvider()
    {
        return [
            [
                'Cinema Olympia - Boyhood (een film van Richard Linklater)',
                'cinema-olympia-boyhood-een-film-van-richard-linkla',
            ],
            [
                'Jekyll/Hyde',
                'jekyll-hyde',
            ],
            [
                'Babbelut - conversatietafels',
                'babbelut-conversatietafels',
            ],
            [
                'Uw toren is niet af - Klos mee!',
                'uw-toren-is-niet-af-klos-mee',
            ],
            [
                'Jump! Kids (danslessenreeks voor 7 - 10 jarigen)',
                'jump-kids-danslessenreeks-voor-7-10-jarigen',
            ],
            [
                'BABBELonië',
                'babbelonie',
            ],
            [
                'Djembé : Gezinsbond Beersel',
                'djembe-gezinsbond-beersel',
            ],
            [
                'Tai Chi vrijdagavond nieuwe reeks van 15 lessen 2015',
                'tai-chi-vrijdagavond-nieuwe-reeks-van-15-lessen-20',
            ],
        ];
    }

    /**
     * @param string $title
     * @param string $expectedSlug
     * @dataProvider slugsProvider
     */
    public function testSlug($title, $expectedSlug)
    {
        $this->assertEquals($expectedSlug, $this->slugger->slug($title));
    }
}
