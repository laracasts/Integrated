<?php

namespace Laracasts\Integrated\Extensions;

use Laracasts\Integrated\Database\Connection;
use Laracasts\Integrated\Database\Adapter;
use Laracasts\Integrated\Emulator;
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
    public function baseUrl()
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
     * Submit a form on the page.
     *
     * @param  string $buttonText
     * @param  array|null $formData
     * @return static
     */
    public function submitForm($buttonText, $formData = null)
    {
        $this->client()->submit(
            $this->fillForm($buttonText, $formData)
        );

        $this->currentPage = $this->client()->getHistory()->current()->getUri();

        $this->clearInputs()->assertPageLoaded($this->currentPage);

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
     * Get the content from the last response.
     *
     * @return string
     */
    protected function response()
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
