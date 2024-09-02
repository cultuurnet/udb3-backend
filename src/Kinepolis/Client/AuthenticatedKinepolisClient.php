<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis\Client;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Kinepolis\Exception\ImageNotFound;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\UploadedFile;
use Psr\Http\Client\ClientInterface;

final class AuthenticatedKinepolisClient implements KinepolisClient
{
    private string $movieApiBaseUrl;

    private string $key;

    private string $secret;

    private ClientInterface $client;

    public function __construct(
        string $movieApiBaseUrl,
        ClientInterface $client,
        string $key,
        string $secret
    ) {
        $this->movieApiBaseUrl = $movieApiBaseUrl;
        $this->client = $client;
        $this->key = $key;
        $this->secret = $secret;
    }

    public function getToken(): string
    {
        $request = new Request(
            'POST',
            $this->movieApiBaseUrl . '/services/jwt/1.0/token',
            $this->createHeaders(),
            Json::encode([
                'client' => $this->key,
                'secret' => $this->secret,
            ])
        );
        $response = $this->client->sendRequest($request)->getBody()->getContents();
        $contents = Json::decodeAssociatively($response);
        return $contents['token'];
    }

    public function getMovies(string $token): array
    {
        $request = new Request(
            'GET',
            $this->movieApiBaseUrl . '/services/content/1.1/movies?progList=2',
            $this->createHeaders($token)
        );

        $response = $this->client->sendRequest($request)->getBody()->getContents();
        $contents = Json::decodeAssociatively($response);
        return $contents['movies'];
    }

    public function getMovieDetail(string $token, int $mid): array
    {
        $request = new Request(
            'GET',
            $this->movieApiBaseUrl . 'services/content/1.1/movies/' . $mid,
            $this->createHeaders($token)
        );

        $response = $this->client->sendRequest($request)->getBody()->getContents();
        $contents = Json::decodeAssociatively($response);
        return $contents['movies'][0];
    }

    public function getImage(string $token, string $path): UploadedFile
    {
        $fileName = $this->getFileName($path);

        $headers = $this->createHeaders($token);
        $headers['Accept'] = 'image/jpeg';
        $request = new Request(
            'GET',
            $this->movieApiBaseUrl . $path,
            $headers
        );

        $response = $this->client->sendRequest($request);

        return new UploadedFile(
            $response->getBody(),
            $response->getBody()->getSize(),
            UPLOAD_ERR_OK,
            $fileName,
            $response->getHeaderLine('Content-Type')
        );
    }

    public function getTheaters(string $token): array
    {
        $request = new Request(
            'GET',
            $this->movieApiBaseUrl . 'services/content/1.1/theaters',
            $this->createHeaders($token)
        );

        $response = $this->client->sendRequest($request)->getBody()->getContents();
        $contents = Json::decodeAssociatively($response);

        return $contents['theatres'];
    }

    public function getPricesForATheater(string $token, string $tid): array
    {
        $request = new Request(
            'GET',
            $this->movieApiBaseUrl . 'services/content/1.1/theaters/' . $tid,
            $this->createHeaders($token)
        );

        $response = $this->client->sendRequest($request)->getBody()->getContents();
        $contents = Json::decodeAssociatively($response);

        return $contents['theatres'][0]['tariffs'][0]['tarifs'];
    }

    private function createHeaders(string $token = null): array
    {
        $headers = [
            'content-type' => 'application/json',
            'User-Agent' => 'Kinepolis-Publiq',
        ];
        if ($token !== null) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        return $headers;
    }

    private function getFileName(string $path): string
    {
        $fileName = substr($path, strrpos($path, '/') + 1);
        // When we are on PHP 8, the check on false can be removed.
        if ($fileName === false || $fileName === '') {
            throw new ImageNotFound(sprintf('Cannot process path: "%s"', $path));
        }
        return $fileName;
    }
}
