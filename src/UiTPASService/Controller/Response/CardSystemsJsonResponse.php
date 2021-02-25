<?php

namespace CultuurNet\UDB3\UiTPASService\Controller\Response;

use CultureFeed_Uitpas_CardSystem;
use CultureFeed_Uitpas_DistributionKey;
use Symfony\Component\HttpFoundation\Response;

class CardSystemsJsonResponse extends Response
{
    /**
     * @param CultureFeed_Uitpas_CardSystem[] $cardSystems
     * @param int $status
     * @param array $headers
     */
    public function __construct(array $cardSystems, int $status = 200, array $headers = [])
    {
        $data = [];
        foreach ($cardSystems as $cardSystem) {
            $data[$cardSystem->id] = $this->convertCardSystemToArray($cardSystem);
        }

        $content = json_encode($data);

        parent::__construct($content, $status, $headers);
    }

    private function convertCardSystemToArray(CultureFeed_Uitpas_CardSystem $cardSystem): array
    {
        $distributionKeys = [];
        foreach ($cardSystem->distributionKeys as $distributionKey) {
            $distributionKeys[$distributionKey->id] = $this->convertDistributionKeyToArray($distributionKey);
        }

        return [
            'id' => $cardSystem->id,
            'name' => $cardSystem->name,
            'distributionKeys' => $distributionKeys,
        ];
    }

    private function convertDistributionKeyToArray(CultureFeed_Uitpas_DistributionKey $distributionKey): array
    {
        return [
            'id' => $distributionKey->id,
            'name' => $distributionKey->name,
        ];
    }
}
