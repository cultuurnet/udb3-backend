<?php

namespace CultuurNet\UDB3\Kinepolis;

interface TrailerRepository
{
    public function search(string $title): ?string;
}