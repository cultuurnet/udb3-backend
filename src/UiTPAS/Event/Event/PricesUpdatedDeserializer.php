<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event\Event;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\MoneyFactory;
use CultuurNet\UDB3\StringLiteral;
use Money\Currency;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use stdClass;

final class PricesUpdatedDeserializer extends JSONDeserializer
{
    private array $schema = [
        'type' => 'object',
        'properties' => [
            'cdbid' => [
                'type' => 'string',
            ],
            'tariffs' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'minLength' => 1,
                        ],
                        'price' => [
                            'type' => 'number',
                            'format' => 'float',
                        ],
                    ],
                    'required' => [
                        'name',
                        'price',
                    ],
                ],
            ],
        ],
        'required' => [
            'cdbid',
            'tariffs',
        ],
    ];

    public function deserialize(StringLiteral $data): PricesUpdated
    {
        /** @var stdClass $dto */
        $dto = parent::deserialize($data);

        $validator = new Validator();
        $result = $validator->validate($dto, Json::encode($this->schema));

        if (!$result->isValid()) {
            $errors = (new ErrorFormatter())->format($result->error());
            throw new \InvalidArgumentException(reset($errors)[0] . ' (JsonPointer: ' . key($errors) . ').');
        }

        $tariffs = [];
        foreach ($dto->tariffs as $tariff) {
            $name = $tariff->name;

            $tariffs[] = new Tariff(
                new TranslatedTariffName(new Language('nl'), new TariffName($name)),
                MoneyFactory::createFromFloat($tariff->price, new Currency('EUR'))
            );
        }

        return new PricesUpdated($dto->cdbid, new Tariffs(...$tariffs));
    }
}
