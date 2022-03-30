<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Yaml;

use PHPUnit\Framework\TestCase;

final class YamlExcludedLabelsRepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_a_list_of_labels_uuids(): void
    {
        $excludedLabelsRepository = new YamlExcludedLabelsRepository([
            '2225d5ae-3b0d-4f6c-9cf9-4d39f80b90c5',
            '1adfe1b6-e05c-46d1-8dca-609fce67ef7b',
        ]);

        $this->assertEquals(
            [
                '2225d5ae-3b0d-4f6c-9cf9-4d39f80b90c5',
                '1adfe1b6-e05c-46d1-8dca-609fce67ef7b',
            ],
            $excludedLabelsRepository->getAll()
        );
    }
}
