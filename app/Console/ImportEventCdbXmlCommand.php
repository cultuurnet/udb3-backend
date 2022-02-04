<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\UDB2\DomainEvents\EventCreated;
use DateTimeImmutable;
use Ramsey\Uuid\UuidFactory;
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
     * @var string
     */
    private $systemUserId;

    /**
     * @var EventBus
     */
    private $eventBus;

    public function __construct(CommandBus $commandBus, EventBus $eventBus, string $systemUserId)
    {
        parent::__construct($commandBus);
        $this->eventBus = $eventBus;
        $this->systemUserId = $systemUserId;
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
            new StringLiteral($this->systemUserId),
            Url::fromNative($input->getArgument(self::URL))
        );

        $domainMessage = (new DomainMessageBuilder(new UuidFactory()))
            ->setUserId($this->systemUserId)
            ->create($incomingUdb2Event);

        $this->eventBus->publish(new DomainEventStream([$domainMessage]));

        return 0;
    }
}
