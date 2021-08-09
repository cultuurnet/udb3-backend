<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\ApiProblem;

/**
 * One class used to construct every possible API problem, so we have a definitive list (for documentation), and we can
 * more easily avoid almost-the-same duplicates.
 *
 * Ideally only this class would construct new ApiProblem instances, and always using specifically named methods.
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
final class ApiProblem
{
    private string $type;
    private string $title;
    private int $status;
    private ?string $detail;
    private ?string $jsonPointer;

    private function __construct(
        string $type,
        string $title,
        int $status,
        ?string $detail = null,
        ?string $jsonPointer = null
    ) {
        $this->type = $type;
        $this->title = $title;
        $this->status = $status;
        $this->detail = $detail;
        $this->jsonPointer = $jsonPointer;
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
        return new self(
            'about:blank',
            'Internal Server Error',
            500,
            $detail
        );
    }

    public static function unauthorized(string $detail): self
    {
        // Don't use about:blank as type here, even though we could, so we can make the URL point to documentation how
        // to fix this.
        return new self(
            'https://api.publiq.be/probs/auth/unauthorized',
            'Unauthorized',
            401,
            $detail
        );
    }

    public static function forbidden(string $detail = null): self
    {
        return new self(
            'https://api.publiq.be/probs/auth/forbidden',
            'Forbidden',
            403,
            $detail
        );
    }

    public static function tokenNotSupported(string $detail): self
    {
        return new self(
            'https://api.publiq.be/probs/auth/token-not-supported',
            'Token not supported',
            400,
            $detail
        );
    }

    public static function bodyMissing(): self
    {
        return new self(
            'https://api.publiq.be/probs/body/missing',
            'Body missing',
            400
        );
    }

    public static function bodyInvalidSyntax(string $format): self
    {
        return new self(
            'https://api.publiq.be/probs/body/invalid-syntax',
            'Invalid body syntax',
            400,
            'The given request body could not be parsed as ' . $format . '.'
        );
    }

    public static function bodyInvalidData(string $detail, string $jsonPointer): self
    {
        return new self(
            'https://api.publiq.be/probs/body/invalid-data',
            'Invalid body data',
            400,
            $detail,
            $jsonPointer
        );
    }

    public static function userNotFound(string $detail): self
    {
        return new self(
            'https://api.publiq.be/probs/uitdatabank/user-not-found',
            'User not found',
            404,
            $detail
        );
    }

    public static function invalidEmailAddress(string $email): self
    {
        return new self(
            'https://api.publiq.be/probs/uitdatabank/invalid-email-address',
            'Invalid email address',
            400,
            sprintf('"%s" is not a valid email address', $email)
        );
    }

    public static function calendarTypeNotSupported(string $detail): self
    {
        return new self(
            'https://api.publiq.be/probs/uitdatabank/calendar-type-not-supported',
            'Calendar type not supported',
            400,
            $detail
        );
    }
}
