<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\User\ManagementToken\ManagementToken;
use CultuurNet\UDB3\User\ManagementToken\ManagementTokenGenerator;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class FixMultipleEventBookingInfoTest extends TestCase
{
    private const BASE_URL = 'https://io.uitdatabank.test';
    private const EVENT_ID = '4d6f7e2a-1234-4b9f-8cb5-6ebd71445307';

    private ClientInterface&MockObject $httpClient;

    private FixMultipleEventBookingInfo $command;

    /**
     * @var RequestInterface[]
     */
    private array $sentRequests = [];

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);

        $tokenGenerator = $this->createMock(ManagementTokenGenerator::class);
        $tokenGenerator->method('newToken')->willReturn(new ManagementToken('a-token', 300));

        $this->command = new FixMultipleEventBookingInfo(
            $this->httpClient,
            $tokenGenerator,
            self::BASE_URL,
            new NullLogger()
        );
    }

    public function test_it_copies_the_sub_event_url_to_the_top_level_for_a_single_calendar_type(): void
    {
        $event = [
            '@id' => self::BASE_URL . '/event/' . self::EVENT_ID,
            'calendarType' => 'single',
            'bookingInfo' => [
                'phone' => '016 12 34 56',
                'email' => 'info@example.com',
            ],
            'subEvent' => [
                [
                    'bookingInfo' => [
                        'url' => 'https://example.com/tickets',
                        'urlLabel' => ['nl' => 'Reserveer plaatsen'],
                    ],
                ],
            ],
        ];

        $putBody = $this->runForEvent($event);

        $this->assertSame(
            [
                'phone' => '016 12 34 56',
                'email' => 'info@example.com',
                'url' => 'https://example.com/tickets',
                'urlLabel' => ['nl' => 'Reserveer plaatsen'],
            ],
            $putBody
        );
    }

    public function test_it_copies_the_shared_sub_event_url_to_the_top_level_for_a_multiple_calendar_type(): void
    {
        $event = [
            '@id' => self::BASE_URL . '/event/' . self::EVENT_ID,
            'calendarType' => 'multiple',
            'bookingInfo' => [
                'phone' => '016 12 34 56',
                'email' => 'info@example.com',
            ],
            'subEvent' => [
                [
                    'bookingInfo' => [
                        'url' => 'https://example.com/tickets',
                        'urlLabel' => ['nl' => 'Reserveer plaatsen'],
                    ],
                ],
                [
                    'bookingInfo' => [
                        'url' => 'https://example.com/tickets',
                        'urlLabel' => ['nl' => 'Reserveer plaatsen'],
                    ],
                ],
            ],
        ];

        $putBody = $this->runForEvent($event);

        $this->assertSame(
            [
                'phone' => '016 12 34 56',
                'email' => 'info@example.com',
                'url' => 'https://example.com/tickets',
                'urlLabel' => ['nl' => 'Reserveer plaatsen'],
            ],
            $putBody
        );
    }

    /**
     * Runs the command against a single event and returns the decoded body of the PUT that was sent to the
     * booking-info endpoint.
     *
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    private function runForEvent(array $event): array
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use ($event): Response {
                $this->sentRequests[] = $request;

                if ($request->getMethod() === 'GET') {
                    return new Response(200, [], Json::encode($event));
                }

                return new Response(204);
            });

        $idsFile = $this->createIdsFile([self::EVENT_ID]);

        $output = new BufferedOutput();
        $returnCode = $this->command->run(new ArrayInput(['file' => $idsFile]), $output);

        $this->assertSame(0, $returnCode, $output->fetch());

        $getRequest = $this->sentRequests[0];
        $this->assertSame('GET', $getRequest->getMethod());
        $this->assertSame('/events/' . self::EVENT_ID, $getRequest->getUri()->getPath());

        $putRequest = $this->sentRequests[1];
        $this->assertSame('PUT', $putRequest->getMethod());
        $this->assertSame('/events/' . self::EVENT_ID . '/booking-info/', $putRequest->getUri()->getPath());

        return Json::decodeAssociatively($putRequest->getBody()->getContents());
    }

    /**
     * @param string[] $ids
     */
    private function createIdsFile(array $ids): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ids');
        file_put_contents($path, implode(PHP_EOL, $ids) . PHP_EOL);

        return $path;
    }
}
