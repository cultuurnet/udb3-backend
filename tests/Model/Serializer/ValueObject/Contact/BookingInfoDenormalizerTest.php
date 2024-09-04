<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Contact;

use CultuurNet\UDB3\InvalidUrl;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use PHPUnit\Framework\TestCase;

final class BookingInfoDenormalizerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $bookingInfo = new BookingInfo(
            new WebsiteLink(
                new Url('https://www.arboretumkalmthout.be'),
                new TranslatedWebsiteLabel(
                    new Language('nl'),
                    new WebsiteLabel('Koop tickets')
                )
            )
        );

        $bookingInfoArray = [
            'url' => 'https://www.arboretumkalmthout.be',
            'telephoneNumber' => null,
            'emailAddress' => null,
            'availability' => null,
            'urlLabel' => [
                'nl' => 'Koop tickets',
            ],
        ];


        $this->assertEquals(
            $bookingInfo,
            (new BookingInfoDenormalizer())->denormalize($bookingInfoArray, BookingInfo::class)
        );
    }

    /**
     * @test
     */
    public function it_throw_on_malformed_urls(): void
    {
        $bookingInfoArray = [
            'url' => 'https://www.arboretumkalmthout.be%20',
            'urlLabel' => [
                'nl' => 'Koop tickets',
            ],
        ];

        $this->expectException(InvalidUrl::class);
        $this->expectExceptionMessage('The url should match pattern: /\\Ahttp[s]?:\\/\\//');

        (new BookingInfoDenormalizer())->denormalize($bookingInfoArray, BookingInfo::class);
    }
}
