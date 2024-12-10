<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\Media\Events\MediaObjectCreated;
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
            new Language('en')
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
            new Description('The Gleaners'),
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
    }
}
