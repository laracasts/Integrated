<?php

namespace Laracasts\Integrated\Extensions;

trait ApiRequests
{

    /**
     * Make a GET request to an API endpoint.
     *
     * @param  string $uri
     * @return static
     */
    protected function get($uri)
    {
        $this->call('GET', $uri);

        return $this;
    }

    /**
     * Alias for "get" method.
     *
     * @param  string $uri
     * @return static
     */
    protected function hit($uri)
    {
        return $this->get($uri);
    }

    /**
     * Make a POST request to an API endpoint.
     *
     * @param  string $uri
     * @param  array  $data
     * @return static
     */
    protected function post($uri, array $data)
    {
        $this->call('POST', $uri, $data);

        return $this;
    }

    /**
     * Make a PUT request to an API endpoint.
     *
     * @param  string $uri
     * @param  array  $data
     * @return static
     */
    protected function put($uri, array $data)
    {
        $this->call('PUT', $uri, $data);

        return $this;
    }

    /**
     * Make a PATCH request to an API endpoint.
     *
     * @param  string $uri
     * @param  array  $data
     * @return static
     */
    protected function patch($uri, array $data)
    {
        $this->call('PATCH', $uri, $data);

        return $this;
    }

    /**
     * Make a DELETE request to an API endpoint.
     *
     * @param  string $uri
     * @return static
     */
    protected function delete($uri)
    {
        $this->call('DELETE', $uri);

        return $this;
    }

    /**
     * Assert that the last response is JSON.
     *
     * @return static
     */
    protected function seeJson()
    {
        $this->assertJson($this->response());

        return $this;
    }

    /**
     * Alias for "seeJson" method.
     *
     * @return static
     */
    protected function seeIsJson()
    {
        return $this->seeJson();
    }

    /**
     * Assert that the status code equals the given code.
     *
     * @param  integer $code
     * @return static
     */
    protected function seeStatusCode($code)
    {
        $this->assertEquals($code, $this->statusCode());

        return $this;
    }

    /**
     * Alias for "seeStatusCode" method.
     *
     * @param  integer $code
     * @return static
     */
    protected function seeStatusCodeIs($code)
    {
        return $this->seeStatusCode($code);
    }

    /**
     * Assert that an API response equals the provided array
     * or json-encoded array.
     *
     * @param  array|string $expected
     * @return static
     */
    protected function seeJsonEquals($expected)
    {
        if (is_array($expected)) {
            $expected = json_encode($expected);
        }

        $this->assertJsonStringEqualsJsonString($expected, $this->response());

        return $this;
    }

    /**
     * Assert that an API response matches the provided array.
     *
     * @param  array|string $expected
     * @return static
     */
    protected function seeJsonContains($expected)
    {
        $response = $this->response();
        $json = json_decode($response, true);

        $this->assertNotEmpty(
            @array_intersect($json, $expected),
            sprintf("Dang! Expected %s to exist in %s, but no dice. Any ideas?", json_encode($expected), $response)
        );

        return $this;
    }
}
