<?php

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category as Udb3ModelCategory;
use InvalidArgumentException;

final class Theme extends Category
{
    public const DOMAIN = 'theme';

    public function __construct(string $id, string $label)
    {
        parent::__construct($id, $label, self::DOMAIN);
    }

    public static function deserialize(array $data): Theme
    {
        return new self($data['id'], $data['label']);
    }

    public static function fromUdb3ModelCategory(Udb3ModelCategory $category): Theme
    {
        $label = $category->getLabel();

        if (is_null($label)) {
            throw new InvalidArgumentException('Category label is required.');
        }

        return new self(
            $category->getId()->toString(),
            $label->toString()
        );
    }
}
