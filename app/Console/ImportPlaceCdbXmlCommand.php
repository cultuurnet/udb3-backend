<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB2DomainEvents\ActorCreated;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use DateTimeImmutable;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class ImportPlaceCdbXmlCommand extends AbstractCommand
{
    private const ID = 'id';
    private const URL = 'url';

    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this
            ->setName('place:cdbxml:import')
            ->setDescription('Import place CdbXML')
            ->addArgument(
                self::ID,
                InputArgument::REQUIRED,
                'CdbId of the place.'
            )
            ->addArgument(
                self::URL,
                InputArgument::REQUIRED,
                'Full URL to the XML file to import.'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $incomingUdb2Event = new ActorCreated(
            new StringLiteral($input->getArgument(self::ID)),
            new DateTimeImmutable(),
            new StringLiteral(SYSTEM_USER_UUID),
            Url::fromNative($input->getArgument(self::URL))
        );

        $domainMessage = (new DomainMessageBuilder())
            ->setUserId(SYSTEM_USER_UUID)
            ->create($incomingUdb2Event);

        /* @var EventBusInterface $eventBus */
        $app = $this->getSilexApplication();
        $eventBus = $app['event_bus'];

        $eventBus->publish(new DomainEventStream([$domainMessage]));
    }
}
