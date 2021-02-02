<?php

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

class MediaObjectReferenceTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_creatable_with_a_media_object_id()
    {
        $id = new UUID('0bda23b1-3332-4866-b69b-1f1c1d1dbcb4');
        $description = new Description('Some image description');
        $copyrightHolder = new CopyrightHolder('Publiq vzw');
        $language = new Language('en');

        $mediaObjectReference = MediaObjectReference::createWithMediaObjectId(
            $id,
            $description,
            $copyrightHolder,
            $language
        );

        $this->assertEquals($id, $mediaObjectReference->getMediaObjectId());
        $this->assertEquals($description, $mediaObjectReference->getDescription());
        $this->assertEquals($copyrightHolder, $mediaObjectReference->getCopyrightHolder());
        $this->assertEquals($language, $mediaObjectReference->getLanguage());
        $this->assertNull($mediaObjectReference->getEmbeddedMediaObject());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_with_an_embedded_media_object()
    {
        $id = new UUID('0bda23b1-3332-4866-b69b-1f1c1d1dbcb4');
        $type = MediaObjectType::imageObject();
        $contentUrl = new Url('http://publiq.be/test.png');
        $thumbnailUrl = new Url('http://publiq.be/test.png?w=100&h=100');

        $mediaObject = new MediaObject($id, $type, $contentUrl, $thumbnailUrl);

        $description = new Description('Some image description');
        $copyrightHolder = new CopyrightHolder('Publiq vzw');
        $language = new Language('en');

        $mediaObjectReference = MediaObjectReference::createWithEmbeddedMediaObject(
            $mediaObject,
            $description,
            $copyrightHolder,
            $language
        );

        $this->assertEquals($id, $mediaObjectReference->getMediaObjectId());
        $this->assertEquals($description, $mediaObjectReference->getDescription());
        $this->assertEquals($copyrightHolder, $mediaObjectReference->getCopyrightHolder());
        $this->assertEquals($language, $mediaObjectReference->getLanguage());
        $this->assertEquals($mediaObject, $mediaObjectReference->getEmbeddedMediaObject());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_description()
    {
        $id = new UUID('0bda23b1-3332-4866-b69b-1f1c1d1dbcb4');
        $description = new Description('Some image description');
        $copyrightHolder = new CopyrightHolder('Publiq vzw');
        $language = new Language('en');

        $mediaObjectReference = MediaObjectReference::createWithMediaObjectId(
            $id,
            $description,
            $copyrightHolder,
            $language
        );

        $updatedDescription = new Description('Updated image description');
        $updatedMediaObjectReference = $mediaObjectReference->withDescription($updatedDescription);

        $this->assertNotEquals($mediaObjectReference, $updatedMediaObjectReference);
        $this->assertEquals($description, $mediaObjectReference->getDescription());
        $this->assertEquals($updatedDescription, $updatedMediaObjectReference->getDescription());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_copyright_holder()
    {
        $id = new UUID('0bda23b1-3332-4866-b69b-1f1c1d1dbcb4');
        $description = new Description('Some image description');
        $copyrightHolder = new CopyrightHolder('Publiq vzw');
        $language = new Language('en');

        $mediaObjectReference = MediaObjectReference::createWithMediaObjectId(
            $id,
            $description,
            $copyrightHolder,
            $language
        );

        $updatedCopyrightHolder = new CopyrightHolder('CultuurNet vzw');
        $updatedMediaObjectReference = $mediaObjectReference->withCopyrightHolder($updatedCopyrightHolder);

        $this->assertNotEquals($mediaObjectReference, $updatedMediaObjectReference);
        $this->assertEquals($copyrightHolder, $mediaObjectReference->getCopyrightHolder());
        $this->assertEquals($updatedCopyrightHolder, $updatedMediaObjectReference->getCopyrightHolder());
    }
}
