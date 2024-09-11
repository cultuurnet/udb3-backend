<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GoogleMapUrlGenerator extends AbstractExtension
{
    public const STATIC_MAP_URL = 'https://maps.googleapis.com/maps/api/staticmap';

    private string $googleMapsApiKey;

    public function __construct(string $googleMapsApiKey)
    {
        $this->googleMapsApiKey = $googleMapsApiKey;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction(
                'googleMapUrl',
                function (array $coordinates, int $widthInPixels, int $heightInPixels) {
                    return $this->generateGoogleMapUrl($coordinates, $widthInPixels, $heightInPixels);
                }
            ),
        ];
    }

    /**
     * @param string[] $markers
     */
    public function generateGoogleMapUrl(array $markers, int $widthInPixels, int $heightInPixels): string
    {
        $markers = array_unique($markers);

        $url = self::STATIC_MAP_URL;
        $url .= '?size=' . $widthInPixels . 'x' . $heightInPixels . '&scale=2';

        foreach ($markers as $marker) {
            $url .= '&markers=' . $marker;
        }

        $url .= '&key=' . $this->googleMapsApiKey;

        return $url;
    }
}
