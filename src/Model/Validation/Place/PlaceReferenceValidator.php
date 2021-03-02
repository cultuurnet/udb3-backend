<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\Place;

use CultuurNet\UDB3\Model\Validation\ValueObject\Geography\TranslatedAddressValidator;
use Respect\Validation\Rules\AlwaysValid;
use Respect\Validation\Rules\Key;
use Respect\Validation\Rules\OneOf;
use Respect\Validation\Rules\When;
use Respect\Validation\Validator;

class PlaceReferenceValidator extends Validator
{
    public function __construct()
    {
        // First check that either @id or address (dummy locations) are present,
        // then validate one or both depending on which are present.
        // Don't check + validate at the same time to prevent cryptic error
        // messages.
        $idSet = (new Key('@id', new AlwaysValid(), true))
            ->setName('location @id');

        $addressSet = (new Key('address', new AlwaysValid(), true))
            ->setName('location address');

        $rules = [
            new OneOf($idSet, $addressSet),
            new When(
                $idSet,
                (new Key('@id', new PlaceIDValidator(), true))
                    ->setName('location @id'),
                new AlwaysValid()
            ),
            new When(
                $addressSet,
                (new Key('address', new TranslatedAddressValidator(), true))
                    ->setName('location address'),
                new AlwaysValid()
            ),
        ];

        parent::__construct($rules);
    }
}
