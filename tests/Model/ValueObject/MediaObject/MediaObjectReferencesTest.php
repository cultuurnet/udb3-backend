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
    public function it_should_return_all_references_either_with_or_without_an_embedded_media_object(): void
    {
        $referenceWithEmbeddedMediaObject = MediaObjectReference::createWithEmbeddedMediaObject(
            new MediaObject(
                new Uuid('0bda23b1-3332-4866-b69b-1f1c1d1dbcb4'),
                MediaObjectType::imageObject()
            ),
            new Description('Some image description'),
            new CopyrightHolder('Publiq vzw'),
            new Language('en')
        );

        $referenceWithoutEmbeddedMediaObject = MediaObjectReference::createWithMediaObjectId(
            new Uuid('0bda23b1-3332-4866-b69b-1f1c1d1dbcb4'),
            new Description('Een afbeelding beschrijving'),
            new CopyrightHolder('Publiq vzw'),
            new Language('nl')
        );

        $references = new MediaObjectReferences(
            $referenceWithEmbeddedMediaObject,
            $referenceWithoutEmbeddedMediaObject
        );

        $expectedReferencesWithEmbeddedMediaObject = new MediaObjectReferences(
            $referenceWithEmbeddedMediaObject
        );

        $expectedReferencesWithoutEmbeddedMediaObject = new MediaObjectReferences(
            $referenceWithoutEmbeddedMediaObject
        );

        $this->assertEquals(
            $expectedReferencesWithEmbeddedMediaObject,
            $references->getReferencesWithEmbeddedMediaObject()
        );

        $this->assertEquals(
            $expectedReferencesWithoutEmbeddedMediaObject,
            $references->getReferencesWithoutEmbeddedMediaObject()
        );
    }
}
