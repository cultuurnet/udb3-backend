<?php

namespace CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Label;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelRelationsRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Security\UserIdentificationInterface;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Rules\AbstractRule;

class DocumentLabelPermissionRule extends AbstractRule
{
    /**
     * @var UUIDParser
     */
    private $uuidParser;

    /**
     * @var UserIdentificationInterface
     */
    private $userIdentification;

    /**
     * @var LabelsRepository
     */
    private $labelsRepository;

    /**
     * @var LabelRelationsRepository
     */
    private $labelRelationsRepository;


    public function __construct(
        UUIDParser $uuidParser,
        UserIdentificationInterface $userIdentification,
        LabelsRepository $labelsRepository,
        LabelRelationsRepository $labelsRelationsRepository
    ) {
        $this->uuidParser = $uuidParser;
        $this->userIdentification = $userIdentification;
        $this->labelsRepository = $labelsRepository;
        $this->labelRelationsRepository = $labelsRelationsRepository;
    }

    public function validate($input)
    {
        // When no @id on the document return it as valid.
        // Later checks will catch this error.
        if (!isset($input['@id'])) {
            return true;
        }

        try {
            $idUrl = new Url($input['@id']);
            $id = $this->uuidParser->fromUrl($idUrl);
        } catch (\InvalidArgumentException $exception) {
            // When not a valid UUID return as valid.
            // Later checks will catch this issue.
            return true;
        }

        $labelPermissionRule = new LabelPermissionRule(
            $id,
            $this->userIdentification,
            $this->labelsRepository,
            $this->labelRelationsRepository
        );

        // Validate all visible labels
        $invalidVisibleLabels = [];
        if (isset($input['labels'])) {
            $invalidVisibleLabels = $this->validateLabels(
                $labelPermissionRule,
                $input['labels']
            );
        }

        // Get all hidden labels
        $invalidHiddenLabels = [];
        if (isset($input['hiddenLabels'])) {
            $invalidHiddenLabels = $this->validateLabels(
                $labelPermissionRule,
                $input['hiddenLabels']
            );
        }

        $invalidLabels = array_merge($invalidVisibleLabels, $invalidHiddenLabels);

        $this->setName(implode(', ', $invalidLabels));

        return count($invalidLabels) === 0;
    }

    protected function createException()
    {
        $validationException = new ValidationException();
        return $validationException->setTemplate('no permission to use labels {{name}}');
    }

    /**
     * @param string[] $labels
     * @return string[] $invalidLabels
     */
    private function validateLabels(LabelPermissionRule $labelPermissionRule, $labels)
    {
        $invalidLabels = [];

        foreach ($labels as $label) {
            if (!$labelPermissionRule->validate($label)) {
                $invalidLabels[] = $label;
            }
        }

        return $invalidLabels;
    }
}
