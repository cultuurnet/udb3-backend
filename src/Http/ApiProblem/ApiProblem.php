<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\ApiProblem;

use CultuurNet\UDB3\Offer\OfferType;
use Exception;

/**
 * One class used to construct every possible API problem, so we have a definitive list (for documentation), and we can
 * more easily avoid almost-the-same duplicates.
 *
 * See https://datatracker.ietf.org/doc/html/rfc7807 for info on API problems.
 *
 * Most important points to keep in mind:
 * - Type should be a URI
 * - Title should always be the same for the used type
 * - The "about:blank" type can only be used if the error needs no further explanation than the status code
 *     (for example, 400 is too vague)
 * - If the "about:blank" type is used, the title should be the HTTP status phrase for the used status code
 *     (for example, "Internal Server Error" for 500)
 * - Avoid using "about:blank" in cases where extra documentation can be helpful
 *     (since the URIs will link to documentation on Stoplight)
 */
final class ApiProblem extends Exception
{
    private string $type;
    private string $title;
    private int $status;
    private ?string $detail;
    private array $schemaErrors = [];
    private array $debugInfo = [];
    private array $extraProperties = [];

    /**
     * @deprecated
     *   Remove once withValidationMessages() has been removed.
     */
    private array $validationMessages = [];

    /**
     * new ApiProblem() always creates a 500 Internal Server Error.
     * For other problems, use a factory method instead.
     * If there is no problem for your error, create a new one with a specific type.
     * Make sure to also document it on the API documentation.
     */
    public function __construct()
    {
        parent::__construct();

        $this->type = 'about:blank';
        $this->title = 'Internal Server Error';
        $this->status = 500;

        $this->message = $this->title;
        $this->code = $this->status;
    }

    private static function create(
        string $type,
        string $title,
        int $status,
        ?string $detail = null,
        array $schemaErrors = []
    ): self {
        $problem = new ApiProblem();

        // Api problem properties.
        $problem->type = $type;
        $problem->title = $title;
        $problem->status = $status;
        $problem->detail = $detail;
        $problem->schemaErrors = $schemaErrors;

        // Exception properties.
        $problem->message = $title;
        $problem->code = $status;

        return $problem;
    }

