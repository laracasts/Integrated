<?php

namespace Laracasts\Integrated\Extensions;

use PHPUnit_Framework_ExpectationFailedException as PHPUnitException;
use Laracasts\Integrated\Database\Connection;
use Laracasts\Integrated\Database\Adapter;
use WebDriver\Exception\NoSuchElement;
use Laracasts\Integrated\Emulator;
use InvalidArgumentException;
use WebDriver\Element;
use WebDriver\Session;
use WebDriver\WebDriver;
use Exception;

abstract class Selenium extends \PHPUnit_Framework_TestCase implements Emulator
{
    use IntegrationTrait;

    /**
     * The WebDriver instance.
     *
     * @var WebDriver
     */
    protected $webDriver;

    /**
     * The current session instance.
     *
     * @var Session
     */
    protected $session;

    /**
     * The database adapter instance.
     *
     * @var Adapter
     */
    protected $db;

    /**
     * Get the base url for all requests.
     *
     * @return string
     */
    protected function baseUrl()
    {
        if (isset($this->baseUrl)) {
            return $this->baseUrl;
        }

        $config = $this->getPackageConfig();

        if (isset($config['baseUrl'])) {
            return $config['baseUrl'];
        }

        return 'http://localhost:8888';
    }

    /**
     * Call a URI in the application.
     *
     * @param  string $requestType
     * @param  string $uri
     * @param  array  $parameters
     * @return self
     */
    protected function makeRequest($requestType, $uri, $parameters = [])
    {
        $this->session = $this->newSession()->open($uri);

        return $this;
    }

    /**
     * Click a link with the given body.
     *
     * @param  string $name
     * @return self
     */
    public function click($name)
    {
        try {
            $link = $this->findByBody($name)->click();
        } catch (InvalidArgumentException $e) {
            $link = $this->findByNameOrId($name)->click();
        }

        $this->updateCurrentUrl();

        $this->assertPageLoaded(
            $this->currentPage,
            "Successfully clicked on a link with a body, name, or class of '{$name}', " .
            "but its destination, {$this->currentPage}, did not produce a 200 status code."
        );

        return $this;
    }

    /**
     * Find an element by its text content.
     *
     * @param  string $body
     * @return Element
     */
    protected function findByBody($body)
    {
        try {
            return $this->session->element('link text', $body);
        } catch (NoSuchElement $e) {
            throw new InvalidArgumentException('No element with the given body exists.');
        }
    }

    /**
     * Filter according to an element's name or id attribute.
     *
     * @param  string $name
     * @param  string $element
     * @return Crawler
     */
    protected function findByNameOrId($name, $element = '*')
    {
        $name = str_replace('#', '', $name);

        try {
            return $this->session->element('css selector', "#{$name}, *[name={$name}]");
        } catch (NoSuchElement $e) {
            throw new InvalidArgumentException(
                "Couldn't find an element, '{$element}', with a name or class attribute of '{$name}'."
            );
        }
    }

    /**
     * Find an element by its "value" attribute.
     *
     * @param  string $value
     * @param  string $element
     * @return \Session
     */
    protected function findByValue($value, $element = 'input')
    {
        try {
            return $this->session->element('css selector', "{$element}[value='{$value}']");
        } catch (NoSuchElement $e) {
            throw new InvalidArgumentException(
                "Couldn't find an {$element} with a 'value' attribute of '{$value}'."
            );
        }
    }

    /**
     * Submit a form on the page.
     *
     * @param  string $buttonText
     * @param  array $formData
     * @return self
     */
    public function submitForm($buttonText, $formData = [])
    {
        foreach ($formData as $name => $value) {
            // Weird, but that's what you gotta do. :)
            $value = ['value' => [$value]];

            $element = $this->findByNameOrId($name);
            $tag = $element->name();

            if ($tag == 'input' && $element->attribute('type') == 'checkbox') {
                $element->click();
            } else {
                $element->postValue($value);
            }
        }

        $this->findByValue($buttonText)->submit();

        $this->updateCurrentUrl();

        return $this;
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
        $value = ['value' => [$text]];
        $this->findByNameOrId($element, $text)->postValue($value);

        return $this;
    }

