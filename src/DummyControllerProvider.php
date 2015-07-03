<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class DummyControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get(
            '/api/1.0/city/suggest/{city}',
            function ($city) {
                return (new JsonResponse())->setContent(
                '[{"cid":"3000_LEUVEN","name":"Leuven","zip":"3000","cityId":"3000_Leuven","cityLabel":"3000 Leuven"}]');
            }
        );

        $controllers->get(
            '/api/1.0/location/suggest/{query}/{postalCode}',
            function ($query, $postalCode) {
                if ($postalCode == '3000' && $query == "Dep") {
                    return (new JsonResponse())->setContent(
                        '[{"id":"22db6f6e-a944-4ecc-a002-74f216294f45","title":"Het Depot"}]'
                    );
                }
                else {
                    return (new JsonResponse())->setContent('[]');
                }
            }
        );

        return $controllers;
    }

}
