<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Validation;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\ConvertsToApiProblem;
use Exception;

final class ChangeNotAllowedByTicketSales extends Exception implements ConvertsToApiProblem
{
    private string $eventId;

    public function __construct(string $eventId)
    {
        parent::__construct('Organizer change not allowed because event ' . $eventId . ' has ticket sales in UiTPAS');
        $this->eventId = $eventId;
    }

    public function toApiProblem(): ApiProblem
    {
        return ApiProblem::eventHasUitpasTicketSales($this->eventId);
    }
}
