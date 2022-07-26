<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event\Event;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\StringLiteral;
use Money\Currency;
use Money\Money;

final class PricesUpdatedDeserializer extends JSONDeserializer
{
    public function deserialize(StringLiteral $data): PricesUpdated
    {
        $dto = parent::deserialize($data);

        if (!isset($dto->cdbid)) {
            throw new \InvalidArgumentException('Missing cdbid property.');
        }

        $eventId = $dto->cdbid;

        if (!isset($dto->tariffs)) {
            throw new \InvalidArgumentException('Missing tariffs property.');
        }

        if (!is_array($dto->tariffs)) {
            throw new \InvalidArgumentException('Expected tariffs property to be an array.');
        }

        $tariffs = [];
        foreach ($dto->tariffs as $tariff) {
            if (empty($tariff->name)) {
                throw new \InvalidArgumentException('Encountered tariff entry without valid name.');
            }
            $name = $tariff->name;
            if (!isset($tariff->price) || !is_numeric($tariff->price)) {
                throw new \InvalidArgumentException('Encountered tariff entry without valid price.');
            }
            $price = (int) ($tariff->price * 100);

            $tariffs[] = new Tariff(
                new TranslatedTariffName(new Language('nl'), new TariffName($name)),
                new Money(
                    $price,
                    new Currency('EUR')
                )
            );
        }

        return new PricesUpdated($eventId, new Tariffs(...$tariffs));
    }
}
