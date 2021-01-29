<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\UiTPAS\Label;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\UiTPAS\Label\InMemoryUiTPASLabelsRepository;
use PHPUnit\Framework\TestCase;

final class InMemoryUiTPASLabelsRepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_return_a_list_of_labels_created_from_injected_strings(): void
    {
        // Note that these are just fictional examples of UiTPAS card systems ids.
        $given = [
            'c73d78b7-95a7-45b3-bde5-5b2ec7b13afa' => 'Paspartoe',
            'ebd91df0-8ed7-4522-8401-ef5508ad1426' => 'UiTPAS',
            'f23ccb75-190a-4814-945e-c95e83101cc5' => 'UiTPAS Gent',
            '98ce6fbc-fb68-4efc-b8c7-95763cb967dd' => 'UiTPAS Oostende',
            '68f849c0-bf55-4f73-b0f4-e0683bf0c807' => 'UiTPAS regio Aalst',
            'cd6200cc-5b9d-43fd-9638-f6cc27f1c9b8' => 'UiTPAS Dender',
            'd9cf96b6-1256-4760-b66b-1c31152d7db4' => 'UiTPAS Zuidwest',
            'aaf3a58e-2aac-45b3-a9e9-3f3ebf467681' => 'UiTPAS Mechelen',
            '47256d4c-47e8-4046-b9bb-acb166920f76' => 'UiTPAS Kempen',
            '54b5273e-5e0b-4c1e-b33f-93eca55eb472' => 'UiTPAS Maasmechelen',
        ];

        $expected = [
            'c73d78b7-95a7-45b3-bde5-5b2ec7b13afa' => new Label('Paspartoe'),
            'ebd91df0-8ed7-4522-8401-ef5508ad1426' => new Label('UiTPAS'),
            'f23ccb75-190a-4814-945e-c95e83101cc5' => new Label('UiTPAS Gent'),
            '98ce6fbc-fb68-4efc-b8c7-95763cb967dd' => new Label('UiTPAS Oostende'),
            '68f849c0-bf55-4f73-b0f4-e0683bf0c807' => new Label('UiTPAS regio Aalst'),
            'cd6200cc-5b9d-43fd-9638-f6cc27f1c9b8' => new Label('UiTPAS Dender'),
            'd9cf96b6-1256-4760-b66b-1c31152d7db4' => new Label('UiTPAS Zuidwest'),
            'aaf3a58e-2aac-45b3-a9e9-3f3ebf467681' => new Label('UiTPAS Mechelen'),
            '47256d4c-47e8-4046-b9bb-acb166920f76' => new Label('UiTPAS Kempen'),
            '54b5273e-5e0b-4c1e-b33f-93eca55eb472' => new Label('UiTPAS Maasmechelen'),
        ];

        $repository = InMemoryUiTPASLabelsRepository::fromStrings($given);
        $actual = $repository->loadAll();

        $this->assertEquals($expected, $actual);
    }
}
