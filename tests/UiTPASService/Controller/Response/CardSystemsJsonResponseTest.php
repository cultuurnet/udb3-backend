<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller\Response;

use CultureFeed_Uitpas_CardSystem;
use CultureFeed_Uitpas_DistributionKey;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\TestCase;

class CardSystemsJsonResponseTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_encode_the_injected_card_systems_to_json(): void
    {
        $cardSystem1 = new CultureFeed_Uitpas_CardSystem();
        $cardSystem1->id = 1;
        $cardSystem1->name = 'Card system 1';

        $dk1 = new CultureFeed_Uitpas_DistributionKey();
        $dk1->id = 1;
        $dk1->name = 'Distribution key 1';

        $dk2 = new CultureFeed_Uitpas_DistributionKey();
        $dk2->id = 2;
        $dk2->name = 'Distribution key 2';

        $cardSystem1->distributionKeys = [$dk1, $dk2];

        $cardSystem2 = new CultureFeed_Uitpas_CardSystem();
        $cardSystem2->id = 2;
        $cardSystem2->name = 'Card system 2';

        $dk3 = new CultureFeed_Uitpas_DistributionKey();
        $dk3->id = 3;
        $dk3->name = 'Distribution key 3';

        $cardSystem2->distributionKeys = [$dk3];

        $cardSystem3 = new CultureFeed_Uitpas_CardSystem();
        $cardSystem3->id = 3;
        $cardSystem3->name = 'Card system 3';

        $cardSystems = [
            $cardSystem1,
            $cardSystem2,
            $cardSystem3,
        ];

        $response = new CardSystemsJsonResponse($cardSystems);

        $expected = Json::decode(SampleFiles::read(__DIR__ . '/data/cardSystems.json'));
        $actual = Json::decode($response->getBody()->getContents());

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_an_empty_json_array_if_no_card_systems_were_injected(): void
    {
        $response = new CardSystemsJsonResponse([]);

        $expected = '[]';
        $actual = $response->getBody()->getContents();

        $this->assertEquals($expected, $actual);
    }
}
