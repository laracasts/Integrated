<?php

namespace Laracasts\Integrated;

interface InteractingEmulator
{
    /**
     * Assert that an alert box is displayed, and contains the given text.
     *
     * @param  string  $text
     * @param  boolean $accept
     * @return
     */
    public function seeInAlert();

    /**
     * Accept an alert.
     *
     * @return self
     */
    public function acceptAlert();
}
