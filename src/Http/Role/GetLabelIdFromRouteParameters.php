<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use InvalidArgumentException;

trait GetLabelIdFromRouteParameters
{
    private ReadRepositoryInterface $labelRepository;

    private function getLabelId(RouteParameters $routeParameters): Uuid
    {
        $labelId = $routeParameters->get('labelId');
        try {
            return new Uuid($labelId);
        } catch (InvalidArgumentException $exception) {
            $entity = $this->labelRepository->getByName($labelId);

            if ($entity === null) {
                throw ApiProblem::urlNotFound('There is no label with identifier: ' . $labelId);
            }

            return new Uuid($entity->getUuid()->toString());
        }
    }
}
