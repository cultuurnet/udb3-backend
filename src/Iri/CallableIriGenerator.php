<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Iri;

/**
 * A IRI generator that delegates its duties to a PHP callable.
 *
 * @link http://php.net/manual/en/language.types.callable.php
 */
class CallableIriGenerator implements IriGeneratorInterface
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * Constructs a new CallableIriGenerator.
     *
     * @param callable $callback
     *   The callback to delegate the generation of IRIs to. The callable needs
     *   to take one string argument, the item to generate the IRI for.
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function iri($item)
    {
        $callback = $this->callback;
        return $callback($item);
    }
}
