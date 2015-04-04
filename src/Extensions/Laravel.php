<?php

namespace Laracasts\Integrated\Extensions;

use PHPUnit_Framework_ExpectationFailedException as PHPUnitException;
use Illuminate\Foundation\Testing\TestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Laracasts\Integrated\Emulator;

abstract class Laravel extends TestCase implements Emulator
{
    use IntegrationTrait;

    /**
     * Enable method spoofing for HTML forms with a "_method" attribute.
     *
     * @setUp
     */
    protected function enableMethodSpoofing()
    {
        $this->app['request']->enableHttpMethodParameterOverride();
    }

    /**
     * Get the base url for all requests.
     *
     * @return string
     */
    public function baseUrl()
    {
        return "http://localhost";
    }

    /**
     * Submit a form on the page.
     *
     * @param  string $buttonText
     * @param  array  $formData
     * @return static
     */
    public function submitForm($buttonText, $formData = [])
    {
        $this->makeRequestUsingForm(
            $this->fillForm($buttonText, $formData)
        );

        return $this;
    }

    /**
     * Call a URI in the application.
     *
     * @param  string $requestType
     * @param  string $uri
     * @param  array  $parameters
     * @return static
     */
    protected function makeRequest($requestType, $uri, $parameters = [])
    {
        $this->call($requestType, $uri, $parameters);

        $this->clearInputs()->followRedirects()->assertPageLoaded($uri);

        // We'll set the current page again here, since it's possible
        // that the user was redirected.

        $this->currentPage = $this->app['request']->url();

        $this->crawler = new Crawler($this->content(), $this->currentPage);

        return $this;
    }

    /**
     * Follow 302 redirections.
     *
     * @return void
     */
    protected function followRedirects()
    {
        while ($this->response->isRedirect()) {
            $this->makeRequest('GET', $this->response->getTargetUrl());
        }

        return $this;
    }

    /**
     * Make a request to a URL using form parameters.
     *
     * @param  Form $form
     * @return static
     */
    protected function makeRequestUsingForm(Form $form)
    {
        return $this->makeRequest(
            $form->getMethod(), $form->getUri(), $form->getPhpValues()
        );
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
        return $this->app['db']->table($table)->where($data)->count();
    }

    /**
     * Get the content from the reponse.
     *
     * @return string
     */
    protected function content()
    {
        return $this->response->getContent();
    }

    /**
     * Get the status code from the last request.
     *
     * @return string
     */
    protected function statusCode()
    {
        return $this->response->getStatusCode();
    }

    /**
     * Provide additional messaging for 500 errors.
     *
     * @param  string|null $message
     * @throws PHPUnitException
     * @return void
     */
    protected function handleInternalError($message = null)
    {
        $crawler = new Crawler($this->content(), $this->currentPage);

        // A little weird, but we need to parse the output HTML to
        // figure out the specifics of where the error occurred.
        // There might be an easier way to figure this out.

        $crawler = $crawler->filter('.exception_title');
        $exception = $crawler->filter('abbr')->html();
        $location = $crawler->filter('a')->extract('title')[0];

        $message .= "\n\n{$exception} on {$location}";

        throw new PHPUnitException($message);
    }

}
