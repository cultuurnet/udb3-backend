<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Label;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelRelationsRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Rules\AbstractRule;
use ValueObjects\StringLiteral\StringLiteral;

class LabelPermissionRule extends AbstractRule
{
    /**
     * @var UUID
     */
    private $documentId;

    /**
     * @var string
     */
    private $userId;

    /**
     * @var LabelsRepository
     */
    private $labelsRepository;

    /**
     * @var LabelRelationsRepository
     */
    private $labelRelationsRepository;


    public function __construct(
        UUID $documentId,
        string $userId,
        LabelsRepository $labelsRepository,
        LabelRelationsRepository $labelsRelationsRepository
    ) {
        $this->documentId = $documentId;
        $this->userId = $userId;
        $this->labelsRepository = $labelsRepository;
        $this->labelRelationsRepository = $labelsRelationsRepository;
    }

    /**
     * @param string $input
     * @return bool
     */
    public function validate($input)
    {
        $this->setName($input);

        // If the label is already present on the item no permission check is needed.
        $labelRelations = $this->labelRelationsRepository->getLabelRelationsForItem(
            new StringLiteral($this->documentId->toString())
        );
        foreach ($labelRelations as $labelRelation) {
            if ($labelRelation->getLabelName()->toNative() === $input) {
                return true;
            }
        }

        // The label is not yet present on the item, do a permission check for the active user.
        return $this->labelsRepository->canUseLabel(
            new StringLiteral($this->userId),
            new StringLiteral($input)
        );
    }

    /**
     * @inheritdoc
     */
    protected function createException()
    {
        $validationException = new ValidationException();
        return $validationException->setTemplate('no permission to use label {{name}}');
    }
}
