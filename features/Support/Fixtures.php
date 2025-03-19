<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

use CultuurNet\UDB3\State\VariableState;

final class Fixtures
{
    public function loadJson(string $filename, VariableState $variableState): string
    {
        $json = file_get_contents(__DIR__ . '/../data/' . $filename);
        return $variableState->replaceVariables($json);
    }

    public function loadJsonWithRandomName(string $filename, VariableState $variableState): string
    {
        $variableState->setRandomVariable('name', 10);
        return $this->loadJson($filename, $variableState);
    }

    public function loadTurtle(string $filename, VariableState $variableState): string
    {
        $turtle = file_get_contents(__DIR__ . '/../data/' . $filename);
        return $variableState->replaceVariables($turtle);
    }

    public function loadMail(string $mailType): string
    {
        return file_get_contents(__DIR__ . '/../data/mails/' . $mailType . '.html');
    }
}
