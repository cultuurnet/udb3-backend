<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\PriceInfo;

use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\PriceInfo\Tariff;
use CultuurNet\UDB3\Symfony\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use ValueObjects\Money\Currency;
use ValueObjects\StringLiteral\StringLiteral;

class PriceInfoJSONDeserializer extends JSONDeserializer
{
    /**
     * @var DataValidatorInterface
     */
    private $validator;

    /**
     * @param DataValidatorInterface|null $validator
     */
    public function __construct(DataValidatorInterface $validator = null)
    {
        if (!$validator) {
            $validator = new PriceInfoDataValidator();
        }

        $this->validator = $validator;

        $assoc = true;
        parent::__construct($assoc);
    }

    /**
     * @param Language $language
     * @return PriceInfoJSONDeserializer
     */
    public function forMainLanguage(Language $language)
    {
        if (!($this->validator instanceof PriceInfoDataValidator)) {
            return $this;
        }

        $c = clone $this;
        $c->validator = $c->validator->forMainLanguage($language);
        return $c;
    }

    /**
     * @param StringLiteral $data
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
                    Price::fromFloat((float) $itemData['price']),
                    Currency::fromNative('EUR')
                );
            } else {
                $tariffs[] = new Tariff(
                    MultilingualString::deserialize($itemData['name']),
                    Price::fromFloat((float) $itemData['price']),
                    Currency::fromNative('EUR')
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
