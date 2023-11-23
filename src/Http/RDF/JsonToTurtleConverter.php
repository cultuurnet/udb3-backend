<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\RDF;

interface JsonToTurtleConverter
{
    public function convert(string $id): string;
}
