<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

use CultuurNet\UDB3\Json;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Http\Adapter\Guzzle7\Client;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class GeopuntAddressParser implements AddressParser, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ClientInterface $httpClient;
    private Uri $apiUrlV4;

    public function __construct(
        string $apiUrlV4,
        ?ClientInterface $httpClient = null
    ) {
        $this->httpClient = $httpClient ?? Client::createWithConfig(['http_errors' => false]);
        $this->apiUrlV4 = new Uri(rtrim($apiUrlV4, '/'));
        $this->logger = new NullLogger();
    }

    public function parse(string $formattedAddress): ?ParsedAddress
    {
        $url = $this->apiUrlV4
            ->withPath($this->apiUrlV4->getPath() . '/Location')
            ->withQuery('q=' . $formattedAddress);

        $request = new Request('GET', $url);
        $response = $this->httpClient->sendRequest($request);
        $body = $response->getBody()->getContents();
        $status = $response->getStatusCode();
        $isStatusOk = $status < 400;

        $logLevel = $isStatusOk ? 'info' : 'error';
        $this->logger->log(
            $logLevel,
            'GET ' . $url->__toString() . ' responded with status code ' . $status . ' and body ' . $body
        );

        if (!$isStatusOk) {
            return null;
        }

        try {
            $data = Json::decode($body);
        } catch (\JsonException $e) {
            $this->logger->error('Caught \JsonException while decoding response body.', ['message' => $e->getMessage()]);
            return null;
        }

        $schema = [
            'type' => 'object',
            'properties' => [
                'LocationResult' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'Municipality' => ['type' => 'string'],
                            'Zipcode' => [
                                'anyOf' => [
                                    ['type' => 'string'],
                                    ['type' => 'null'],
                                ],
                            ],
                            'Thoroughfarename' => [
                                'anyOf' => [
                                    ['type' => 'string'],
                                    ['type' => 'null'],
                                ],
                            ],
                            'Housenumber' => [
                                'anyOf' => [
                                    ['type' => 'string'],
                                    ['type' => 'null'],
                                ],
                            ],
                        ],
                        'required' => ['Municipality', 'Zipcode', 'Thoroughfarename', 'Housenumber'],
                    ],
                ],
            ],
            'required' => ['LocationResult'],
        ];

        $validator = new Validator(null, 100);
        $result = $validator->validate($data, Json::encode($schema));
        if (!$result->isValid()) {
            $errors = (new ErrorFormatter())->format($result->error());
            $this->logger->error(
                'Response body did not match the expected JSON schema. Did the API introduce a breaking change?',
                ['errors' => $errors]
            );
            return null;
        }

        if (!isset($data->LocationResult[0])) {
            $this->logger->info('Response body did not contain any array items inside "LocationResult" property. Either the address is not located in Belgium, or it is not recognized as an official address.');
            return null;
        }
        $result = $data->LocationResult[0];

        $this->logger->info('Successfully parsed response body.');
        return new ParsedAddress(
            $result->Thoroughfarename,
            $result->Housenumber,
            $result->Zipcode,
            $result->Municipality
        );
    }
}
