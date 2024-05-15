<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis\Trailer;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Google_Client;
use Google_Service_YouTube;

final class YoutubeTrailerRepository implements TrailerRepository
{
    private string $channelId;

    private Google_Service_YouTube $youTubeClient;

    private UuidGeneratorInterface $uuidGenerator;

    private bool $enabled;

    public function __construct(string $developerKey, string $channelId, UuidGeneratorInterface $uuidGenerator, bool $enabled = true)
    {
        $this->channelId = $channelId;
        $this->uuidGenerator = $uuidGenerator;

        $client = new Google_Client();
        $client->setApplicationName('UiTDatabankTrailerFinder');
        $client->setDeveloperKey($developerKey);
        $this->youTubeClient = new Google_Service_YouTube($client);
        $this->enabled = $enabled;
    }

    public function search(string $title): ?Video
    {
        if (!$this->enabled) {
            return null;
        }

        $response = $this->youTubeClient->search->listSearch('id,snippet', [
            'channelId' => $this->channelId,
            'q' => $title,
            'maxResults' => 1,
        ]);

        foreach ($response['items'] as $result) {
            switch ($result['id']['kind']) {
                case 'youtube#video':
                    return new Video(
                        $this->uuidGenerator->generate(),
                        new Url('https://www.youtube.com/watch?v=' . $result['id']['videoId']),
                        new Language('nl')
                    );
            }
        }

        return null;
    }
}
