<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

final class ImageTest extends TestCase
{
    private Image $image;

    protected function setUp(): void
    {
        $this->image = new Image(
            new Uuid('cf539408-bba9-4e77-9f85-72019013db37'),
            new Language('nl'),
            new Description('Description of the image'),
            new CopyrightHolder('publiq')
        );
    }

    /**
     * @test
     */
    public function it_stores_an_id(): void
    {
        $this->assertEquals(new Uuid('cf539408-bba9-4e77-9f85-72019013db37'), $this->image->getId());
    }

    /**
     * @test
     */
    public function it_stores_a_language(): void
    {
        $this->assertEquals(new Language('nl'), $this->image->getLanguage());
    }

    /**
     * @test
     */
    public function it_stores_a_description(): void
    {
        $this->assertEquals(new Description('Description of the image'), $this->image->getDescription());
    }

    /**
     * @test
     */
    public function it_stores_a_copyright_holder(): void
    {
        $this->assertEquals(new CopyrightHolder('publiq'), $this->image->getCopyrightHolder());
    }

    /**
     * @test
     * @dataProvider imagesDataProvider
     */
    public function it_can_compare_images(Image $image1, Image $image2, bool $equal): void
    {
        $this->assertEquals($equal, $image1->sameAs($image2));
    }

    public function imagesDataProvider(): array
    {
        $image = new Image(
            new Uuid('b40a7a77-49b7-495f-b2f2-62ebbeb818b9'),
            new Language('en'),
            new Description('Image description'),
            new CopyrightHolder('Image copyright holder')
        );

        return [
            'Equal images' => [
                $image,
                $image,
                true,
            ],
            'Different id' => [
                $image,
                new Image(
                    new Uuid('d7da6e1d-f594-4614-9d77-157913e32d5b'),
                    new Language('en'),
                    new Description('Image description'),
                    new CopyrightHolder('Image copyright holder')
                ),
                false,
            ],
            'Different language' => [
                $image,
                new Image(
                    new Uuid('b40a7a77-49b7-495f-b2f2-62ebbeb818b9'),
                    new Language('nl'),
                    new Description('Image description'),
                    new CopyrightHolder('Image copyright holder')
                ),
                false,
            ],
            'Different description' => [
                $image,
                new Image(
                    new Uuid('b40a7a77-49b7-495f-b2f2-62ebbeb818b9'),
                    new Language('en'),
                    new Description('Different image description'),
                    new CopyrightHolder('Image copyright holder')
                ),
                false,
            ],
            'Different copyright holder' => [
                $image,
                new Image(
                    new Uuid('b40a7a77-49b7-495f-b2f2-62ebbeb818b9'),
                    new Language('en'),
                    new Description('Image description'),
                    new CopyrightHolder('Different image copyright holder')
                ),
                false,
            ],
            'Everything different' => [
                $image,
                new Image(
                    new Uuid('d7da6e1d-f594-4614-9d77-157913e32d5b'),
                    new Language('nl'),
                    new Description('Different image description'),
                    new CopyrightHolder('Different image copyright holder')
                ),
                false,
            ],
        ];
    }
}
