<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\ApiProblem;

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
    private ?string $jsonPointer;
    private array $debugInfo = [];

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
        ?string $jsonPointer = null
    ): self {
        $problem = new ApiProblem();

        // Api problem properties.
        $problem->type = $type;
        $problem->title = $title;
        $problem->status = $status;
        $problem->detail = $detail;
        $problem->jsonPointer = $jsonPointer;

        // Exception properties.
        $problem->message = $title;
        $problem->code = $status;
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

    public function getJsonPointer(): ?string
    {
        return $this->jsonPointer;
    }

    /**
     * @deprecated
     *   Remove once all usages of this are removed
     *   (i.e. when we don't throw DataValidationException and GroupedValidationException anymore)
     */
    public function withValidationMessages(array $validationMessages): self
    {
        $c = clone $this;
        $c->validationMessages = $validationMessages;
        return $c;
    }

    public function withDebugInfo(array $debugInfo): self
    {
        $c = clone $this;
        $c->debugInfo = $debugInfo;
        return $c;
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
        if ($this->jsonPointer) {
            $json['jsonPointer'] = $this->jsonPointer;
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
     *   Remove this method once all ApiProblem instances use a named method.
     */
    public static function custom(
        string $type,
        string $title,
        int $status,
        ?string $detail = null,
        ?string $jsonPointer = null
    ): self {
        return new self($type, $title, $status, $detail, $jsonPointer);
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

    public static function bodyInvalidData(string $detail, string $jsonPointer): self
    {
        return self::create(
            'https://api.publiq.be/probs/body/invalid-data',
            'Invalid body data',
            400,
            $detail,
            $jsonPointer
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
}
