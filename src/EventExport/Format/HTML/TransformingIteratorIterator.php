<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\HTML;

use \Traversable;
use \IteratorIterator;

/**
 * Iterator iterator that uses a callable to transform the current item.
 */
class TransformingIteratorIterator extends IteratorIterator
{
    /** @var callable */
    private $function;

    public function __construct(Traversable $iterator, callable $function)
    {
        parent::__construct($iterator);
        $this->function = $function;
    }

    public function current()
    {
        $fn = $this->function;
        $current = parent::current();
        return $fn($current, parent::key());
    }
}
