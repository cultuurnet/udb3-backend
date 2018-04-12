<?php

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerIsInPermissionGroup;
use CultuurNet\UDB3\Http\CompositePsr7RequestAuthorizer;
use CultuurNet\UDB3\Offer\DefaultExternalOfferEditingService;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use CultuurNet\UDB3\Offer\LocalOfferReadingService;
use CultuurNet\UDB3\Offer\OfferType;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class OfferServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['offer_reading_service'] = $app->share(
            function (Application $app) {
                return (new LocalOfferReadingService($app['iri_offer_identifier_factory']))
                    ->withDocumentRepository(OfferType::EVENT(), $app['event_jsonld_repository'])
                    ->withDocumentRepository(OfferType::PLACE(), $app['place_jsonld_repository']);
            }
        );

        $app['external_offer_editing_service'] = $app->share(
            function (Application $app) {
                return new DefaultExternalOfferEditingService(
                    $app['http.guzzle'],
                    $app['http.guzzle_psr7_factory'],
                    new CompositePsr7RequestAuthorizer(
                        $app['http.jwt_request_authorizer'],
                        $app['http.api_key_request_authorizer']
                    )
                );
            }
        );

        $app['iri_offer_identifier_factory'] = $app->share(
            function (Application $app) {
                return new IriOfferIdentifierFactory(
                    $app['config']['offer_url_regex']
                );
            }
        );

        $app['should_auto_approve_new_offer'] = $app->share(
            function (Application $app) {
                return new ConsumerIsInPermissionGroup(
                    new StringLiteral($app['config']['uitid']['auto_approve_group_id'])
                );
            }
        );
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}
