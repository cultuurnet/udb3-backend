<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class DBALDatabaseConnectionCheckerTest extends TestCase
{
    private Connection&MockObject $connection;

    private LoggerInterface&MockObject $logger;

    private DBALDatabaseConnectionChecker $connectionChecker;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->connectionChecker = new DBALDatabaseConnectionChecker(
            $this->connection,
            $this->logger
        );
    }

    /**
     * @test
     */
    public function it_logs_success_when_connection_is_healthy(): void
    {
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT 1');

        $this->connection->expects($this->never())
            ->method('close');

        $this->connection->expects($this->never())
            ->method('connect');

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Connection to database successfully verified');

        $this->connectionChecker->ensureConnection();
    }

    /**
     * @test
     */
    public function it_reconnects_when_connection_is_lost(): void
    {
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT 1')
            ->willThrowException(new Exception('Connection lost'));

        $this->connection->expects($this->once())
            ->method('close');

        $this->connection->expects($this->once())
            ->method('connect');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Database connection lost, reconnecting...', [
                'exception_message' => 'Connection lost',
            ]);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Successfully reconnected to database');

        $this->connectionChecker->ensureConnection();
    }

    /**
     * @test
     */
    public function it_logs_critical_when_reconnection_fails(): void
    {
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT 1')
            ->willThrowException(new Exception('Connection lost'));

        $this->connection->expects($this->once())
            ->method('close');

        $this->connection->expects($this->once())
            ->method('connect')
            ->willThrowException(new Exception('Cannot reconnect'));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Database connection lost, reconnecting...', [
                'exception_message' => 'Connection lost',
            ]);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Failed to reconnect to database', [
                'exception_message' => 'Cannot reconnect',
            ]);

        $this->connectionChecker->ensureConnection();
    }
}
