<?php

namespace CultuurNet\UDB3\Silex\Variations;

use Broadway\Serializer\SimpleInterfaceSerializer;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\EventSourcing\DBAL\AggregateAwareDBALEventStore;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Silex\AggregateType;
use CultuurNet\UDB3\Variations\DefaultOfferVariationService;
use CultuurNet\UDB3\Variations\OfferVariationCommandHandler;
use CultuurNet\UDB3\Variations\OfferVariationRepository;
use CultuurNet\UDB3\Variations\ReadModel\Search\Doctrine\DBALRepository;
use CultuurNet\UDB3\Variations\ReadModel\Search\Doctrine\ExpressionFactory;
use CultuurNet\UDB3\Variations\ReadModel\Search\Projector;
use Silex\Application;
use Silex\ServiceProviderInterface;

class VariationsServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {

    }
    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }
}
