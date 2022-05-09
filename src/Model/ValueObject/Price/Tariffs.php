<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

class Tariffs extends Collection
{
    public function __construct(Tariff ...$tariffs)
    {
        parent::__construct(...$tariffs);
    }

    public function hasDuplicateNames(): bool
    {
        $matrix = $this->geTariffsMatrix();

        foreach ($matrix as $languageTariffs) {
            if (count($languageTariffs) !== count(array_unique($languageTariffs))) {
                return true;
            }
        }
        return false;
    }

    public function getDuplicatesNames(): array
    {
        $matrix = $this->geTariffsMatrix();
        $duplicateNames = [];

        foreach ($matrix as $languageCode => $languageTariffs) {
            $tempTable = [];
            foreach ($languageTariffs as $i => $tariffName) {
                if (in_array($tariffName, $tempTable, true)) {
                    $duplicateNames[] = [
                        'index' => $i,
                        'language' => $languageCode,
                        'name' => $tariffName,
                    ];
                } else {
                    $tempTable[] = $tariffName;
                }
            }
        }

        return $duplicateNames;
    }

    private function geTariffsMatrix(): array
    {
        $tariffsMatrix = [];
        /** @var Tariff $tariff */
        foreach ($this->toArray() as $tariff) {
            $languages = $tariff->getName()->getLanguages();
            /** @var Language $language */
            foreach ($languages as $language) {
                $tariffsMatrix[$language->getCode()][] = $tariff->getName()->getTranslation($language)->toString();
            }
        }

        return $tariffsMatrix;
    }
}
