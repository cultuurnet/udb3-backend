<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

final class AiGeneratedImageFileNameTest extends TestCase
{
    private const IMAGE_ID = '6a1ae8b4-3b2d-4b8e-9f0a-1c2d3e4f5a6b';

    /**
     * @test
     * @dataProvider locationProvider
     */
    public function it_applies_the_ai_generated_file_name(
        string $location,
        ?bool $isAiGenerated,
        string $expected
    ): void {
        $this->assertEquals(
            $expected,
            AiGeneratedImageFileName::apply($location, new Uuid(self::IMAGE_ID), $isAiGenerated)
        );
    }

    public function locationProvider(): array
    {
        $id = self::IMAGE_ID;

        return [
            'file name is renamed when ai generated' => ["$id.jpeg", true, "$id.ai.jpeg"],
            'path is renamed when ai generated' => ["uploads/$id.png", true, "uploads/$id.ai.png"],
            'url is renamed when ai generated' => [
                "https://images.uitdatabank.dev/$id.jpeg",
                true,
                "https://images.uitdatabank.dev/$id.ai.jpeg",
            ],
            'ai file name is not changed when ai generated' => ["$id.ai.jpeg", true, "$id.ai.jpeg"],
            'ai url is renamed when not ai generated' => [
                "https://images.uitdatabank.dev/$id.ai.jpeg",
                false,
                "https://images.uitdatabank.dev/$id.jpeg",
            ],
            'file name is not changed when not ai generated' => ["$id.jpeg", false, "$id.jpeg"],
            'ai file name is not changed when ai generated is unknown' => ["$id.ai.jpeg", null, "$id.ai.jpeg"],
            'file name is not changed when ai generated is unknown' => ["$id.jpeg", null, "$id.jpeg"],
            'file name of another image is not changed' => [
                'https://images.uitdatabank.dev/9a1ae8b4-3b2d-4b8e-9f0a-1c2d3e4f5a6b.jpeg',
                true,
                'https://images.uitdatabank.dev/9a1ae8b4-3b2d-4b8e-9f0a-1c2d3e4f5a6b.jpeg',
            ],
            'legacy url is not changed' => [
                'https://media.uitdatabank.be/20160101/logo.jpeg',
                true,
                'https://media.uitdatabank.be/20160101/logo.jpeg',
            ],
            'file name with a prefix is not changed' => [
                "https://images.uitdatabank.dev/prefix-$id.jpeg",
                true,
                "https://images.uitdatabank.dev/prefix-$id.jpeg",
            ],
        ];
    }
}
