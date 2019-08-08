<?php

namespace CultuurNet\UDB3\Model\Import\Validation\Place;

use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

class PlaceReferenceExistsValidator extends Validator
{
    public function __construct(
        UUIDParser $placeIDParser,
        DocumentRepositoryInterface $placeDocumentRepository
    ) {
        $rules = [
            new Key('@id', new PlaceIDExistsValidator($placeIDParser, $placeDocumentRepository), false),
        ];

        parent::__construct($rules);
    }
}
