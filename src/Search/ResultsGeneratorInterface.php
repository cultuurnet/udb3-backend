<?php

namespace CultuurNet\UDB3\Search;

interface ResultsGeneratorInterface
{
    public function count(string $query): int;

    /**
     * @param string $query
     * @return \Iterator
     */
    public function search(string $query);
}
