<?php

namespace CultuurNet\UDB3\Http\Label\Query;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use Symfony\Component\HttpFoundation\Request;

interface QueryFactoryInterface
{
    /**
     * @param Request $request
     * @return Query
     */
    public function createFromRequest(Request $request);
}
