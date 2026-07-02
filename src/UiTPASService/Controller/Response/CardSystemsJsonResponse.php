<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller\Response;

use CultureFeed_Uitpas_CardSystem;
use CultureFeed_Uitpas_DistributionKey;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\UiTPAS\CardSystem\CardSystem;
use Slim\Psr7\Interfaces\HeadersInterface;
use UnexpectedValueException;

class CardSystemsJsonResponse extends JsonResponse
{
    /**
     * @param CultureFeed_Uitpas_CardSystem[] $cardSystems
     */
    public function __construct(array $cardSystems, int $status = 200, ?HeadersInterface $headers = null)
    {
        $data = [];
        foreach ($cardSystems as $cardSystem) {
            $data[$cardSystem->id] = $this->convertCardSystemToArray($cardSystem);
        }

        $content = Json::encode($data);

        parent::__construct($content, $status, $headers);
    }

    /**
     * Builds the same JSON shape from the UiTPAS REST value objects instead of the legacy
     * CultureFeed objects, so the response stays identical regardless of the client behind it.
     *
     * @param CardSystem[] $cardSystems
     */
    public static function fromCardSystems(
        array $cardSystems,
        int $status = 200,
        ?HeadersInterface $headers = null
    ): JsonResponse {
        $data = [];
        foreach ($cardSystems as $cardSystem) {
            $id = self::toIntId($cardSystem->getId()->toNative());

            $distributionKeys = [];
            foreach ($cardSystem->getDistributionKeys() as $distributionKey) {
                $distributionKeyId = self::toIntId($distributionKey->getId()->toNative());
                $distributionKeys[$distributionKeyId] = [
                    'id' => $distributionKeyId,
                    'name' => $distributionKey->getName(),
                ];
            }

            $data[$id] = [
                'id' => $id,
                'name' => $cardSystem->getName(),
                'distributionKeys' => $distributionKeys,
            ];
        }

        return new JsonResponse(Json::encode($data), $status, $headers);
    }

    /**
     * UiTPAS ids are integers per the API spec, but the Id value object holds them as strings.
     * Casting a non-numeric string with (int) would silently collapse to 0 and make all card
     * systems (or keys) overwrite each other, so fail loudly if the type ever changes instead.
     */
    private static function toIntId(string $id): int
    {
        $intId = filter_var($id, FILTER_VALIDATE_INT);
        if ($intId === false) {
            throw new UnexpectedValueException(sprintf('Expected a numeric UiTPAS id but got "%s".', $id));
        }

        return $intId;
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
