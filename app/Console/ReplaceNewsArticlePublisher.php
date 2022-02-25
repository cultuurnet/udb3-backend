<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class ReplaceNewsArticlePublisher extends Command
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();

        $this->connection = $connection;
    }

    public function configure(): void
    {
        $this
            ->setName('article:replace-publisher')
            ->setDescription(
                'Replace a news article publisher (make sure to use double quotes around names with white space)'
            )
            ->addArgument(
                'old-publisher',
                InputArgument::REQUIRED,
                'The name of the old publisher to replace'
            )
            ->addArgument(
                'new-publisher',
                InputArgument::REQUIRED,
                'The name of the new publisher to set'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $oldPublisher = $input->getArgument('old-publisher');
        $newPublisher = $input->getArgument('new-publisher');

        $newsArticlesCount = $this->getNewsArticleCount($oldPublisher);

        if ($newsArticlesCount <= 0) {
            $output->writeln('No articles found with publisher "' . $oldPublisher . '"');
            return 0;
        }

        $update = $this->getHelper('question')
        ->ask(
            $input,
            $output,
            new ConfirmationQuestion(
                'This action will update ' . $newsArticlesCount . ' news articles with publisher "' . $newPublisher . '", continue? [y/N] ',
                false
            )
        );

        if (!$update) {
            $output->writeln('Bailing out.');
            return 0;
        }

        $this->updateNewsArticles($oldPublisher, $newPublisher);
        $output->writeln('Updated all news articles.');

        return 0;
    }

    private function getNewsArticleCount(string $publisher): int
    {
        return $this->connection->createQueryBuilder()
            ->select('*')
            ->from('news_article')
            ->where('publisher = :publisher')
            ->setParameter(':publisher', $publisher)
            ->execute()
            ->rowCount();
    }

    private function updateNewsArticles(string $oldPublisher, string $newPublisher): void
    {
        $this->connection->createQueryBuilder()
            ->update('news_article')
            ->where('publisher = :oldPublisher')
            ->setParameter(':oldPublisher', $oldPublisher)
            ->set('publisher', ':newPublisher')
            ->setParameter(':newPublisher', $newPublisher)
            ->execute();
    }
}
