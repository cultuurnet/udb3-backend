<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use Psr\Http\Message\ServerRequestInterface;

final class DuplicateLabelValidatingRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $errors = [];

        $data = $request->getParsedBody();

        if (isset($data->labels, $data->hiddenLabels)) {
            $hiddenLabelsLowerCased = array_map(
                fn (string $labelName) => (new LabelName($labelName))->toLowerCase()->toString(),
                $data->hiddenLabels
            );
            foreach ($data->labels as $index => $label) {
                $labelLowerCased = (new LabelName($label))->toLowerCase()->toString();
                if (in_array($labelLowerCased, $hiddenLabelsLowerCased, true)) {
                    $errors[] = new SchemaError(
                        '/labels/' . $index,
                        'Label "' . $label . '" cannot be both in labels and hiddenLabels properties.'
                    );
                }
            }
        }

        if (count($errors) > 0) {
            throw ApiProblem::bodyInvalidData(...$errors);
        }

        return $request;
    }
}
