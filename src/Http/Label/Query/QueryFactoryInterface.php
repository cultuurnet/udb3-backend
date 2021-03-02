<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label\Query;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use Symfony\Component\HttpFoundation\Request;

interface QueryFactoryInterface
{
    /**
     * @return Query
     */
    public function createFromRequest(Request $request);
}
