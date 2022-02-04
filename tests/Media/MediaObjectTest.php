<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Events\MediaObjectCreated;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
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
        $description = new StringLiteral('The Gleaners');
        $copyrightHolder = new CopyrightHolder('Jean-François Millet');
        $location = Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png');
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
    public function it_should_keep_track_of_media_object_meta_data()
    {
        $mediaObject = MediaObject::create(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new StringLiteral('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
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
            new StringLiteral('The Gleaners'),
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
