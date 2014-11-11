<?php
/**
 * @file
 */

use \Symfony\Component\HttpFoundation\JsonResponse;
use \Symfony\Component\HttpFoundation\Request;
use \Silex\Application;
use \CultuurNet\UDB3\UDB2\EntryAPIFactory;
use \CultuurNet\Auth\User;

$app->get(
    'api/1.0/entry/search',
    function (Request $request, Application $app) {
        /** @var EntryAPIFactory $entryAPIFactory */
        $entryAPIFactory = $app['udb2_entry_api_factory'];

        /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $app['session'];
        /** @var User $minimalUserData */
        $minimalUserData = $session->get('culturefeed_user');

        $tokenCredentials = $minimalUserData->getTokenCredentials();

        $entryAPI = $entryAPIFactory->withTokenCredentials($tokenCredentials);

        $query = $request->query->get('query', '');
        $page = $request->query->get('page', 1);
        $pageLength = $request->query->get('pagelength', 50);
        $results = $entryAPI->getEvents($query, $page, $pageLength);

        $content = array();

        /** @var CultureFeed_Cdb_List_Item $item */
        foreach ($results as $item) {
            $content[] = $app['iri_generator']->iri($item->getCdbId());
        }

        return JsonResponse::create()->setData($content);
    }
)->before($checkAuthenticated);
