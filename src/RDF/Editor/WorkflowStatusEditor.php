<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use DateTime;
use EasyRdf\Literal;
use EasyRdf\Resource;

final class WorkflowStatusEditor
{
    private array $workflowStatusMapping;

    private const PROPERTY_WORKFLOW_STATUS = 'udb:workflowStatus';
    private const PROPERTY_WORKFLOW_STATUS_DRAFT = 'https://data.publiq.be/concepts/workflowStatus/draft';
    private const PROPERTY_WORKFLOW_STATUS_READY_FOR_VALIDATION = 'https://data.publiq.be/concepts/workflowStatus/ready-for-validation';
    private const PROPERTY_WORKFLOW_STATUS_APPROVED = 'https://data.publiq.be/concepts/workflowStatus/approved';
    private const PROPERTY_WORKFLOW_STATUS_REJECTED = 'https://data.publiq.be/concepts/workflowStatus/rejected';
    private const PROPERTY_WORKFLOW_STATUS_DELETED = 'https://data.publiq.be/concepts/workflowStatus/deleted';
    private const PROPERTY_AVAILABLE_FROM = 'udb:availableFrom';

    public function __construct()
    {
        $this->workflowStatusMapping = [
            WorkflowStatus::DRAFT()->toString() => self::PROPERTY_WORKFLOW_STATUS_DRAFT,
            WorkflowStatus::READY_FOR_VALIDATION()->toString() => self::PROPERTY_WORKFLOW_STATUS_READY_FOR_VALIDATION,
            WorkflowStatus::APPROVED()->toString() => self::PROPERTY_WORKFLOW_STATUS_APPROVED,
            WorkflowStatus::REJECTED()->toString() => self::PROPERTY_WORKFLOW_STATUS_REJECTED,
            WorkflowStatus::DELETED()->toString() => self::PROPERTY_WORKFLOW_STATUS_DELETED,
        ];
    }

    public function setWorkflowStatus(Resource $resource, WorkflowStatus $workflowStatus): void
    {
        $resource->set(
            self::PROPERTY_WORKFLOW_STATUS,
            new Resource($this->workflowStatusMapping[$workflowStatus->toString()])
        );
    }

    public function setAvailableFrom(Resource $resource, \DateTimeImmutable $publicationDate): void
    {
        $resource->set(
            self::PROPERTY_AVAILABLE_FROM,
            new Literal($publicationDate->format(DateTime::ATOM), null, 'xsd:dateTime')
        );
    }
}