<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Log;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use SocketIO\Emitter;

class SocketIOEmitterHandler extends AbstractProcessingHandler
{
    /**
     * @var Emitter
     */
    protected $emitter;

    /**
     * @param integer $level  The minimum logging level at which this handler will be triggered
     * @param Boolean $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(Emitter $emitter, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct(
            $level,
            $bubble
        );

        $this->emitter = $emitter;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        $event = $record['formatted']['message'];
        $data = $record['formatted']['context'];

        $this->emitter->emit($event, $data);
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultFormatter()
    {
        return new NormalizerFormatter();
    }
}
