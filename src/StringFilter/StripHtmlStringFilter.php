<?php

namespace CultuurNet\UDB3\StringFilter;

class StripHtmlStringFilter implements StringFilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter($string)
    {
        // Add one newline after each break tag.
        $string = $this->setNewlinesAfterClosingTags($string, 'br', 1, true);

        // Add two newlines after each closing paragraph tag.
        $string = $this->setNewlinesAfterClosingTags($string, 'p', 2);

        // Decode all HTML entities, like &amp;, so they are human-readable.
        $string = html_entity_decode($string);

        // Strip all HTML tags.
        $string = strip_tags($string);

        // Remove any excessive consecutive newlines.
        $string = $this->limitConsecutiveNewlines($string, 2);

        // Trim any whitespace or newlines from the start and/or end of the string.
        $string = trim($string);

        return $string;
    }

    /**
     * Sets a specific amount of newlines after each occurrence of a specific closing HTML tag.
     *
     * @param string $string
     *   String to set newlines in.
     * @param string $tag
     *   Label name. For example "br" to set a newline after each "<br />" or "<br>" (if self-closing flag is set), or
     *   "p" to set a newline after each "</p>" (if not self-closing).
     * @param int $newlineCount
     *   Amount of newlines to set after the closing tag. If any newlines are set already, they will be removed.
     * @param bool $selfClosing
     *   Indicates whether the tag is self-closing (<br />) or not (<p></p>).
     *
     * @return string
     *   Processed string.
     */
    protected function setNewlinesAfterClosingTags($string, $tag, $newlineCount = 1, $selfClosing = false)
    {
        // Start of the pattern.
        $pattern = '/';

        if ($selfClosing) {
            // Find the self-closing tag, including its attributes and optionally a closing slash.
            // .*? means: Get any characters, 0 or more, but non-greedy so stop when the first / or > is encountered.
            $pattern .= '(<' . $tag . '.*?[\\/]?>)';
        } else {
            // Find the closing tag.
            $pattern .= '(<\\/' . $tag . '>)';
        }

        // Capture any newlines after the tag as well.
        $pattern .= '([\\n]*)';

        // End of the pattern. Use i to make it case-insensitive, as HTML tags can be both uppercase and lowercase.
        $pattern .= '/i';

        // Append all pattern matches with a newline character (or more if specified).
        $newlines = '';
        for ($i = 0; $i < $newlineCount; $i++) {
            $newlines .= PHP_EOL;
        }

        // Loop over all matching tags from the string.
        return preg_replace_callback($pattern, function ($match) use ($newlines) {
            // Return the tag appended by the specified amount of newlines. Note that $match[0] is the full captured
            // match, so it also includes the newlines after the tag. $match[1] is just the tag itself, and $match[2]
            // are the newlines following it (if any).
            return $match[1] . $newlines;
        }, $string);
    }

    /**
     * Restricts the number of consecutive newlines in a specific string.
     *
     * @param string $string
     *   String to limit consecutive newlines in.
     * @param int $limit
     *   Limit of consecutive newlines. (Defaults to 2.)
     *
     * @return string
     *   Processed string.
     */
    protected function limitConsecutiveNewlines($string, $limit = 2)
    {
        // Pattern that finds any consecutive newlines that exceed the allowed limit.
        $exceeded = $limit + 1;
        $pattern = '/((\\n){' . $exceeded . ',})/';

        // Create a string with the maximum number of allowed newlines.
        $newlines = '';
        for ($i = 0; $i < $limit; $i++) {
            $newlines .= PHP_EOL;
        }

        // Find each match and replace it with the maximum number of newlines.
        return preg_replace($pattern, $newlines, $string);
    }
}
