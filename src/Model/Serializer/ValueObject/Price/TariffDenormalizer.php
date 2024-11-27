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
    private bool $forBasePrice;

    public function __construct(bool $forBasePrice)
    {
        $this->forBasePrice = $forBasePrice;
    }

    public function denormalize($data, $class, $format = null, array $context = []): Tariff
    {
        if ($this->forBasePrice) {
            return Tariff::createBasePrice(
                MoneyFactory::createFromCents($data['price'], new Currency($data['currency']))
            );
        }

        /** @var TranslatedTariffName $tariffName */
        $tariffName = (new TranslatedTariffNameDenormalizer())->denormalize(
            $data['name'],
            TranslatedTariffName::class
        );

        return new Tariff(
            $tariffName,
            MoneyFactory::createFromCents($data['price'], new Currency($data['currency']))
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === Tariff::class;
    }
}
