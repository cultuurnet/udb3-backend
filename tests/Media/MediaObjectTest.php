<?php

namespace CultuurNet\UDB3\Media;

use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Events\MediaObjectCreated;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class MediaObjectTest extends AggregateRootScenarioTestCase
{
    /**
     * @inheritdoc
     */
    protected function getAggregateRootClass()
    {
        return MediaObject::class;
    }

    /**
     * @test
     */
    public function it_can_be_created()
    {
        $fileId = new UUID('de305d54-75b4-431b-adb2-eb6b9e546014');
        $fileType = new MIMEType('image/png');
        $description = new StringLiteral('sexy ladies without clothes');
        $copyrightHolder = new StringLiteral('Bart Ramakers');
        $location = Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png');
        $language = new Language('en');

        $this->scenario
            ->withAggregateId($fileId->toNative())
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
    public function it_should_keep_track_of_media_object_meta_data()
    {
        $mediaObject = MediaObject::create(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new StringLiteral('sexy ladies without clothes'),
            new StringLiteral('Bart Ramakers'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );

        $this->assertEquals(
            new MIMEType('image/png'),
            $mediaObject->getMimeType()
        );

        $this->assertEquals(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            $mediaObject->getMediaObjectId()
        );

        $this->assertEquals(
            new StringLiteral('sexy ladies without clothes'),
            $mediaObject->getDescription()
        );

        $this->assertEquals(
            new StringLiteral('Bart Ramakers'),
            $mediaObject->getCopyrightHolder()
        );

        $this->assertEquals(
            new Language('en'),
            $mediaObject->getLanguage()
        );
    }
}