    private function setExtraProperties(array $extraProperties): void
    {
        $this->extraProperties = $extraProperties;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function getSchemaErrors(): array
    {
        return $this->schemaErrors;
    }

    /**
     * @deprecated
     *   Remove once all usages of this are removed
     *   (i.e. when we don't throw DataValidationException and GroupedValidationException anymore)
     */
    public function setValidationMessages(array $validationMessages): void
    {
        $this->validationMessages = $validationMessages;
    }

    public function setDebugInfo(array $debugInfo): void
    {
        $this->debugInfo = $debugInfo;
    }

    public function toArray(): array
    {
        $json = [
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
        ];

        if ($this->detail) {
            $json['detail'] = $this->detail;
        }

        if (count($this->schemaErrors) > 0) {
            $json['schemaErrors'] = array_map(
                fn (SchemaError $schemaError) => [
                    'jsonPointer' => $schemaError->getJsonPointer(),
                    'error' => $schemaError->getError(),
                ],
                $this->schemaErrors
            );
        }

        if (count($this->extraProperties) > 0) {
            $json = array_merge($this->extraProperties, $json);
        }

        /** @deprecated Remove once withValidationMessages() is removed. */
        if (count($this->validationMessages) > 0) {
            $json['validation_messages'] = $this->validationMessages;
        }

        if (count($this->debugInfo) > 0) {
            $json['debug'] = $this->debugInfo;
        }

        return $json;
    }

    /**
     * @deprecated
     *   Use a named method instead with a specific type.
     *   Remove this method once all ApiProblem instances use a method for a more specific type.
     */
    public static function blank(
        string $title,
        int $status,
        ?string $detail = null,
        array $schemaErrors = []
    ): self {
        return self::create('about:blank', $title, $status, $detail, $schemaErrors);
    }

    public static function internalServerError(string $detail = ''): self
    {
        $problem = new self();
        $problem->detail = $detail;
        return $problem;
    }

    public static function unauthorized(string $detail): self
    {
        // Don't use about:blank as type here, even though we could, so we can make the URL point to documentation how
        // to fix this.
        return self::create(
            'https://api.publiq.be/probs/auth/unauthorized',
            'Unauthorized',
            401,
            $detail
        );
    }

    public static function forbidden(string $detail = null): self
    {
        return self::create(
            'https://api.publiq.be/probs/auth/forbidden',
            'Forbidden',
            403,
            $detail
        );
    }

    public static function notAcceptable(string $detail = null): self
    {
        return self::create(
            'https://api.publiq.be/probs/headers/not-acceptable',
            'Not Acceptable',
            406,
            $detail
        );
    }

    public static function urlNotFound(string $detail = null): self
    {
        return self::create(
            'https://api.publiq.be/probs/url/not-found',
            'Not Found',
            404,
            $detail
        );
    }

    public static function resourceNotFound(string $resourceType, string $resourceId): self
    {
        return self::urlNotFound('The ' . $resourceType . ' with id "' . $resourceId . '" was not found.');
    }

    public static function offerNotFound(OfferType $offerType, string $offerId): self
    {
        return self::resourceNotFound(strtolower($offerType->toString()), $offerId);
    }

    public static function eventNotFound(string $eventId): self
    {
        return self::offerNotFound(OfferType::event(), $eventId);
    }

    public static function placeNotFound(string $placeId): self
    {
        return self::offerNotFound(OfferType::place(), $placeId);
    }

    public static function organizerNotFound(string $organizerId): self
    {
        return self::resourceNotFound('Organizer', $organizerId);
    }

    public static function newsArticleNotFound(string $articleId): self
    {
        return self::resourceNotFound('News Article', $articleId);
    }

    public static function imageNotFound(string $imageId): self
    {
        return self::resourceNotFound('Image', $imageId);
    }

    public static function tokenNotSupported(string $detail): self
    {
        return self::create(
            'https://api.publiq.be/probs/auth/token-not-supported',
            'Token not supported',
            400,
            $detail
        );
    }

    public static function bodyMissing(): self
    {
        return self::create(
            'https://api.publiq.be/probs/body/missing',
            'Body missing',
            400
        );
    }

    public static function bodyInvalidSyntax(string $format): self
    {
        return self::create(
            'https://api.publiq.be/probs/body/invalid-syntax',
            'Invalid body syntax',
            400,
            'The given request body could not be parsed as ' . $format . '.'
        );
    }

    public static function bodyInvalidData(SchemaError ...$schemaErrors): self
    {
        return self::create(
            'https://api.publiq.be/probs/body/invalid-data',
            'Invalid body data',
            400,
            null,
            $schemaErrors
        );
    }

    public static function bodyInvalidDataWithDetail(string $detail): self
    {
        return self::create(
            'https://api.publiq.be/probs/body/invalid-data',
            'Invalid body data',
            400,
            $detail
        );
    }

    public static function queryParameterInvalidValue(string $parameterName, string $value, array $allowedValues): self
    {
        return self::create(
            'https://api.publiq.be/probs/url/query-parameter-invalid',
            'Query parameter invalid',
            400,
            'Query parameter ' . $parameterName . ' has invalid value "' . $value . '". Should be one of ' . implode(', ', $allowedValues)
        );
    }

    public static function pathParameterInvalid(string $detail): self
    {
        return self::create(
            'https://api.publiq.be/probs/url/path-parameter-invalid',
            'Path parameter invalid',
            400,
            $detail
        );
    }

    public static function userNotFound(string $detail): self
    {
        return self::create(
            'https://api.publiq.be/probs/uitdatabank/user-not-found',
            'User not found',
            404,
            $detail
        );
    }

    public static function invalidEmailAddress(string $email): self
    {
        return self::create(
            'https://api.publiq.be/probs/uitdatabank/invalid-email-address',
            'Invalid email address',
            400,
            sprintf('"%s" is not a valid email address', $email)
        );
    }

    public static function calendarTypeNotSupported(string $detail): self
    {
        return self::create(
            'https://api.publiq.be/probs/uitdatabank/calendar-type-not-supported',
            'Calendar type not supported',
            400,
            $detail
        );
    }

    public static function inCompatibleAudienceType(string $detail): self
    {
        return self::create(
            'https://api.publiq.be/probs/uitdatabank/incompatible-audience-type',
            'Incompatible audience type',
            400,
            $detail
        );
    }

    public static function resourceIdAlreadyInUse(string $id): self
    {
        return self::create(
            'https://api.publiq.be/probs/uitdatabank/resource-id-already-in-use',
            'Resource id already in use',
            400,
            'The id ' . $id . ' is already in use by a resource of a different type.'
        );
    }

    public static function duplicateUrl(string $originalUrl, string $normalized): self
    {
        return self::create(
            'https://api.publiq.be/probs/uitdatabank/duplicate-url',
            'Duplicate URL',
            400,
            'The url ' . $originalUrl . ' (normalized to ' . $normalized . ') is already in use.'
        );
    }

    public static function labelNotAllowed(string $labelName): self
    {
        $e = self::create(
            'https://api.publiq.be/probs/uitdatabank/label-not-allowed',
            'Label not allowed',
            403,
            'The label "' . $labelName .  '" is reserved and you do not have sufficient permissions to use it.'
        );
        $e->setExtraProperties(['label' => $labelName]);
        return $e;
    }
}
