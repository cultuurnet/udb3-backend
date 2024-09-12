<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category as Udb3ModelCategory;
use InvalidArgumentException;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category instead where possible.
 */
class Category implements Serializable, JsonLdSerializableInterface
{
    protected string $id;

    protected string $label;

    protected string $domain;

    public function __construct(string $id, string $label, string $domain)
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Category ID can not be empty.');
        }

        $this->id = $id;
        $this->label = $label;
        $this->domain = $domain;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function serialize(): array
    {
        return [
          'id' => $this->id,
          'label' => $this->label,
          'domain' => $this->domain,
        ];
    }

    public static function deserialize(array $data): Category
    {
        return new self($data['id'], $data['label'], $data['domain']);
    }

    public function toJsonLd(): array
    {
        // Matches the serialized array.
        return $this->serialize();
    }

    public static function fromUdb3ModelCategory(Udb3ModelCategory $category): Category
    {
        $label = $category->getLabel();
        $domain = $category->getDomain();

        if (is_null($label)) {
            throw new InvalidArgumentException('Category label is required.');
        }

        if (is_null($domain)) {
            throw new InvalidArgumentException('Category domain is required.');
        }

        return new self(
            $category->getId()->toString(),
            $label->toString(),
            $domain->toString()
        );
    }
}
