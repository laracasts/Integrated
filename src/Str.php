<?php

namespace Laracasts\Integrated;

class Str
{
    /**
     * Determine if a given string starts with a given substring.
     * Swiped from Taylor Otwell :,
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && strpos($haystack, $needle) === 0) {
                return true;
            }
        }

        return false;
    }
}
