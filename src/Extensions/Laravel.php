<?php

namespace Laracasts\Integrated\Extensions;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Laracasts\Integrated\Emulator;
use TestCase;

abstract class Laravel extends TestCase implements Emulator
{
    use IntegrationTrait;

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
     * @return self
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
     * @return self
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
     * @return self
     */
    protected function makeRequestUsingForm(Form $form)
    {
        return $this->makeRequest(
            $form->getMethod(), $form->getUri(), $form->getValues()
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
}
