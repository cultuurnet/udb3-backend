<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Import;

use CultuurNet\UDB3\Http\Event\ImportEventRequestHandler;
use CultuurNet\UDB3\Http\Organizer\ImportOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Place\ImportPlaceRequestHandler;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

/**
 * @deprecated
 *   Should only register old /imports/... endpoints for backward compatibility.
 */
class ImportControllerProvider implements ControllerProviderInterface
{
    public const PATH = '/imports';

    public function connect(Application $app): ControllerCollection
    {
        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/events/', ImportEventRequestHandler::class);
        $controllers->put('/events/{eventId}/', ImportEventRequestHandler::class);

        $controllers->post('/places/', ImportPlaceRequestHandler::class);
        $controllers->put('/places/{placeId}/', ImportPlaceRequestHandler::class);

        $controllers->post('/organizers/', ImportOrganizerRequestHandler::class);
        $controllers->put('/organizers/{organizerId}/', ImportOrganizerRequestHandler::class);

        return $controllers;
    }
}
