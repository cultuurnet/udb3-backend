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
        if ($this->isLabelManagementEnabled($app)) {
            $this->setUpReadRestController($app);
            $this->setUpEditRestController($app);

            return $this->setControllerPaths($app['controllers_factory']);
        } else {
            return $app['controllers_factory'];
        }
    }

    /**
     * @param Application $app
     */
    private function setUpReadRestController(Application $app)
    {
        $app[self::READ_REST_CONTROLLER] = $app->share(
            function (Application $app) {
                return new ReadRestController(
                    $app[LabelServiceProvider::READ_SERVICE],
                    $app[LabelServiceProvider::QUERY_FACTORY],
                    new RequestHelper()
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
        $controllers->get('/{id}', self::READ_REST_CONTROLLER . ':get');
        $controllers->patch('/{id}', self::EDIT_REST_CONTROLLER . ':patch');
        $controllers->get('/', self::READ_REST_CONTROLLER . ':search');
        $controllers->post('/', self::EDIT_REST_CONTROLLER . ':create');

        return $controllers;
    }

    /**
     * @param Application $app
     * @return bool
     */
    private function isLabelManagementEnabled(Application $app)
    {
        /** @var \Qandidate\Toggle\ToggleManager $toggles */
        $toggles = $app['toggles'];

        return $toggles->active(
            'label-management',
            $app['toggles.context']
        );
    }
}
