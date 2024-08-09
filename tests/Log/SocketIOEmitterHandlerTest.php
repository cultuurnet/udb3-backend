<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Log;

use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SocketIO\Emitter;

class SocketIOEmitterHandlerTest extends TestCase
{
    protected SocketIOEmitterHandler $handler;

    /**
     * @var Emitter&MockObject
     */
    protected $emitter;

    public function setUp(): void
    {
        // SocketIO\Emitter unfortunately does not adhere to an interface, so
        // we need to use the implementation and ensure all required
        // constructor arguments are provided.
        $this->emitter = $this
            ->getMockBuilder(Emitter::class)
            ->setConstructorArgs([new TestRedisClientDouble()])
            ->getMock();

        $this->handler = new SocketIOEmitterHandler($this->emitter);
    }

    protected function getRecord(
        int $level = Logger::WARNING,
        string $message = 'test',
        array $context = []
    ): array {
        return [
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => Logger::getLevelName($level),
            'channel' => 'test',
            'datetime' => \DateTime::createFromFormat(
                'U.u',
                sprintf('%.6F', microtime(true))
            ),
            'extra' => [],
        ];
    }

    /**
     * @test
     */
    public function it_emits_to_the_socketIOEmitter(): void
    {
        $context = ['job_id' => 1];

        $this->emitter->expects($this->once())
            ->method('emit')
            ->with('job_started', $context);

        $record = $this->getRecord(Logger::WARNING, 'job_started', $context);
        $this->handler->handle($record);
    }
}

class TestRedisClientDouble
{
    public function publish(): void
    {
    }
}
