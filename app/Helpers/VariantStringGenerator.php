<?php

namespace App\Helpers;

class VariantStringGenerator
{
    /**
     * Generate an ordered, searchable combination string from variant parts.
     * Example input: ["Baju", "Red", "XL"]
     * Example output: "BRXadejlu" (First letters capitalized, rest sorted lowercase)
     *
     * @param  array<string>  $parts
     */
    public static function generate(array $parts): string
    {
        if (empty($parts)) {
            return '';
        }

        $prefix = '';
        $remainingChars = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }

            // Take the first character, capitalize it for the prefix
            $firstChar = mb_substr($part, 0, 1);
            $prefix .= mb_strtoupper($firstChar);

            // Take the rest of the string, lowercase it, and split into chars
            $rest = mb_substr($part, 1);
            $rest = mb_strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rest));

            if (! empty($rest)) {
                foreach (mb_str_split($rest) as $char) {
                    $remainingChars[] = $char;
                }
            }
        }

        // Sort the remaining characters alphabetically
        sort($remainingChars);

        return $prefix.implode('', $remainingChars);
    }
}
