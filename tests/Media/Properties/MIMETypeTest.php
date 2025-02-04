<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Properties;

use PHPUnit\Framework\TestCase;

final class MIMETypeTest extends TestCase
{
    public function it_can_be_created_from_a_string(): void
    {
        $mimeType = new MIMEType('image/jpeg');
        $this->assertEquals('image/jpeg', $mimeType->toString());
    }

    public function it_can_be_created_from_a_subtype(): void
    {
        $mimeType = MIMEType::fromSubtype('jpeg');
        $this->assertEquals('image/jpeg', $mimeType->toString());
    }

    public function it_returns_a_filename_extension(): void
    {
        $mimeType = new MIMEType('image/jpeg');
        $this->assertEquals('jpeg', $mimeType->getFilenameExtension());
    }

    public function it_validates_the_subtype(): void
    {
        $this->expectException(UnsupportedMIMETypeException::class);
        $mimeType = MIMEType::fromSubtype('unsupported');
    }
}
