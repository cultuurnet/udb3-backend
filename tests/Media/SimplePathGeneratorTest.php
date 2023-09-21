<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

final class SimplePathGeneratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_generate_the_file_path_for_a_given_id_and_extentions(): void
    {
        $generator = new SimplePathGenerator();
        $fileId = new UUID('de305d54-75b4-431b-adb2-eb6b9e546014');
        $extension = 'png';
        $expectedPath = 'de305d54-75b4-431b-adb2-eb6b9e546014.png';

        $path = $generator->path($fileId, $extension);

        $this->assertEquals($expectedPath, $path);
    }
}
