<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\PriceInfo;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\PriceInfo\Tariff;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use Money\Currency;
use Money\Money;
use CultuurNet\UDB3\StringLiteral;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class PriceInfoJSONDeserializer extends JSONDeserializer
{
    /**
     * @var PriceInfoDataValidator
     */
    private $validator;


    public function __construct(PriceInfoDataValidator $validator)
    {
        $this->validator = $validator;

        $assoc = true;
        parent::__construct($assoc);
    }

    /**
     * @return PriceInfoJSONDeserializer
     */
    public function forMainLanguage(Language $language)
    {
        $c = clone $this;
        $c->validator = $c->validator->forMainLanguage($language);
        return $c;
    }

    /**
     * @return PriceInfo
     *
     * @throws MissingValueException
     * @throws \Exception
     */
    public function deserialize(StringLiteral $data)
    {
        /* @var array $data */
        $data = parent::deserialize($data);

        $this->validator->validate($data);

        $basePrice = null;
        $tariffs = [];

        foreach ($data as $itemData) {
            if ($itemData['category'] == 'base') {
                $basePrice = new BasePrice(
                    new Money((int) ($itemData['price'] * 100), new Currency('EUR'))
                );
            } else {
                $tariffs[] = new Tariff(
                    MultilingualString::deserialize($itemData['name']),
                    new Money((int) ($itemData['price'] * 100), new Currency('EUR'))
                );
            }
        }

        $priceInfo = new PriceInfo($basePrice);

        foreach ($tariffs as $tariff) {
            $priceInfo = $priceInfo->withExtraTariff($tariff);
        }

        return $priceInfo;
    }
}
