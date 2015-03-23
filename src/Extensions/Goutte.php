<?php

namespace Laracasts\Integrated\Extension;

use Symfony\Component\DomCrawler\Form;
use Goutte\Client;

abstract class Goutte extends \PHPUnit_Framework_TestCase implements Emulator
{
    use IntegrationTrait;

    /**
     * The Goutte client instance.
     *
     * @var Client
     */
    protected $client;

    /**
     * Get the base url for all requests.
     *
     * @return string
     */
    public function baseUrl()
    {
        if (isset($this->baseUrl)) {
            return $this->baseUrl;
        }

        return 'http://localhost:8888';
    }

    /**
     * Submit a form on the page.
     *
     * @param  string $buttonText
     * @param  array|null $formData
     * @return self
     */
    public function submitForm($buttonText, $formData = null)
    {
        $this->client()->submit(
            $this->fillForm($buttonText, $formData)
        );

        $this->currentPage = $this->client()->getHistory()->current()->getUri();

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
    protected function makeRequest($requestType, $uri)
    {
        $this->crawler = $this->client()->request('GET', $uri);

        $this->clearInputs()->assertPageLoaded($uri);

        return $this;
    }

    /**
     * Get a Goutte client instance.
     *
     * @return Client
     */
    protected function client()
    {
        if (! $this->client) {
            $this->client = new Client;
        }

        return $this->client;
    }

    /**
     * Get the content from the last response.
     *
     * @return string
     */
    protected function content()
    {
        return (string) $this->client->getResponse();
    }

    /**
     * Get the status code from the last response.
     *
     * @return integer
     */
    protected function statusCode()
    {
        return $this->client->getResponse()->getStatus();
    }
}
