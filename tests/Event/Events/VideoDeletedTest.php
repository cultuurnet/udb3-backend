<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event\Events;

use CultuurNet\UDB3\Event\Events\VideoDeleted;
use PHPUnit\Framework\TestCase;

final class VideoDeletedTest extends TestCase
{
    private VideoDeleted $videoDeleted;

    private array $videoDeletedAsArray;

    protected function setUp(): void
    {
        $this->videoDeleted = new VideoDeleted(
            '8164319a-f4c2-44ea-b666-f4dea4542628',
            '00cf030c-af27-4339-acea-f60b40aaaf0a'
        );

        $this->videoDeletedAsArray = [
            'item_id' => '8164319a-f4c2-44ea-b666-f4dea4542628',
            'video_id' => '00cf030c-af27-4339-acea-f60b40aaaf0a',
        ];
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $this->assertEquals(
            $this->videoDeleted->serialize(),
            $this->videoDeletedAsArray
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $this->assertEquals(
            VideoDeleted::deserialize($this->videoDeletedAsArray),
            $this->videoDeleted
        );
    }
}
