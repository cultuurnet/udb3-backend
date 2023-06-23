<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

use Stringy\Stringy as Stringy;

class TruncateStringFilter implements StringFilterInterface
{
    protected bool $wordSafe = false;

    protected int $minWordSafeLength = 0;

    protected bool $addEllipsis = false;

    protected bool $spaceBeforeEllipsis = false;

    protected bool $sentenceFriendly = false;

    protected int $maxLength;

    public function __construct(int $maxLength)
    {
        $this->setMaxLength($maxLength);
    }

    public function setMaxLength(int $maxLength): void
    {
        $this->maxLength = $maxLength;
    }

    public function addEllipsis(bool $toggle = true): void
    {
        $this->addEllipsis = $toggle;
    }

    public function spaceBeforeEllipsis(bool $toggle = true): void
    {
        $this->spaceBeforeEllipsis = $toggle;
    }

    public function turnOnWordSafe(int $minWordSafeLength = 1): void
    {
        $this->wordSafe = true;
        $this->minWordSafeLength = $minWordSafeLength;
    }

    /**
     * When turned on, the filter will try not to truncate in the middle of a sentence.
     */
    public function beSentenceFriendly(): void
    {
        $this->sentenceFriendly = true;
    }

    public function filter(string $string): string
    {
        // Maximum length and minimum length to enable word-safe truncating should always be greater than zero.
        $maxLength = max($this->maxLength, 0);
        $minWordSafeLength = max($this->minWordSafeLength, 0);

        // Do not attempt word-safe truncating if the maximum length is smaller than the minimum length to do
        // word-safe truncating.
        $wordSafe = $this->wordSafe && $maxLength >= $minWordSafeLength;

        // Define the suffix of the truncated string.
        $suffix = '';
        if ($this->addEllipsis) {
            $ellipsis = '...';
            if ($this->spaceBeforeEllipsis) {
                $ellipsis = ' ...';
            }
            $suffix = Stringy::create($ellipsis, 'UTF-8');

            // If the ellipsis is longer or equal to the maximum length, simply truncate the ellipsis so it fits in
            // the maximum length and return it.
            if ($suffix->length() >= $maxLength) {
                return (string) $suffix->truncate($maxLength);
            }
        }

        $stringy = Stringy::create($string, 'UTF-8');

        $sentencePattern = '/(.*[.!?])(?:\\s|\\h|$|\\\u00a0).*/su';
        $trunc = (string) $stringy->first($maxLength);
        $hasEndingSymbolInRange = preg_match($sentencePattern, $trunc);

        if ($this->sentenceFriendly && $hasEndingSymbolInRange === 1) {
            $sentenceTruncated = preg_replace($sentencePattern, '$1' . $suffix, $trunc);
            $truncated = Stringy::create($sentenceTruncated, 'UTF-8');
        } elseif ($wordSafe) {
            $truncated = $stringy->safeTruncate($maxLength, $suffix);
        } else {
            $truncated = $stringy->truncate($maxLength, $suffix);
        }

        if ($this->addEllipsis) {
            // Make sure the string does not end in more than 3 dots. The pattern looks for a sequence of
            // 4 or more ("{4,}") dots ("(\\.)") at the end of the string ("$").
            $pattern = '(\\.){4,}$';
            $truncated = $truncated->regexReplace($pattern, $suffix);
        }

        return (string) $truncated;
    }
}
