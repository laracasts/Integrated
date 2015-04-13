<?php

namespace Laracasts\Integrated\Extensions;

use PHPUnit_Framework_ExpectationFailedException as PHPUnitException;
use Laracasts\Integrated\AnnotationReader;
use Symfony\Component\DomCrawler\Form;
use Laracasts\Integrated\File;
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
     * The annotation reader instance.
     *
     * @var AnnotationReader
     */
    protected $annotations;

    /**
     * Prepare the test for PHPUnit.
     *
     * @return  void
     */
    public function setUp()
    {
        parent::setUp();

        $this->callMethods(
            $this->getAnnotations()->having('setUp')
        );
    }

    /**
     * Make a GET request to the given uri.
     *
     * @param  string $uri
     * @return static
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
            $url = rtrim(sprintf("%s/%s", $this->baseUrl(), $url), '/');
        }

        return $url;
    }

    /**
     * Search the DOM for the given text.
     *
     * @param  string $text
     * @return static
     */
    public function see($text)
    {
        try {
            $message = sprintf(
                "Could not find '%s' on the page, '%s'.", $text, $this->currentPage
            );

            $this->assertRegExp("/{$text}/i", $this->response(), $message);
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
     * @return static
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
     * @return static
     */
    public function onPage($page)
    {
        return $this->seePageIs($page);
    }

    /**
     * Click a link with the given body.
     *
     * @param  string $name
     * @return static
     */
    public function click($name)
    {
        $link = $this->crawler->selectLink($name);

        // If we couldn't find the link by its body, then
        // we'll do a second pass and see if the user
        // provided a name or id attribute instead.

        if (! count($link)) {
            $link = $this->filterByNameOrId($name, 'a');

            if (! count($link)) {
                $message = "Couldn't see a link with a body, name, or id attribute of, '{$name}'.";

                throw new InvalidArgumentException($message);
            }
        }

        $this->visit($link->link()->getUri());

        return $this;
    }

    /**
     * Alias that points to the click method.
     *
     * @param  string $text
     * @return static
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
     * @return static
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
     * @return static
     */
    public function fill($text, $element)
    {
        return $this->storeInput($element, $text);
    }

    /**
     * Check a checkbox.
     *
     * @param  string $element
     * @return static
     */
    public function check($element)
    {
        return $this->storeInput($element, true);
    }

    /**
     * Alias that defers to check method.
     *
     * @param  string $element
     * @return static
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
     * @return static
     */
    public function select($element, $option)
    {
        return $this->storeInput($element, $option);
    }

    /**
     * Attach a file to a form.
     *
     * @param  string $element
     * @param  string $absolutePath
     * @return static
     */
    public function attachFile($element, $absolutePath)
    {
        return $this->storeInput($element, $absolutePath);
    }

    /**
     * Store a form input.
     *
     * @param  string $name
     * @param  string $value
     * @return static
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
     * @return static
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

        die(var_dump($this->response()));
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
     * @param  string $message
     * @throws PHPUnitException
     * @return void
     */
    protected function assertPageLoaded($uri, $message = null)
    {
        $status = $this->statusCode();

        try {
            $this->assertEquals(200, $status);
        } catch (PHPUnitException $e) {
            $message = $message ?: "A GET request to '{$uri}' failed. Got a {$status} code instead.";

            $this->logLatestContent();

            if (method_exists($this, 'handleInternalError')) {
                $this->handleInternalError($message);
            }

            throw new PHPUnitException($message);
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
        $crawler = $this->filterByNameOrId($filter);

        if (! count($crawler)) {
            $message = "Nothing matched the '{$filter}' CSS query provided for {$this->currentPage}.";

            throw new InvalidArgumentException($message);
        }
    }

    /**
     * Ensure that the given file exists.
     *
     * @param  string $path
     * @return static
     */
    public function seeFile($path)
    {
        $this->assertFileExists($path);

        return $this;
    }

    /**
     * Ensure that a database table contains a row with the given data.
     *
     * @param  string $table
     * @param  array  $data
     * @return static
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
     * @return static
     */
    public function verifyInDatabase($table, array $data)
    {
        return $this->seeInDatabase($table, $data);
    }

    /**
     * Clear out the inputs array.
     *
     * @return static
     */
    protected function clearInputs()
    {
        $this->inputs = [];

        return $this;
    }

    /**
     * Filter according to an element's name or id attribute.
     *
     * @param  string $name
     * @param  string $element
     * @return Crawler
     */
    protected function filterByNameOrId($name, $element = '*')
    {
        $name = str_replace('#', '', $name);

        return $this->crawler->filter("{$element}#{$name}, {$element}[name={$name}]");
    }

    /**
     * Log the response content to an output file for the user.
     *
     * @return void
     */
    protected function logLatestContent()
    {
        $this->files()->put("tests/logs/output.txt", $this->response());
    }

    /**
     * Fetch the user-provided package configuration.
     *
     * @return object
     */
    protected function getPackageConfig()
    {
        if ( ! file_exists('integrated.json')) {
            return [];
        }

        if (! $this->packageConfig) {
            $this->packageConfig = json_decode(file_get_contents('integrated.json'), true);
        }

        return $this->packageConfig;
    }

    /**
     * Get the annotation reader instance.
     *
     * @return AnnotationReader
     */
    public function getAnnotations()
    {
        if (! $this->annotations) {
            $this->annotations = new AnnotationReader($this);
        }

        return $this->annotations;
    }

    /**
     * Get a filesystem class.
     *
     * @return File
     */
    public function files()
    {
        return new File;
    }

    /**
     * Trigger all provided methods on the current object.
     *
     * @param  array $methods
     * @return void
     */
    protected function callMethods(array $methods)
    {
        foreach ($methods as $method) {
            call_user_func([$this, $method]);
        }
    }

    /**
     * Clean up after for PHPUnit.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->callMethods(
            $this->getAnnotations()->having('tearDown')
        );
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
