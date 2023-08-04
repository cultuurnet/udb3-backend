<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateEducationalDescription;
use CultuurNet\UDB3\Organizer\Serializers\UpdateEducationalDescriptionDenormalizer;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UpdateEducationalDescriptionDenormalizerTest extends TestCase
{
    const DESCRIPTION = 'value';
    private string $id;
    private Language $mainLanguage;
    private UpdateEducationalDescriptionDenormalizer $denormalizer;

    public function setUp(): void
    {
        $this->id = '18eab5bf-09bf-4521-a8b4-c0f4a585c096';
        $this->mainLanguage = new Language('en');
        $this->denormalizer = new UpdateEducationalDescriptionDenormalizer($this->id, $this->mainLanguage);
    }

    /**
     * @test
     * @group educationalDescription
     */
    public function it_supports_the_update_educational_description(): void
    {
        $this->assertFalse($this->denormalizer->supportsDenormalization([], Organizer::class));
        $this->assertTrue($this->denormalizer->supportsDenormalization([], UpdateEducationalDescription::class));
    }

    /**
     * @test
     * @group educationalDescription
     */
    public function it_converts_to_an_educational_description_command(): void
    {
        $command = $this->denormalizer->denormalize(['educationalDescription' => self::DESCRIPTION]);

        $this->assertEquals($this->id, $command->getItemId());
        $this->assertEquals($this->mainLanguage, $command->getLanguage());
        $this->assertEquals(new Description(self::DESCRIPTION), $command->getEducationalDescription());
        $this->assertEquals(Permission::organisatiesBewerken(), $command->getPermission());
    }

    /**
     * @test
     * @group educationalDescription
     */
    public function it_fails_to_convert_an_invalid_request_body(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $command = $this->denormalizer->denormalize(['SO_WRONG' => self::DESCRIPTION]);
    }
}