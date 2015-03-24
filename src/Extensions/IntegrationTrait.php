<?php

namespace Laracasts\Integrated\Extensions;

use PHPUnit_Framework_ExpectationFailedException as PHPUnitException;
use Symfony\Component\DomCrawler\Form;
use Laracasts\Integrated\Str;
use InvalidArgumentException;
use BadMethodCallException;

trait IntegrationTrait
{
    /**
     * The DomCrawler instance.
     *
     * @var DomCrawler
     */
    protected $crawler;

    /**
     * The current page URL.
     *
     * @var string
     */
    protected $currentPage;

    /**
     * User-filled form inputs.
     *
     * @var array
     */
    protected $inputs = [];

    /**
     * The user-provided package configuration.
     *
     * @var array
     */
    protected $packageConfig;

    /**
     * Make a GET request to the given uri.
     *
     * @param  string $url
     * @return self
     */
    public function visit($uri)
    {
        $this->currentPage = $this->prepareUrl($uri);

        $this->makeRequest('GET', $this->currentPage);

        return $this;
    }

    /**
     * Prepare the relative URL, given by the user.
     *
     * @param  string $url
     * @return string
     */
    protected function prepareUrl($url)
    {
        if (Str::startsWith($url, '/')) {
            $url = substr($url, 1);
        }

        if (! Str::startsWith($url, 'http')) {
            $url = sprintf("%s/%s", $this->baseUrl(), $url);
        }

        return $url;
    }

    /**
     * Search the DOM for the given text.
     *
     * @param  string $text
     * @return self
     */
    public function see($text)
    {
        try {
            $message = sprintf(
                "Could not find '%s' on the page, '%s'.", $text, $this->currentPage
            );

            $this->assertRegExp("/{$text}/i", $this->content(), $message);
        } catch (PHPUnitException $e) {
            $this->logLatestContent();

            throw $e;
        }

        return $this;
    }

    /**
     * Assert that the current page matches a uri.
     *
     * @param  string $uri
     * @return self
     */
    public function seePageIs($uri)
    {
        $this->assertPageLoaded($uri = $this->prepareUrl($uri));

        $message = "Expected to be on the page, {$uri}, but wasn't.";

        $this->assertEquals($uri, $this->currentPage, $message);

        return $this;
    }

    /**
     * Alias that defers to seePageIs.
     *
     * @param  string $page
     * @return self
     */
    public function onPage($page)
    {
        return $this->seePageIs($page);
    }

    /**
     * Click a link with the given body.
     *
     * @param  string $text
     * @return self
     */
    public function click($text)
    {
        $link = $this->crawler->selectLink($text);

        if (! count($link)) {
            $message = "Couldn't find a link with the given text, '{$text}'.";

            throw new InvalidArgumentException($message);
        }

        $this->visit($link->link()->getUri());

        return $this;
    }

    /**
     * Alias that points to the click method.
     *
     * @param  string $text
     * @return self
     */
    public function follow($text)
    {
        return $this->click($text);
    }

    /**
     * Fill in an input with the given text.
     *
     * @param  string $text
     * @param  string $element
     * @return self
     */
    public function type($text, $element)
    {
        return $this->fill($text, $element);
    }

    /**
     * Alias that defers to type method.
     *
     * @param  string $text
     * @param  string $element
     * @return self
     */
    public function fill($text, $element)
    {
        return $this->storeInput($element, $text);
    }

    /**
     * Check a checkbox.
     *
     * @param  string $element
     * @return self
     */
    public function check($element)
    {
        return $this->storeInput($element, true);
    }

    /**
     * Alias that defers to check method.
     *
     * @param  string $element
     * @return self
     */
    public function tick($element)
    {
        return $this->check($element);
    }

    /**
     * Select an option from a dropdown.
     *
     * @param  string $element
     * @param  string $option
     * @return self
     */
    public function select($element, $option)
    {
        return $this->storeInput($element, $option);
    }

    /**
     * Store a form input.
     *
     * @param  string $name
     * @param  string $value
     * @return self
     */
    protected function storeInput($name, $value)
    {
        $this->assertFilterProducedResults($name);

        $name = str_replace('#', '', $name);

        $this->inputs[$name] = $value;

        return $this;
    }

