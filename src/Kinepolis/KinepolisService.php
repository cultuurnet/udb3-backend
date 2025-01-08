<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Event\Commands\AddImage;
use CultuurNet\UDB3\Event\Commands\Moderation\Publish;
use CultuurNet\UDB3\Event\Commands\UpdateDescription;
use CultuurNet\UDB3\Event\Event as EventAggregate;
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
use CultuurNet\UDB3\Media\ImageUploaderInterface;
use CultuurNet\UDB3\Media\Properties\Description as MediaDescription;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
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

    private ProductionRepository $productionRepository;

    private LoggerInterface $logger;

    public function __construct(
        CommandBus $commandBus,
        Repository $aggregateRepository,
        KinepolisClient $client,
        MovieParser $movieParser,
        PriceParser $priceParser,
        MappingRepository $movieMappingRepository,
        ImageUploaderInterface $imageUploader,
        UuidGeneratorInterface $uuidGenerator,
        ProductionRepository $productionRepository,
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
        $this->productionRepository = $productionRepository;
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

            foreach ($parsedMovies as $parsedMovie) {
                $this->process($parsedMovie, $token);
            }
        }
    }

    private function process(ParsedMovie $parsedMovie, string $token): void
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
        } else {
            $updateCalendar = new UpdateCalendar($eventId, $parsedMovie->getCalendar());
            $commands[] = $updateCalendar;
            $this->logger->info('Event with id ' . $eventId . ' updated');
        }

        if ($parsedMovie->getDescription() !== null) {
            $updateDescription = new UpdateDescription(
                $eventId,
                new Language('nl'),
                $parsedMovie->getDescription()
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
            new Language('nl'),
            $parsedMovie->getTitle(),
            new Category(new CategoryID('0.50.6.0.0'), new CategoryLabel('Film'), CategoryDomain::eventType()),
            $parsedMovie->getLocationId(),
            $parsedMovie->getCalendar(),
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
            new Language('nl')
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
