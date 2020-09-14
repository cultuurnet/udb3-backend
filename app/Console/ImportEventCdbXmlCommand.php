<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB2DomainEvents\EventCreated;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\User\UserIdentityDetails;
use DateTimeImmutable;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class ImportEventCdbXmlCommand extends AbstractCommand
{
    private const ID = 'id';
    private const URL = 'url';

    /**
     * @var EventBusInterface
     */
    private $eventBus;

    public function __construct(CommandBusInterface $commandBus, EventBusInterface $eventBus)
    {
        parent::__construct($commandBus);
        $this->eventBus = $eventBus;
    }

    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this
            ->setName('event:cdbxml:import')
            ->setDescription('Import event CdbXML')
            ->addArgument(
                self::ID,
                InputArgument::REQUIRED,
                'CdbId of the event.'
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
        $incomingUdb2Event = new EventCreated(
            new StringLiteral($input->getArgument(self::ID)),
            new DateTimeImmutable(),
            new StringLiteral(UserIdentityDetails::SYSTEM_USER_UUID),
            Url::fromNative($input->getArgument(self::URL))
        );

        $domainMessage = (new DomainMessageBuilder())
            ->setUserId(UserIdentityDetails::SYSTEM_USER_UUID)
            ->create($incomingUdb2Event);

        $this->eventBus->publish(new DomainEventStream([$domainMessage]));
    }
}
