<?php

namespace Laracasts\Integrated;

interface Emulator
{
    /**
     * Make a GET request to the given page.
     *
     * @param  string $page
     * @return self
     */
    public function visit($page);

    /**
     * Search the DOM for the given text.
     *
     * @param  string $text
     * @return self
     */
    public function see($text);

    /**
     * Convenience method that defers to onPage.
     *
     * @param  string $page
     * @return self
     */
    public function seePageIs($page);

    /**
     * Assert that the current page is...
     *
     * @param  string $page
     * @return self
     */
    public function onPage($page);

    /**
     * Click a link with the given body.
     *
     * @param  string $text
     * @return self
     */
    public function click($text);

    /**
     * Alias that points to the click method.
     *
     * @param  string $text
     * @return self
     */
    public function follow($text);

    /**
     * Submit a form on the page.
     *
     * @param  string $buttonText
     * @param  array|null $formData
     * @return self
     */
    public function submitForm($buttonText, $formData = null);

    /**
     * Press the form submit button with the given text.
     *
     * @param  string $buttonText
     * @return self
     */
    public function press($buttonText);

    /**
     * Fill in an input with the given text.
     *
     * @param  string $text
     * @param  string $element
     * @return self
     */
    public function type($text, $element);

    /**
     * Conveience method that defers to type method.
     *
     * @param  string $text
     * @param  string $element
     * @return self
     */
    public function fill($text, $element);

    /**
     * Check a checkbox.
     *
     * @param  string $element
     * @return self
     */
    public function check($element);

    /**
     * Alias that defers to check method.
     *
     * @param  string $element
     * @return self
     */
    public function tick($element);

    /**
     * Select an option from a dropdown.
     *
     * @param  string $element
     * @param  string $option
     * @return self
     */
    public function select($element, $option);

    /**
     * Ensure that a database table contains a row with the given data.
     *
     * @param  string $table
     * @param  array  $data
     * @return self
     */
    public function seeInDatabase($table, array $data);

    /**
     * Convenience method that defers to seeInDatabase.
     *
     * @param  string $table
     * @param  array $data
     * @return self
     */
    public function verifyInDatabase($table, array $data);

    /**
     * Dump the response content from the last request to the console.
     *
     * @return void
     */
    public function dump();
}
