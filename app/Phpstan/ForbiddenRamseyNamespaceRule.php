<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Phpstan;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Use_>
 */
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

            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'The "Ramsey" namespace is not allowed in file: %s, please us %s',
                        $scope->getFile(),
                        Uuid::class
                    )
                )->identifier('udb3.forbiddenRamseyNamespace')->build(),
            ];
        }

        return [];
    }
}
