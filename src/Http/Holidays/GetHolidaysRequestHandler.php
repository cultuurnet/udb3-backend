<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Holidays;

use CultuurNet\UDB3\Clock\Clock;
use CultuurNet\UDB3\Holidays\HolidaysService;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\QueryParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal This endpoint is for internal use only and should not be exposed in the public API documentation.
 */
final class GetHolidaysRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly HolidaysService $holidaysService,
        private readonly Clock $clock
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParameters = new QueryParameters($request);

        $today = DateTimeImmutable::createFromInterface($this->clock->getDateTime())->setTime(0, 0, 0);

        $startDateParam = $queryParameters->get('startDate');
        $endDateParam = $queryParameters->get('endDate');

        if ($startDateParam !== null) {
            $parsedStartDate = DateTimeImmutable::createFromFormat('Y-m-d', $startDateParam);
            if ($parsedStartDate === false) {
                throw ApiProblem::queryParameterInvalidValue('startDate', $startDateParam, ['YYYY-MM-DD']);
            }
            $startDate = $parsedStartDate->setTime(0, 0, 0);
        } else {
            $startDate = $today;
        }

        if ($endDateParam !== null) {
            $parsedEndDate = DateTimeImmutable::createFromFormat('Y-m-d', $endDateParam);
            if ($parsedEndDate === false) {
                throw ApiProblem::queryParameterInvalidValue('endDate', $endDateParam, ['YYYY-MM-DD']);
            }
            $endDate = $parsedEndDate->setTime(0, 0, 0);
        } else {
            $endDate = $today->modify('+1 year');
        }

        $maxAllowedDate = $today->modify('+5 years');
        if ($endDate > $maxAllowedDate) {
            throw ApiProblem::dateRangeExceedsLimit();
        }

        return new JsonResponse($this->holidaysService->getHolidays($startDate, $endDate));
    }
}
