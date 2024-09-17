<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Calendar\Calendar as LegacyCalendar;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\Commands\AddImage;
use CultuurNet\UDB3\Event\Commands\Moderation\Publish;
use CultuurNet\UDB3\Event\Commands\UpdateDescription;
use CultuurNet\UDB3\Event\Event as EventAggregate;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\Productions\AddEventToProduction;
use CultuurNet\UDB3\Event\Productions\GroupEventsAsProduction;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Kinepolis\Client\KinepolisClient;
use CultuurNet\UDB3\Kinepolis\Exception\ImageNotFound;
use CultuurNet\UDB3\Kinepolis\Mapping\MappingRepository;
use CultuurNet\UDB3\Kinepolis\Parser\MovieParser;
use CultuurNet\UDB3\Kinepolis\Parser\PriceParser;
use CultuurNet\UDB3\Kinepolis\Trailer\TrailerRepository;
use CultuurNet\UDB3\Kinepolis\ValueObject\ParsedMovie;
use CultuurNet\UDB3\Kinepolis\ValueObject\ParsedPriceForATheater;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Media\ImageUploaderInterface;
use CultuurNet\UDB3\Media\Properties\Description as MediaDescription;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use CultuurNet\UDB3\Offer\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Offer\Commands\Video\AddVideo;
use CultuurNet\UDB3\Security\AuthorizableCommand;
use Exception;
use Google\Service\Exception as GoogleException;
use Psr\Log\LoggerInterface;

final class KinepolisService
{
    private CommandBus $commandBus;

    private Repository $aggregateRepository;

    private KinepolisClient $client;

    private MovieParser $movieParser;

    private PriceParser $priceParser;

    private MappingRepository $movieMappingRepository;

    private ImageUploaderInterface $imageUploader;

    private UuidGeneratorInterface $uuidGenerator;

    private TrailerRepository $trailerRepository;

    private ProductionRepository $productionRepository;

    private LoggerInterface $logger;

    private bool $trailersEnabled;

    public function __construct(
        CommandBus $commandBus,
        Repository $aggregateRepository,
        KinepolisClient $client,
        MovieParser $movieParser,
        PriceParser $priceParser,
        MappingRepository $movieMappingRepository,
        ImageUploaderInterface $imageUploader,
        UuidGeneratorInterface $uuidGenerator,
        TrailerRepository $trailerRepository,
        ProductionRepository $productionRepository,
        bool $trailersEnabled,
        LoggerInterface $logger
    ) {
        $this->commandBus = $commandBus;
        $this->aggregateRepository = $aggregateRepository;
        $this->client = $client;
        $this->movieParser = $movieParser;
        $this->priceParser = $priceParser;
        $this->movieMappingRepository = $movieMappingRepository;
        $this->imageUploader = $imageUploader;
        $this->uuidGenerator = $uuidGenerator;
        $this->trailerRepository = $trailerRepository;
        $this->productionRepository = $productionRepository;
        $this->trailersEnabled = $trailersEnabled;
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
                $parsedMovies = $this->movieParser->getParsedMovies($movieDetail, $parsedPrices);
            } catch (Exception $exception) {
                $this->logger->error('Problem with parsing movie with id ' . $mid . ': ' . $exception->getMessage());
                return;
            }

            $this->logger->info('Found ' . count($parsedMovies) . ' screenings for movie with kinepolisId ' . $mid);

            // We do only 1 search by movieTitle for a trailer to avoid hitting the YouTube Rate limiting.
            $movieTitle = $movie['title'];
            $trailer = null;
            if ($this->trailersEnabled) {
                try {
                    $trailer = $this->trailerRepository->findMatchingTrailer($movieTitle);
                } catch (GoogleException $exception) {
                    $this->logger->error('Problem with searching trailer for ' . $movieTitle . ':' . $exception->getMessage());
                }
            }

            foreach ($parsedMovies as $parsedMovie) {
                $this->process($parsedMovie, $token, $trailer);
            }
        }
    }

    private function process(ParsedMovie $parsedMovie, string $token, ?Video $trailer): void
    {
        $commands = [];
        $eventId = $this->movieMappingRepository->getByMovieId($parsedMovie->getExternalId());

        if ($eventId === null) {
            $eventId = $this->createNewMovie($parsedMovie);
            $commands[] = new Publish($eventId);
            try {
                $addImage = $this->uploadImage($token, $parsedMovie, $eventId);
                $commands[] = $addImage;
            } catch (ImageNotFound $imageNotFound) {
                $this->logger->error($imageNotFound->getMessage());
            }

            $commands[] = $this->getLinkToProductionCommand($parsedMovie->getTitle()->toString(), $eventId);

            if ($trailer !== null) {
                $this->logger->info('Found trailer ' . $trailer->getUrl()->toString() . ' for movie ' . $parsedMovie->getTitle()->toString());
                $addVideo = new AddVideo(
                    $eventId,
                    $trailer
                );
                $commands[] = $addVideo;
            }
        } else {
            $updateCalendar = new UpdateCalendar($eventId, LegacyCalendar::fromUdb3ModelCalendar($parsedMovie->getCalendar()));
            $commands[] = $updateCalendar;
            $this->logger->info('Event with id ' . $eventId . ' updated');
        }

        if ($parsedMovie->getDescription() !== null) {
            $updateDescription = new UpdateDescription(
                $eventId,
                new LegacyLanguage('nl'),
                Description::fromUdb3ModelDescription($parsedMovie->getDescription())
            );
            $commands[] = $updateDescription;
        }

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

    private function uploadImage(string $token, ParsedMovie $parsedMovie, string $eventId): AddImage
    {
        // Movie pictures are normally never updated in the external API
        // to avoid multiple needless updates, we only add an image during the creation
        $uploadedImage = $this->client->getImage($token, $parsedMovie->getImageUrl());
        $imageId = $this->imageUploader->upload(
            $uploadedImage,
            new MediaDescription($parsedMovie->getTitle()->toString()),
            new CopyrightHolder('Kinepolis'),
            new LegacyLanguage('nl')
        );
        return new AddImage($eventId, $imageId);
    }

    private function getLinkToProductionCommand(string $title, string $eventId): AuthorizableCommand
    {
        $productions = $this->productionRepository->search($title, 0, 1);
        if (count($productions) < 1) {
            return GroupEventsAsProduction::withProductionName([$eventId], $title);
        }
        return new AddEventToProduction($eventId, $productions[0]->getProductionId());
    }
}
