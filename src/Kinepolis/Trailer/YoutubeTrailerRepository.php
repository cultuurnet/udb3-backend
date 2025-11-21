<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis\Trailer;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Google\Service\Exception as GoogleException;
use Google_Service_YouTube;
use Psr\Log\LoggerInterface;

final class YoutubeTrailerRepository implements TrailerRepository
{
    private string $channelId;

    private Google_Service_YouTube $youTubeClient;

    private UuidGeneratorInterface $uuidGenerator;

    private LoggerInterface $logger;

    private bool $enabled;

    private bool $quotaHasBeenReached = false;

    public function __construct(
        Google_Service_YouTube $youTubeClient,
        string $channelId,
        UuidGeneratorInterface $uuidGenerator,
        LoggerInterface $logger,
        bool $enabled
    ) {
        $this->channelId = $channelId;
        $this->uuidGenerator = $uuidGenerator;
        $this->youTubeClient = $youTubeClient;
        $this->logger = $logger;
        $this->enabled = $enabled;
    }

    public function findMatchingTrailer(string $title): ?Video
    {
        if (!$this->enabled || $this->quotaHasBeenReached) {
            return null;
        }

        try {
            $response = $this->youTubeClient->search->listSearch('id,snippet', [
                'channelId' => $this->channelId,
                'q' => urlencode($title),
                'maxResults' => 1,
            ]);

            foreach ($response['items'] as $result) {
                switch ($result['id']['kind']) {
                    case 'youtube#video':
                        $youtubeTrailer = new Url('https://www.youtube.com/watch?v=' . $result['id']['videoId']);
                        $this->logger->info('Matched ' . $youtubeTrailer->toString() . ' for ' . $title);
                        return new Video(
                            $this->uuidGenerator->generate(),
                            $youtubeTrailer,
                            new Language('nl')
                        );
                    default:
                        $this->logger->info('No Matching trailer found for ' . $title);
                }
            }
        } catch (GoogleException $exception) {
            $message = $exception->getMessage();
            $this->logger->error($message);
            if ($message === 'The request cannot be completed because you have exceeded your \u003ca href=\"/youtube/v3/getting-started#quota\"\u003equota\u003c/a\u003e.') {
                $this->quotaHasBeenReached = true;
            }
            if (stripos($message, 'quota') !== false) {
                $this->quotaHasBeenReached = true;
            }
        }

        return null;
    }
}
