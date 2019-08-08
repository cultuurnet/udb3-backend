<?php

namespace CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category;

use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryResolverInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use Respect\Validation\Exceptions\CallbackException;
use Respect\Validation\Rules\Callback;

class CategoryIDExistsValidator extends Callback
{
    /**
     * @param CategoryResolverInterface $categoryResolver
     */
    public function __construct(CategoryResolverInterface $categoryResolver)
    {
        $callback = function ($id) use ($categoryResolver) {
            return !is_null($categoryResolver->byId(new CategoryID((string) $id)));
        };

        parent::__construct($callback);
    }

    /**
     * @param string $input
     * @return bool
     */
    public function validate($input)
    {
        $this->setName($input);
        return parent::validate($input);
    }

    /**
     * @return CallbackException
     */
    protected function createException()
    {
        return new CallbackException();
    }
}
