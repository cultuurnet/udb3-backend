<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\Media\Events\MediaObjectCreated;
use CultuurNet\UDB3\Media\Events\MediaObjectRenamed;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class MediaObjectTest extends AggregateRootScenarioTestCase
{
    protected function getAggregateRootClass(): string
    {
        return MediaObject::class;
    }

    /**
     * @test
     */
    public function it_can_be_created(): void
    {
        $fileId = new Uuid('de305d54-75b4-431b-adb2-eb6b9e546014');
        $fileType = new MIMEType('image/png');
        $description = new Description('The Gleaners');
        $copyrightHolder = new CopyrightHolder('Jean-François Millet');
        $location = new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png');
        $language = new Language('en');

        $this->scenario
            ->withAggregateId($fileId->toString())
            ->when(
                function () use ($fileId, $fileType, $description, $copyrightHolder, $location, $language) {
                    return MediaObject::create(
                        $fileId,
                        $fileType,
                        $description,
                        $copyrightHolder,
                        $location,
                        $language
                    );
                }
            )
            ->then(
                [
                    new MediaObjectCreated(
                        $fileId,
                        $fileType,
                        $description,
                        $copyrightHolder,
                        $location,
                        $language
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_keep_track_of_media_object_meta_data(): void
    {
        $mediaObject = MediaObject::create(
            new Uuid('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en'),
            true
        );

        $this->assertEquals(
            new MIMEType('image/png'),
            $mediaObject->getMimeType()
        );

        $this->assertEquals(
            new Uuid('de305d54-75b4-431b-adb2-eb6b9e546014'),
            $mediaObject->getMediaObjectId()
        );

        $this->assertEquals(
            new Description('The Gleaners (Generated with AI)'),
            $mediaObject->getDescription()
        );

        $this->assertEquals(
            new CopyrightHolder('Jean-François Millet'),
            $mediaObject->getCopyrightHolder()
        );

        $this->assertEquals(
            new Language('en'),
            $mediaObject->getLanguage()
        );

        $this->assertTrue($mediaObject->isAiGenerated());
    }

    /**
     * @test
     */
    public function it_adds_the_ai_generated_suffix_to_the_description_of_an_ai_generated_image(): void
    {
        $fileId = new Uuid('de305d54-75b4-431b-adb2-eb6b9e546014');
        $fileType = new MIMEType('image/png');
        $copyrightHolder = new CopyrightHolder('Jean-François Millet');
        $location = new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png');
        $language = new Language('nl');

        $this->scenario
            ->withAggregateId($fileId->toString())
            ->when(
                fn () => MediaObject::create(
                    $fileId,
                    $fileType,
                    new Description('The Gleaners'),
                    $copyrightHolder,
                    $location,
                    $language,
                    true
                )
            )
            ->then(
                [
                    new MediaObjectCreated(
                        $fileId,
                        $fileType,
                        new Description('The Gleaners (Gegenereerd met AI)'),
                        $copyrightHolder,
                        $location,
                        $language,
                        true
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_has_no_ai_generated_value_by_default(): void
    {
        $mediaObject = MediaObject::create(
            new Uuid('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );

        $this->assertNull($mediaObject->isAiGenerated());
    }

    /**
     * @test
     */
    public function it_can_be_renamed(): void
    {
        $fileId = new Uuid('de305d54-75b4-431b-adb2-eb6b9e546014');

        $this->scenario
            ->withAggregateId($fileId->toString())
            ->given([
                new MediaObjectCreated(
                    $fileId,
                    new MIMEType('image/png'),
                    new Description('The Gleaners'),
                    new CopyrightHolder('Jean-François Millet'),
                    new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
                    new Language('en')
                ),
            ])
            ->when(
                fn (MediaObject $mediaObject) => $mediaObject->rename(
                    new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.ai.png')
                )
            )
            ->then([
                new MediaObjectRenamed(
                    $fileId,
                    new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.ai.png')
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_is_not_renamed_to_the_same_source_location(): void
    {
        $fileId = new Uuid('de305d54-75b4-431b-adb2-eb6b9e546014');

        $this->scenario
            ->withAggregateId($fileId->toString())
            ->given([
                new MediaObjectCreated(
                    $fileId,
                    new MIMEType('image/png'),
                    new Description('The Gleaners'),
                    new CopyrightHolder('Jean-François Millet'),
                    new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
                    new Language('en')
                ),
            ])
            ->when(
                fn (MediaObject $mediaObject) => $mediaObject->rename(
                    new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png')
                )
            )
            ->then([]);
    }
}
