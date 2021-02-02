<?php

namespace CultuurNet\UDB3\Model\ValueObject\Text;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsNotEmpty;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Trims;

class Title
{
    use IsString;
    use IsNotEmpty;
    use Trims;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $value = $this->trim($value);
        $this->guardNotEmpty($value);
        $this->setValue($value);
    }
}
