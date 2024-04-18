<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Money\Currency;
use Money\Money;

final class ParsedPriceForATheater
{
    private int $basePrice;

    private int $discountPrice;

    private int $studentPrice;

    private int $surchargeForLongMovie;

    private int $surchargeFor3D;

    public function __construct(
        int $basePrice,
        int $discountPrice,
        int $studentPrice,
        int $surchargeForLongMovie,
        int $surchargeFor3D
    ) {
        $this->basePrice = $basePrice;
        $this->discountPrice = $discountPrice;
        $this->studentPrice = $studentPrice;
        $this->surchargeForLongMovie = $surchargeForLongMovie;
        $this->surchargeFor3D = $surchargeFor3D;
    }

    public function getBaseTariff(bool $isLong, bool $is3D): Tariff
    {
        $basePrice = $isLong ? $this->basePrice + $this->surchargeForLongMovie : $this->basePrice;
        $basePrice = $is3D ? $basePrice + $this->surchargeFor3D : $basePrice;
        return new Tariff(
            new TranslatedTariffName(
                new Language('nl'),
                new TariffName('Basistarief')
            ),
            new Money($basePrice, new Currency('EUR'))
        );
    }

    public function getOtherTariffs(bool $isLong, bool $is3D): Tariffs
    {
        $studentPrice = $isLong ? $this->studentPrice + $this->surchargeForLongMovie : $this->studentPrice;
        $studentPrice = $is3D ? $studentPrice + $this->surchargeFor3D : $studentPrice;

        $discountPrice = $isLong ? $this->discountPrice + $this->surchargeForLongMovie : $this->discountPrice;
        $discountPrice = $is3D ? $discountPrice + $this->surchargeFor3D : $discountPrice;

        return new Tariffs(
            new Tariff(
                new TranslatedTariffName(
                    new Language('nl'),
                    new TariffName('Kinepolis Student Card')
                ),
                new Money($studentPrice, new Currency('EUR'))
            ),
            new Tariff(
                new TranslatedTariffName(
                    new Language('nl'),
                    new TariffName('Kortingstarief')
                ),
                new Money($discountPrice, new Currency('EUR'))
            ),
        );
    }
}
