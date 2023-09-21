<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Commands;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

final class UploadImageTest extends TestCase
{
    /**
     * @var UploadImage
     */
    private $uploadImage;

    protected function setUp(): void
    {
        $this->uploadImage = new UploadImage(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('description'),
            new CopyrightHolder('copyright'),
            '/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png',
            new Language('en')
        );
    }

    /**
     * @test
     */
    public function it_stores_a_file_id(): void
    {
        $this->assertEquals(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            $this->uploadImage->getFileId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_mime_type(): void
    {
        $this->assertEquals(
            new MIMEType('image/png'),
            $this->uploadImage->getMimeType()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_description(): void
    {
        $this->assertEquals(
            new Description('description'),
            $this->uploadImage->getDescription()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_copyright(): void
    {
        $this->assertEquals(
            new CopyrightHolder('copyright'),
            $this->uploadImage->getCopyrightHolder()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_file_path(): void
    {
        $this->assertEquals(
            StringLiteral::fromNative('/uploads/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            $this->uploadImage->getFilePath()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_language(): void
    {
        $this->assertEquals(
            new Language('en'),
            $this->uploadImage->getLanguage()
        );
    }
}
