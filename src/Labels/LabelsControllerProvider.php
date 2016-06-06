<?php

namespace CultuurNet\UDB3\Silex\Labels;

use CultuurNet\UDB3\Symfony\Label\EditRestController;
use CultuurNet\UDB3\Symfony\Label\Helper\RequestHelper;
use CultuurNet\UDB3\Symfony\Label\ReadRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class LabelsControllerProvider implements ControllerProviderInterface
{
    const READ_REST_CONTROLLER = 'labels.read_rest_controller';
    const EDIT_REST_CONTROLLER = 'labels.edit_rest_controller';

    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        $this->setUpReadRestController($app);
        $this->setUpEditRestController($app);

        return $this->setControllerPaths($app['controllers_factory']);
    }

    /**
     * @param Application $app
     */
    private function setUpReadRestController(Application $app)
    {
        $app[self::READ_REST_CONTROLLER] = $app->share(
            function (Application $app) {
                return new ReadRestController(
                    $app[LabelServiceProvider::READ_SERVICE]
                );
            }
        );
    }

    /**
     * @param Application $app
     */
    private function setUpEditRestController(Application $app)
    {
        $app[self::EDIT_REST_CONTROLLER] = $app->share(
            function (Application $app) {
                return new EditRestController(
                    $app[LabelServiceProvider::WRITE_SERVICE],
                    new RequestHelper()
                );
            }
        );
    }

    /**
     * @param ControllerCollection $controllers
     * @return ControllerCollection
     */
    private function setControllerPaths(ControllerCollection $controllers)
    {
        $controllers
            ->get('/{uuid}', self::READ_REST_CONTROLLER . ':getByUuid')
            ->bind('label');

        $controllers->post(
            '/',
            self::EDIT_REST_CONTROLLER . ':create'
        );

        return $controllers;
    }
}
