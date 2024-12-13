<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Broadway\Domain\DomainMessageIsReplayed;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory\UuidFactory;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory\GeneratedUuidFactory;

class DomainMessageBuilder
{
    private ?string $userId = null;
    private ?string $id = null;
    private ?int $playhead = null;
    private ?DateTime $recordedOn = null;
    private ?bool $forReplay = false;
    private UuidFactory $uuidFactory;

    public function __construct(UuidFactory $uuidFactory = null)
    {
        if ($uuidFactory === null) {
            $uuidFactory = new GeneratedUuidFactory();
        }

        $this->uuidFactory = $uuidFactory;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function setRecordedOnFromDateTimeString(string $dateTime): self
    {
        $this->recordedOn = DateTime::fromString($dateTime);

        return $this;
    }

    public function setPlayhead(int $i): self
    {
        $this->playhead = $i;

        return $this;
    }

    public function forReplay(bool $forReplay = true): self
    {
        $this->forReplay = $forReplay;

        return $this;
    }

    public function create(object $payload): DomainMessage
    {
        $finalMetaData = new Metadata();

        $finalMetaData = $finalMetaData->merge(
            new Metadata(
                [
                    'user_id' => $this->userId ?? $this->uuidFactory->uuid4()->toString(),
                ]
            )
        );

        $message = new DomainMessage(
            $this->id ?? $this->uuidFactory->uuid4()->toString(),
            $this->playhead ?? 1,
            $finalMetaData,
            $payload,
            $this->recordedOn ?? DateTime::now()
        );

        if (is_bool($this->forReplay)) {
            $replayMetadata = new Metadata(
                [
                    DomainMessageIsReplayed::METADATA_REPLAY_KEY => $this->forReplay,
                ]
            );

            $message = $message->andMetadata($replayMetadata);
        }

        return $message;
    }
}
