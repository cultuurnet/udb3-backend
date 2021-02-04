<?php

namespace CultuurNet\BroadwayAMQP\Dummies;

use Broadway\Serializer\SerializableInterface;

class DummyEvent implements SerializableInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $content;

    /**
     * @param string $id
     * @param string $content
     */
    public function __construct($id, $content)
    {
        $this->id = $id;
        $this->content = $content;
    }

    /**
     * @param array $data
     * @return mixed The object instance
     */
    public static function deserialize(array $data)
    {
        return new static(
            $data['id'],
            $data['content']
        );
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
        ];
    }
}
