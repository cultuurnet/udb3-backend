<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FireProjectedToJSONLDCommand extends AbstractFireProjectedToJSONLDCommand
{
    protected function configure(): void
    {
        $this
            ->setName('fire-projected-to-jsonld')
            ->setDescription('Fires JSONLD projected events for the specified entity')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'type of the entity, either "place" or "organizer"'
            )
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'id of the entity'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->inReplayMode(
            function (
                EventBus $eventBus,
                InputInterface $input,
                OutputInterface $output
            ): void {
                $type = $input->getArgument('type');

                $domainMessageBuilder = new DomainMessageBuilder();

                $this->fireEvent(
                    $input->getArgument('id'),
                    $this->getEventFactory($type),
                    $output,
                    $domainMessageBuilder,
                    $eventBus
                );
            },
            $input,
            $output
        );

        return 0;
    }
}