    /**
     * Press the form submit button with the given text.
     *
     * @param  string $buttonText
     * @return self
     */
    public function press($buttonText)
    {
        return $this->submitForm($buttonText, $this->inputs);
    }

    /**
     * Dump the response content from the last request to the console.
     *
     * @return void
     */
    public function dump()
    {
        $this->logLatestContent();

        die(var_dump($this->content()));
    }

    /**
     * Fill out the form, using the given data.
     *
     * @param  string $buttonText
     * @param  array  $formData
     * @return Form
     */
    protected function fillForm($buttonText, $formData = [])
    {
        if (! is_string($buttonText)) {
            $formData = $buttonText;
            $buttonText = null;
        }

        return $this->getForm($buttonText)->setValues($formData);
    }

    /**
     * Get the form from the DOM.
     *
     * @param  string|null $button
     * @throws InvalidArgumentException
     * @return Form
     */
    protected function getForm($button = null)
    {
        // If the first argument isn't a string, that means
        // the user wants us to auto-find the form.

        try {
            if ($button) {
                return $this->crawler->selectButton($button)->form();
            }

            return $this->crawler->filter('form')->form();
        } catch (InvalidArgumentException $e) {
            // We'll catch the exception, in order to provide a
            // more readable failure message for the user.

            throw new InvalidArgumentException(
                "Couldn't find a form that contains a button with text '{$button}'."
            );
        }
    }

    /**
     * Assert that a 200 status code was returned from the last call.
     *
     * @param  string $uri
     * @throws PHPUnitException
     * @return void
     */
    protected function assertPageLoaded($uri)
    {
        $status = $this->statusCode();

        try {
            $message = "A GET request to '{$uri}' failed. Got a {$status} code instead.";

            $this->assertEquals(200, $status, $message);
        } catch (PHPUnitException $e) {
            $this->logLatestContent();

            throw $e;
        }
    }

    /**
     * Assert that the filtered Crawler contains nodes.
     *
     * @param  string $filter
     * @throws InvalidArgumentException
     * @return void
     */
    protected function assertFilterProducedResults($filter)
    {
        // We'll first assume that an element or id was provided.

        $crawler = $this->crawler->filter($filter);

        // If we couldn't find anything, we'll do one more check to
        // see if a name attribute for the element was provided.

        if (! count($crawler)) {
            $filter = str_replace('#', '', $filter);
            $crawler = $this->crawler->filter("* [name={$filter}]");
        }

        // Lastly, if we still have nothing, we'll alert the user.

        if (! count($crawler)) {
            $message = "Nothing matched the '{$filter}' CSS query provided for {$this->currentPage}.";

            throw new InvalidArgumentException($message);
        }
    }

    /**
     * Ensure that a database table contains a row with the given data.
     *
     * @param  string $table
     * @param  array  $data
     * @return self
     */
    public function seeInDatabase($table, array $data)
    {
        $count = $this->seeRowsWereReturned($table, $data);

        $message = sprintf(
            "Didn't see row in the '%s' table that matched the attributes '%s'.",
            $table, json_encode($data)
        );

        $this->assertGreaterThan(0, $count, $message);

        return $this;
    }

    /**
     * Alias that defers to seeInDatabase.
     *
     * @param  string $table
     * @param  array  $data
     * @return self
     */
    public function verifyInDatabase($table, array $data)
    {
        return $this->seeInDatabase($table, $data);
    }

    /**
     * Clear out the inputs array.
     *
     * @return self
     */
    protected function clearInputs()
    {
        $this->inputs = [];

        return $this;
    }

    /**
     * Log the response content to an output file for the user.
     *
     * @return void
     */
    protected function logLatestContent()
    {
        $outputDir = 'tests/logs';

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);

            file_put_contents("{$outputDir}/output.txt", $this->content());
        }
    }

    /**
     * Fetch the user-provided package configuration.
     *
     * @return object
     */
    protected function getPackageConfig()
    {
        if (! $this->packageConfig) {
            $this->packageConfig = json_decode(file_get_contents('integrated.json'), true);
        }

        return $this->packageConfig;
    }

    /**
     * Handle dynamic calls.
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (Str::startsWith($method, 'and')) {
            $method = strtolower(substr($method, 3));

            if (method_exists($this, $method)) {
                return call_user_func_array([$this, $method], $args);
            }
        }

        throw new BadMethodCallException("The '{$method}' method does not exist.");
    }
}
