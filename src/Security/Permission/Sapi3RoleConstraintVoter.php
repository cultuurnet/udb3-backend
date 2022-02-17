<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Role\ReadModel\Constraints\UserConstraintsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Psr\Http\Message\UriInterface;
use CultuurNet\UDB3\StringLiteral;

class Sapi3RoleConstraintVoter implements PermissionVoter
{
    /**
     * @var UserConstraintsReadRepositoryInterface
     */
    private $userConstraintsReadRepository;

    /**
     * @var UriInterface
     */
    private $searchLocation;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var string|null
     */
    private $apiKey;

    /**
     * @var array
     */
    private $queryParameters;

    public function __construct(
        UserConstraintsReadRepositoryInterface $userConstraintsReadRepository,
        UriInterface $searchLocation,
        HttpClient $httpClient,
        ?string $apiKey,
        array $queryParameters
    ) {
        $this->userConstraintsReadRepository = $userConstraintsReadRepository;
        $this->searchLocation = $searchLocation;
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->queryParameters = $queryParameters;
    }

    public function isAllowed(
        Permission $permission,
        StringLiteral $itemId,
        StringLiteral $userId
    ): bool {
        $constraints = $this->userConstraintsReadRepository->getByUserAndPermission(
            $userId,
            $permission
        );
        if (count($constraints) < 1) {
            return false;
        }

        $query = $this->createQueryFromConstraints(
            $constraints,
            $itemId
        );

        $totalItems = $this->search($query);

        return $totalItems === 1;
    }

    private function createQueryString(
        StringLiteral $constraint,
        StringLiteral $resourceId
    ): string {
        $constraintStr = '(' . $constraint->toNative() . ')';
        $resourceIdStr = $resourceId->toNative();

        return '(' . $constraintStr . ' AND id:' . $resourceIdStr . ')';
    }

    private function createQueryFromConstraints(
        array $constraints,
        StringLiteral $resourceId
    ): string {
        $queryString = '';

        foreach ($constraints as $constraint) {
            if (strlen($queryString)) {
                $queryString .= ' OR ';
            }

            $queryString .= $this->createQueryString($constraint, $resourceId);
        }

        return $queryString;
    }

    private function search(string $query): int
    {
        $queryParameters =
            [
                'q' => $query,
                'start' => 0,
                'limit' => 1,
            ];

        $queryParameters += $this->queryParameters;

        $queryParameters = http_build_query($queryParameters);

        $headers = [];

        if ($this->apiKey) {
            $headers['X-Api-Key'] = $this->apiKey;
        }

        $url = $this->searchLocation->withQuery($queryParameters);

        $request = new Request(
            'GET',
            (string) $url,
            $headers
        );

        $response = $this->httpClient->sendRequest($request);

        $decodedResponse = json_decode(
            $response->getBody()->getContents()
        );

        return (int) $decodedResponse->{'totalItems'};
    }
}
