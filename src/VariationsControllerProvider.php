<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Hydra\PagedCollection;
use CultuurNet\UDB3\Hydra\Symfony\PageUrlGenerator;
use CultuurNet\UDB3\Symfony\JsonLdResponse;
use CultuurNet\UDB3\Variations\Command\CreateEventVariationJSONDeserializer;
use CultuurNet\UDB3\Variations\Command\DeleteEventVariation;
use CultuurNet\UDB3\Variations\Command\EditDescriptionJSONDeserializer;
use CultuurNet\UDB3\Variations\Model\Properties\Id;
use CultuurNet\UDB3\Variations\ReadModel\Search\CriteriaFromParameterBagFactory;
use CultuurNet\UDB3\Variations\ReadModel\Search\RepositoryInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\String\String;

class VariationsControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];
        $controllerProvider = $this;

        $controllers->get(
            '/',
            function (Application $app, Request $request) {
                $factory = new CriteriaFromParameterBagFactory();
                $criteria = $factory->createCriteriaFromParameterBag($request->query);

                /** @var RepositoryInterface $search */
                $search = $app['variations.search'];

                $itemsPerPage = 5;
                $pageNumber = intval($request->query->get('page', 0));

                $variationIds = $search->getEventVariations(
                    $criteria,
                    $itemsPerPage,
                    $pageNumber
                );

                /** @var DocumentRepositoryInterface $jsonLDRepository */
                $jsonLDRepository = $app['variations.jsonld_repository'];

                $variations = [];
                foreach ($variationIds as $variationId) {
                    $document = $jsonLDRepository->get($variationId);

                    if ($document) {
                        $variations[] = $document->getBody();
                    }
                }

                $totalItems = $search->countEventVariations(
                    $criteria
                );

                $pageUrlFactory = new PageUrlGenerator(
                    $request->query,
                    $app['url_generator'],
                    'variations',
                    'page'
                );

                return new JsonResponse(
                    new PagedCollection(
                        $pageNumber,
                        $itemsPerPage,
                        $variations,
                        $totalItems,
                        $pageUrlFactory
                    )
                );
            }
        )->bind('variations');

        $controllers->post(
            '/',
            function (Application $app, Request $request) use ($controllerProvider) {
                $deserializer = new CreateEventVariationJSONDeserializer();
                $command = $deserializer->deserialize(
                    new String($request->getContent())
                );

                $commandId = $app['event_command_bus']->dispatch($command);
                return $controllerProvider->getResponseForCommandId($commandId);
            }
        )->before(function($request) use ($controllerProvider) {
            return $controllerProvider->requireJsonContent($request);
        });

        // @todo Verify if the variation with the given id exists, based on a
        // read model.
        $controllers->patch(
            '/{id}',
            function (Request $request, Application $app, $id) use ($controllerProvider) {
                $variationId = new Id($id);
                $jsonCommand = new String($request->getContent());
                $deserializer = new EditDescriptionJSONDeserializer($variationId);
                $command = $deserializer->deserialize($jsonCommand);

                $commandId = $app['event_command_bus']->dispatch($command);
                return $controllerProvider->getResponseForCommandId($commandId);
            }
        )->before(function($request) use ($controllerProvider) {
            return $controllerProvider->requireJsonContent($request);
        });

        // @todo Verify if the variation with the given id exists, based on a
        // read model.
        $controllers->delete(
            '/{id}',
            function (Application $app, $id) use ($controllerProvider) {
                $variationId = new Id($id);
                $command = new DeleteEventVariation($variationId);

                $commandId = $app['event_command_bus']->dispatch($command);
                return $controllerProvider->getResponseForCommandId($commandId);
            }
        );

        $controllers->get(
            '/{id}',
            function (Application $app, $id) {
                /** @var DocumentRepositoryInterface $jsonLDRepository */
                $jsonLDRepository = $app['variations.jsonld_repository'];

                $document = $jsonLDRepository->get($id);

                $response = JsonLdResponse::create()
                    ->setContent($document->getRawBody());

                return $response;
            }
        );

        return $controllers;
    }

    /**
     * @param Request $request
     * @return JsonResponse|null
     */
    private function requireJsonContent(Request $request)
    {
        if ($request->getContentType() != 'json') {
            return new JsonResponse(
                [],
                Response::HTTP_UNSUPPORTED_MEDIA_TYPE
            );
        } else {
            return null;
        }
    }

    /**
     * @param string $commandId
     * @return JsonResponse
     */
    private function getResponseForCommandId($commandId) {
        return JsonResponse::create(
            ['commandId' => $commandId],
            JsonResponse::HTTP_ACCEPTED
        );
    }

}
