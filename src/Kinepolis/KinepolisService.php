<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Calendar\Calendar as LegacyCalendar;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\Commands\UpdateDescription;
use CultuurNet\UDB3\Event\Event as EventAggregate;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Kinepolis\Parser\Parser;
use CultuurNet\UDB3\Kinepolis\Parser\PriceParser;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Media\ImageUploaderInterface;
use CultuurNet\UDB3\Media\Properties\Description as MediaDescription;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use CultuurNet\UDB3\Offer\Commands\UpdatePriceInfo;
use Exception;
use Psr\Log\LoggerInterface;

final class KinepolisService
{
    private CommandBus $commandBus;

    private Repository $aggregateRepository;

    private KinepolisClient $client;

    private Parser $parser;

    private PriceParser $priceParser;

    private MappingRepository $movieMappingRepository;

    private ImageUploaderInterface $imageUploader;

    private UuidGeneratorInterface $uuidGenerator;

    private LoggerInterface $logger;

    public function __construct(
        CommandBus $commandBus,
        Repository $aggregateRepository,
        KinepolisClient $client,
        Parser $parser,
        PriceParser $priceParser,
        MappingRepository $movieMappingRepository,
        ImageUploaderInterface $imageUploader,
        UuidGeneratorInterface $uuidGenerator,
        LoggerInterface $logger
    ) {
        $this->commandBus = $commandBus;
        $this->aggregateRepository = $aggregateRepository;
        $this->client = $client;
        $this->parser = $parser;
        $this->priceParser = $priceParser;
        $this->movieMappingRepository = $movieMappingRepository;
        $this->imageUploader = $imageUploader;
        $this->uuidGenerator = $uuidGenerator;
        $this->logger = $logger;
    }

    public function import(): void
    {
        try {
            $token = $this->client->getToken();
        } catch (Exception $exception) {
            $this->logger->error('Problem with Kinepolis Service Authentication: ' . $exception->getMessage());
            return;
        }

        try {
            $theaters = $this->client->getTheaters($token);
        } catch (Exception $exception) {
            $this->logger->error('Problem with fetching theatres from Kinepolis Service: ' . $exception->getMessage());
            return;
        }

        /**
         * @var ParsedPriceForATheater[] $parsedPrices
         */
        $parsedPrices = [];
        foreach ($theaters as $theater) {
            try {
                $pricesToParse = $this->client->getPricesForATheater($token, $theater['tid']);
            } catch (Exception $exception) {
                $this->logger->error('Problem with fetching the prices from Theater ' . $theater['tid'] . ': ' . $exception->getMessage());
                return;
            }

            try {
                $parsedPrices[$theater['tid']] = $this->priceParser->parseTheaterPrices($pricesToParse);
            } catch (Exception $exception) {
                $this->logger->error('Problem with parsing the prices from Theater ' . $theater['tid'] . ': ' . $exception->getMessage());
                return;
            }
        }

        try {
            $movies = $this->client->getMovies($token);
        } catch (Exception $exception) {
            $this->logger->error('Problem with fetching movies from Kinepolis Service: ' . $exception->getMessage());
            return;
        }

        $this->logger->info('Found ' . count($movies) . ' movie productions.');

        foreach ($movies as $movie) {
            $mid = $movie['mid'];

            try {
                $movieDetail = $this->client->getMovieDetail($token, $mid);
            } catch (Exception $exception) {
                $this->logger->error('Problem with fetching movieDetails from movie with id ' . $mid . ': ' . $exception->getMessage());
                return;
            }
            try {
                $parsedMovies = $this->parser->getParsedMovies($movieDetail, $parsedPrices);
            } catch (Exception $exception) {
                $this->logger->error('Problem with parsing movie with id ' . $mid . ': ' . $exception->getMessage());
                return;
            }

            $this->logger->info('Found ' . count($parsedMovies) . ' screenings for movie with kinepolisId ' . $mid);

            foreach ($parsedMovies as $parsedMovie) {
                $this->process($parsedMovie);
            }
        }
    }

    private function process(ParsedMovie $parsedMovie): void
    {
        $commands = [];
        $eventId = $this->movieMappingRepository->getByMovieId($parsedMovie->getExternalId());

        if ($eventId === null) {
            $eventId = $this->createNewMovie($parsedMovie);
        } else {
            $updateCalendar = new UpdateCalendar($eventId, LegacyCalendar::fromUdb3ModelCalendar($parsedMovie->getCalendar()));
            $commands[] = $updateCalendar;
            $this->logger->info('Event with id ' . $eventId . ' updated');
        }

        $updateDescription = new UpdateDescription(
            $eventId,
            new LegacyLanguage('nl'),
            Description::fromUdb3ModelDescription($parsedMovie->getDescription())
        );
        $commands[] = $updateDescription;

        $updatePriceInfo = new UpdatePriceInfo(
            $eventId,
            $parsedMovie->getPriceInfo()
        );
        $commands[] = $updatePriceInfo;

        foreach ($commands as $command) {
            $this->commandBus->dispatch($command);
        }
    }

    private function createNewMovie(ParsedMovie $parsedMovie): string
    {
        $eventId = $this->uuidGenerator->generate();
        $eventAggregate = EventAggregate::create(
            $eventId,
            new LegacyLanguage('nl'),
            $parsedMovie->getTitle(),
            new EventType('0.50.6.0.0', 'Film'),
            $parsedMovie->getLocationId(),
            LegacyCalendar::fromUdb3ModelCalendar($parsedMovie->getCalendar()),
            $parsedMovie->getTheme()
        );

        $this->aggregateRepository->save($eventAggregate);
        $this->movieMappingRepository->create($eventId, $parsedMovie->getExternalId());
        $this->logger->info('Event created with id ' . $eventId);
        return $eventId;
    }
}
