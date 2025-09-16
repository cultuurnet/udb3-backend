<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller\Response;

use CultureFeed_Uitpas_CardSystem;
use CultureFeed_Uitpas_DistributionKey;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Json;
use Slim\Psr7\Interfaces\HeadersInterface;

class CardSystemsJsonResponse extends JsonResponse
{
    /**
     * @param CultureFeed_Uitpas_CardSystem[] $cardSystems
     */
    public function __construct(array $cardSystems, int $status = 200, ?HeadersInterface $headers = null)
    {
        $data = [];
        foreach ($cardSystems as $cardSystem) {
            $data[$cardSystem->getId()] = $this->convertCardSystemToArray($cardSystem);
        }

        $content = Json::encode($data);

        parent::__construct($content, $status, $headers);
    }

    private function convertCardSystemToArray(CultureFeed_Uitpas_CardSystem $cardSystem): array
    {
        $distributionKeys = [];
        foreach ($cardSystem->distributionKeys as $distributionKey) {
            $distributionKeys[$distributionKey->id] = $this->convertDistributionKeyToArray($distributionKey);
        }

        return [
            'id' => $cardSystem->getId(),
            'name' => $cardSystem->getName(),
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
