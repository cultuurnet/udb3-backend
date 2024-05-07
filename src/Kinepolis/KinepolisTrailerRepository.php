<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Google_Client;
use Google_Service_YouTube;

final class KinepolisTrailerRepository implements TrailerRepository
{
    private string $channelId;

    private Google_Service_YouTube $youTubeClient;
    public function __construct(string $developerKey, string $channelId)
    {
        $this->channelId = $channelId;

        $client = new Google_Client();
        $client->setApplicationName('TrailerFinger');
        $client->setDeveloperKey($developerKey);
        $this->youTubeClient = new Google_Service_YouTube($client);
    }

    public function search(string $title): ?Video
    {
        $response = $this->youTubeClient->search->listSearch('id,snippet', [
            'channelId' => $this->channelId,
            'q' => $title,
            'maxResults' => 1,
        ]);

        foreach ($response['items'] as $result) {
            switch ($result['id']['kind']) {
                case 'youtube#video':
                    return new Video(
                        $result['id']['videoId'],
                        new Url('https://www.youtube.com/watch?v=' . $result['id']['videoId']),
                        new Language('nl')
                    );
            }
        }

        return null;
    }
}
