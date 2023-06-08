<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

final class Fixtures
{
    public function loadJson(string $filename, Variables $variables): string
    {
        $organizer = file_get_contents(__DIR__ . '/../data/' . $filename);
        $name = $variables->getVariable('name');
        return str_replace('%{name}', $name, $organizer);
    }

    public function loadJsonWithRandomName(string $filename, Variables $variables): string
    {
        $variables->addRandomVariable('name', 10);
        return $this->loadJson($filename, $variables);
    }
}
