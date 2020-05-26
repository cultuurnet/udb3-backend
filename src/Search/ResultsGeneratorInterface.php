<?php

namespace CultuurNet\UDB3\Search;

interface ResultsGeneratorInterface
{
    /**
     * @param string $query
     * @return \Iterator
     */
    public function search(string $query);
}