    /**
     * Check a checkbox.
     *
     * @param  string $element
     * @return self
     */
    public function check($element)
    {
        $this->findByNameOrId($element)->click();

        return $this;
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
        $this->findByValue($option, 'option')->click();

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
        return $this->submitForm($buttonText);
    }

    /**
     * Assert that an alert box is displayed, and contains the given text.
     *
     * @param  string  $text
     * @param  boolean $accept
     * @return
     */
    public function seeInAlert($text, $accept = true)
    {
        try {
            $alert = $this->session->alert_text();
        } catch (\WebDriver\Exception\NoAlertOpenError $e) {
            throw new PHPUnitException(
                "Could not see '{$text}' because no alert box was shown."
            );
        }

        $this->assertContains($text, $alert);

        if ($accept) {
            $this->acceptAlert();
        }

        return $this;
    }

    /**
     * Accept an alert.
     *
     * @return self
     */
    public function acceptAlert()
    {
        try {
            $this->session->accept_alert();
        } catch (\WebDriver\Exception\NoAlertOpenError $e) {
            throw new PHPUnitException(
                "Well, tried to accept the alert, but there wasn't one. Dangit."
            );
        }

        return $this;
    }

    /**
     * Take a snapshot of the current page.
     *
     * @param  string|null $destination
     * @return self
     */
    public function snap($destination = null)
    {
        $destination = $destination ?: './tests/logs/screenshot.png';
        $dir = dirname($destination);

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents(
            $destination,
            base64_decode($this->session->screenshot())
        );

        return $this;
    }

    /**
     * Get the number of rows that match the given condition.
     *
     * @param  string $table
     * @param  array $data
     * @return integer
     */
    protected function seeRowsWereReturned($table, $data)
    {
        return $this->getDbAdapter()->table($table)->whereExists($data);
    }

    /**
     * Get the adapter to the database.
     *
     * @return Adapter
     */
    protected function getDbAdapter()
    {
        if (! $this->db) {
            $connection = new Connection($this->getPackageConfig()['pdo']);

            $this->db = new Adapter($connection);
        }

        return $this->db;
    }

    /**
     * Update the current page url.
     *
     * @return self
     */
    protected function updateCurrentUrl()
    {
        $this->currentPage = $this->session->url();

        return $this;
    }

    /**
     * Get the content from the last response.
     *
     * @return string
     */
    protected function content()
    {
        return $this->session->source();
    }

    /**
     * Get the status code from the last response.
     *
     * @return integer
     */
    protected function statusCode()
    {
        $response = $this->content();

        // Todo: Temporary. What is the correct way to get the status code?

        if (stristr($response, 'Sorry, the page you are looking for could not be found.')) {
            return 500;
        }

        return 200;
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
        $element = $this->findByNameOrId($filter);
    }

    /**
     * Close the browser, once the test completes.
     *
     * @tearDown
     * @return void
     */
    public function closeBrowser()
    {
        if ($this->session) {
            $this->session->close();
        }
    }

    /**
     * Halt the proces for any number of seconds.
     *
     * @param  integer $seconds
     * @return self
     */
    public function wait($seconds = 4)
    {
        // We'll provide a little protection, in case the
        // user thinks they're providing milliseconds.
        if ($seconds >= 1000) {
            $seconds = 4;
        }

        sleep($seconds);

        return $this;
    }

    /**
     * Create a new WebDriver session.
     *
     * @param  string $browser
     * @return Session
     */
    protected function newSession()
    {
        $host = 'http://localhost:4444/wd/hub';

        $this->webDriver = new WebDriver($host);
        $capabilities = [];

        return $this->session = $this->webDriver->session($this->getBrowserName(), $capabilities);
    }

    /**
     * Retrieve the user's desired browser for the tests.
     *
     * @return string
     */
    protected function getBrowserName()
    {
        $config = $this->getPackageConfig();

        if (isset($config['selenium'])) {
            return $config['selenium']['browser'];
        }

        return 'firefox';
    }
}
