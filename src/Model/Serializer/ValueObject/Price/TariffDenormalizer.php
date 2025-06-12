<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\MoneyFactory;
use Money\Currency;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class TariffDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): Tariff
    {
        /** @var TranslatedTariffName $tariffName */
        $tariffName = (new TranslatedTariffNameDenormalizer())->denormalize(
            $data['name'],
            TranslatedTariffName::class
        );

        $tariff = new Tariff(
            $tariffName,
            MoneyFactory::create(
                $data['price'],
                new Currency($data['priceCurrency'])
            )
        );
        return isset($data['groupPrice']) ? $tariff->withGroupPrice($data['groupPrice']) : $tariff;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === Tariff::class;
    }
}
