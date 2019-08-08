<?php

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

class TimeSpan
{
    /**
     * @var \DateTimeInterface
     */
    protected $start;

    /**
     * @var \DateTimeInterface
     */
    protected $end;

    /**
     * Constructor
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ) {
        if ($end < $start) {
            throw new \InvalidArgumentException('End date can not be earlier than start date.');
        }

        $this->start = $start;
        $this->end = $end;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getEnd()
    {
        return $this->end;
    }
}
