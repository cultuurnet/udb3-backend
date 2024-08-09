<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Log;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ContextEnrichingLoggerTest extends TestCase
{
    /**
     * @test
     */
    public function it_passes_additional_context_to_the_decorated_logger(): void
    {
        /** @var LoggerInterface&MockObject $decoratedLogger */
        $decoratedLogger = $this->createMock(LoggerInterface::class);
        $additionalContext = [
            'job_id' => 1,
        ];
        $logger = new ContextEnrichingLogger(
            $decoratedLogger,
            $additionalContext
        );

        $decoratedLogger->expects($this->once())
            ->method('log')
            ->with(
                LogLevel::DEBUG,
                'test',
                [
                    'foo' => 'bar',
                    'job_id' => 1,
                ]
            );
        $logger->log(
            LogLevel::DEBUG,
            'test',
            [
                'foo' => 'bar',
            ]
        );
    }
}
