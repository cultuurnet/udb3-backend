<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Doctrine\DBAL\Connection;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReindexOffersWithPopularityScore extends Command
{
    private $allowedTypes = [
        'event',
        'place',
    ];

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure(): void
    {
        $this
            ->setName('offer:reindex-offers-with-popularity')
            ->setDescription('Reindex events or places that have a popularity score.')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'The type of the offer, either place or event.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument('type');

        if (!\in_array($type, $this->allowedTypes, true)) {
            throw new \InvalidArgumentException('The type "'.$type." is not support. Use event or place.");
        }

        return 0;
    }
}
