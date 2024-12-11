<?php

declare(strict_types=1);

namespace CultuurNet\phpstan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

class ForbiddenRamseyNamespaceRule implements Rule
{
    public function getNodeType(): string
    {
        return Use_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        foreach ($node->uses as $use) {
            if (! str_contains($use->name->toString(), 'Ramsey')) {
                continue;
            }

            $filePath = $scope->getFile();
            if (str_ends_with($filePath, 'src/Model/ValueObject/Identity/Uuid.php')) {
                continue;
            }

            return [
                sprintf(
                    'The "Ramsey" namespace is not allowed in file: %s, please us CultuurNet\UDB3\Model\ValueObject\Identity\Uuid',
                    $filePath
                ),
            ];
        }

        return [];
    }
}
