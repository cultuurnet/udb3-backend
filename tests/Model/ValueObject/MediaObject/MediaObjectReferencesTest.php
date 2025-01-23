<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class MediaObjectReferencesTest extends TestCase
{
    /**
     * @test
     */
    public function it_converts_to_images(): void
    {
        $mediaObject1 = MediaObjectReference::createWithMediaObjectId(
            new Uuid('0bda23b1-3332-4866-b69b-1f1c1d1dbcb4'),
            new Description('Some image description'),
            new CopyrightHolder('Publiq vzw'),
            new Language('en')
        );

        $mediaObject2 = MediaObjectReference::createWithMediaObjectId(
            new Uuid('0bda23b1-3332-4866-b69b-1f1c1d1dbcb4'),
            new Description('Een afbeelding beschrijving'),
            new CopyrightHolder('Publiq vzw'),
            new Language('nl')
        );

        $mediaObjectReferences = new MediaObjectReferences($mediaObject1, $mediaObject2);

        $images = $mediaObjectReferences->toImages();

        $this->assertEquals(
            new Images(
                new Image(
                    new Uuid('0bda23b1-3332-4866-b69b-1f1c1d1dbcb4'),
                    new Language('en'),
                    new Description('Some image description'),
                    new CopyrightHolder('Publiq vzw'),
                ),
                new Image(
                    new Uuid('0bda23b1-3332-4866-b69b-1f1c1d1dbcb4'),
                    new Language('nl'),
                    new Description('Een afbeelding beschrijving'),
                    new CopyrightHolder('Publiq vzw')
                )
            ),
            $images
        );
    }
}
