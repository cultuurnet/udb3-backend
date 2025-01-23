<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class MediaObjectReferenceTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_creatable_with_a_media_object_id(): void
    {
        $id = new Uuid('0bda23b1-3332-4866-b69b-1f1c1d1dbcb4');
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
    public function it_should_return_a_copy_with_an_updated_description(): void
    {
        $id = new Uuid('0bda23b1-3332-4866-b69b-1f1c1d1dbcb4');
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
    public function it_should_return_a_copy_with_an_updated_copyright_holder(): void
    {
        $id = new Uuid('0bda23b1-3332-4866-b69b-1f1c1d1dbcb4');
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
