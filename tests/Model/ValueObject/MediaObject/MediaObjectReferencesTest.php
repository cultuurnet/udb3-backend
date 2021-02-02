<?php

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

class MediaObjectReferencesTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_return_all_references_either_with_or_without_an_embedded_media_object()
    {
        $referenceWithEmbeddedMediaObject = MediaObjectReference::createWithEmbeddedMediaObject(
            new MediaObject(
                new UUID('0bda23b1-3332-4866-b69b-1f1c1d1dbcb4'),
                MediaObjectType::imageObject(),
                new Url('http://publiq.be/test.png'),
                new Url('http://publiq.be/test.png?w=100&h=100')
            ),
            new Description('Some image description'),
            new CopyrightHolder('Publiq vzw'),
            new Language('en')
        );

        $referenceWithoutEmbeddedMediaObject = MediaObjectReference::createWithMediaObjectId(
            new UUID('0bda23b1-3332-4866-b69b-1f1c1d1dbcb4'),
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
