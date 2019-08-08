<?php

namespace CultuurNet\UDB3\Http\Variations;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Http\CommandDeserializerControllerTrait;
use CultuurNet\UDB3\Variations\Command\DeleteOfferVariation;
use CultuurNet\UDB3\Variations\Command\EditDescriptionJSONDeserializer;
use CultuurNet\UDB3\Variations\Model\Properties\Id;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EditVariationsRestController
{
    use CommandDeserializerControllerTrait;

    /**
     * @var DocumentRepositoryInterface
     */
    private $repository;

    /**
     * @param DocumentRepositoryInterface $repository
     * @param CommandBusInterface $commandBus
     */
    public function __construct(
        DocumentRepositoryInterface $repository,
        CommandBusInterface $commandBus
    ) {
        $this->repository = $repository;
        $this->setCommandBus($commandBus);
    }

    /**
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function edit(Request $request, $id)
    {
        $this->guardId($id);

        $deserializer = new EditDescriptionJSONDeserializer(
            new Id($id)
        );

        return $this->handleRequestWithDeserializer(
            $request,
            $deserializer
        );
    }

    /**
     * @param string $id
     * @return JsonResponse
     */
    public function delete($id)
    {
        $this->guardId($id);

        $command = new DeleteOfferVariation(
            new Id($id)
        );

        return $this->handleCommand($command);
    }

    /**
     * @param string $id
     */
    private function guardId($id)
    {
        try {
            $document = $this->repository->get($id);
        } catch (DocumentGoneException $e) {
            throw new GoneHttpException();
        }

        if (!$document) {
            throw new NotFoundHttpException();
        }
    }
}
