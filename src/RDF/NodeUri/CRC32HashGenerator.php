<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\NodeUri;

// We use CRC32 to generate hashes for the RDF namespaces.
// We decided on CRC32 instead sha1/md5 because the hash is much shorter.
// Because all hashes are always prefixed by the type of node, the risk of collisions is very low.
use CultuurNet\UDB3\Json;

final class CRC32HashGenerator implements HashGenerator
{
    public function generate(array $data): string
    {
        $this->recursiveSort($data);
        return hash('crc32b', Json::encode($data));
    }

    private function recursiveSort(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveSort($value);
            }
        }
    }
}
