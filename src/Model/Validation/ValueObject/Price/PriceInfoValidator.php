<?php

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Price;

use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Callback;
use Respect\Validation\Rules\Each;
use Respect\Validation\Validator;

class PriceInfoValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new ArrayType(),
            new Each(
                (new TariffValidator())->setName('each priceInfo entry')
            ),
            (new Callback(
                function (array $entries) {
                    $entries = array_filter(
                        $entries,
                        function ($tariff) {
                            return $tariff['category'] == 'base';
                        }
                    );

                    return count($entries) === 1;
                }
            ))->setTemplate('priceInfo must contain exactly 1 base price'),
        ];

        parent::__construct($rules);
    }
}
