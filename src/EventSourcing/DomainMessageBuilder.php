<?php

namespace CultuurNet\UDB3\EventSourcing;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\Broadway\Domain\DomainMessageIsReplayed;
use ValueObjects\Identity\UUID;

/**
 * Helper class for building domain messages, to be used in automated tests.
 */
class DomainMessageBuilder
{
    /**
     * @var string $userId
     */
    private $userId;

    /**
     * @var string $id
     */
    private $id;

    /**
     * @var int
     */
    private $playhead;

    /**
     * @var DateTime
     */
    private $recordedOn;

    /**
     * @var null|bool
     */
    private $forReplay;

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

    /**
     * @return \Broadway\Domain\DomainMessage
     */
    public function create($payload)
    {
        $finalMetaData = new Metadata();

        $finalMetaData = $finalMetaData->merge(
            new Metadata(
                [
                    'user_id' => $this->userId ?? UUID::generateAsString(),
                ]
            )
        );

        $message =  new DomainMessage(
            $this->id ?? UUID::generateAsString(),
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
