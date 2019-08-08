<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Role;

use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use ValueObjects\StringLiteral\StringLiteral;
use CultuurNet\Deserializer\DataValidationException;

class QueryJSONDeserializer extends JSONDeserializer
{
    /**
     * @var QueryDataValidator
     */
    private $validator;

    public function __construct()
    {
        $assoc = true;
        parent::__construct($assoc);

        $this->validator = new QueryDataValidator();
    }

    /**
     * @param StringLiteral $data
     * @return Query
     * @throws DataValidationException
     */
    public function deserialize(StringLiteral $data): Query
    {
        $data = parent::deserialize($data);
        /** @var array $data */
        $this->validator->validate($data);

        return new Query($data['query']);
    }
}
