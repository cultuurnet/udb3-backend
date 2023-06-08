<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

use CultuurNet\UDB3\State\Variables;

final class Fixtures
{
    public function loadJson(string $filename, Variables $variables): string
    {
        $json = file_get_contents(__DIR__ . '/../data/' . $filename);

        foreach ($variables->getVariables() as $key => $value) {
            $json = str_replace('%{' . $key . '}', $value, $json);
        }

        return $json;
    }

    public function loadJsonWithRandomName(string $filename, Variables $variables): string
    {
        $variables->addRandomVariable('name', 10);
        return $this->loadJson($filename, $variables);
    }
}
