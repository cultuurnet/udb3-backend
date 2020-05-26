<?php

namespace CultuurNet\UDB3\Media\Commands;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class UploadImageTest extends TestCase
{
    /**
     * @var UploadImage
     */
    private $uploadImage;

    protected function setUp()
    {
        $this->uploadImage = new UploadImage(
            UUID::fromNative('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            StringLiteral::fromNative('description'),
            StringLiteral::fromNative('copyright'),
            StringLiteral::fromNative('/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );
    }

    /**
     * @test
     */
    public function it_stores_a_file_id()
    {
        $this->assertEquals(
            UUID::fromNative('de305d54-75b4-431b-adb2-eb6b9e546014'),
            $this->uploadImage->getFileId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_mime_type()
    {
        $this->assertEquals(
            new MIMEType('image/png'),
            $this->uploadImage->getMimeType()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_description()
    {
        $this->assertEquals(
            StringLiteral::fromNative('description'),
            $this->uploadImage->getDescription()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_copyright()
    {
        $this->assertEquals(
            StringLiteral::fromNative('copyright'),
            $this->uploadImage->getCopyrightHolder()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_file_path()
    {
        $this->assertEquals(
            StringLiteral::fromNative('/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            $this->uploadImage->getFilePath()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_language()
    {
        $this->assertEquals(
            new Language('en'),
            $this->uploadImage->getLanguage()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_item_id()
    {
        $this->assertEquals(
            'de305d54-75b4-431b-adb2-eb6b9e546014',
            $this->uploadImage->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_permission()
    {
        $this->assertEquals(
            Permission::MEDIA_UPLOADEN(),
            $this->uploadImage->getPermission()
        );
    }
}
