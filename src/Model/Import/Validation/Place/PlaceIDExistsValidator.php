<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Validation\Place;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Respect\Validation\Exceptions\CallbackException;
use Respect\Validation\Rules\Callback;

class PlaceIDExistsValidator extends Callback
{
    public function __construct(
        UUIDParser $placeIDParser,
        DocumentRepository $placeDocumentRepository
    ) {
        $callback = function ($idUrl) use ($placeIDParser, $placeDocumentRepository) {
            try {
                $url = new Url($idUrl);
                $id = $placeIDParser->fromUrl($url);
                $placeDocumentRepository->fetch($id->toString());
                return true;
            } catch (DocumentDoesNotExist $e) {
                // The place is deleted, so it can't be coupled to the event.
                return false;
            } catch (\Exception $e) {
                // The @id url is invalid. This is handled by another validator.
                return true;
            }
        };

        parent::__construct($callback);

        $this->setTemplate('Location with id {{name}} does not exist.');
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
