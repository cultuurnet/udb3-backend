<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Variations\Command\CreateEventVariationJSONDeserializer;
use CultuurNet\UDB3\Variations\Command\ValidationException;
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

        $controllers->post(
            '/',
            function (Application $app, Request $request) {
                $deserializer = new CreateEventVariationJSONDeserializer();
                $command = $deserializer->deserialize(
                    new String($request->getContent())
                );

                $data['commandId'] = $app['event_command_bus']->dispatch(
                    $command
                );

                return new JsonResponse(
                    $data,
                    Response::HTTP_ACCEPTED
                );
            }
        )->before(
            function (Request $request) {
                if ($request->getContentType() != 'json') {
                    return new JsonResponse(
                        [],
                        Response::HTTP_UNSUPPORTED_MEDIA_TYPE
                    );
                }
            }
        );

        return $controllers;
    }

}
