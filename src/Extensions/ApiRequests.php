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
        $response = $this->response();

        $this->assertJson($response, "Failed asserting that the following response was JSON: {$response}");

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
     * @param  array $expected
     * @return static
     */
    protected function seeJsonContains($expected)
    {
        $response = $this->response();
        $json = json_decode($response, true);

        // If we have a collection of results, we'll sift through each array
        // in the collection, and check to see if there's a match.
        if ( ! isset($json[0])) $json = [$json];

        $containsFragment = array_reduce($json, function($carry, $array) use ($expected) {
            if ($carry) return $carry;

            return $this->jsonHasFragment($expected, $array);
        });

        $this->assertTrue($containsFragment, sprintf(
            "Dang! Expected %s to exist in %s, but nope. Ideas?",
            json_encode($expected), $response
        ));

        return $this;
    }

    /**
     * Determine if the given fragment is contained with a decoded set of JSON.
     *
     * @param  array $fragment
     * @param  array $json
     * @return boolean
     */
    protected function jsonHasFragment(array $fragment, $json)
    {
        return @array_intersect($json, $fragment) == $fragment;
    }
}
