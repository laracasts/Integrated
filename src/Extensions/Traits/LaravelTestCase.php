<?php

namespace Laracasts\Integrated\Extensions\Traits;

use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Crawler;
use Laracasts\Integrated\Extensions\IntegrationTrait;
use Laracasts\Integrated\Extensions\Traits\ApiRequests;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Laracasts\Integrated\Extensions\Traits\WorksWithDatabase;
use PHPUnit_Framework_ExpectationFailedException as PHPUnitException;

trait LaravelTestCase
{
    use IntegrationTrait, ApiRequests, WorksWithDatabase;

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
        if (isset($this->baseUrl)) {
            return $this->baseUrl;
        }

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
     * @param  array  $cookies
     * @param  array  $files
     * @return static
     */
    protected function makeRequest($requestType, $uri, $parameters = [], $cookies = [], $files = [])
    {
        $this->call($requestType, $uri, $parameters, $cookies, $files);

        $this->clearInputs()->followRedirects()->assertPageLoaded($uri);

        // We'll set the current page again here, since it's possible
        // that the user was redirected.

        $this->currentPage = $this->app['request']->fullUrl();

        $this->crawler = new Crawler($this->response(), $this->currentPage());

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
        $files = $this->convertFormFiles($form);

        return $this->makeRequest(
            $form->getMethod(), $form->getUri(), $form->getPhpValues(), [], $files
        );
    }


    /**
     * Get the content from the reponse.
     *
     * @return string
     */
    protected function response()
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
        $crawler = new Crawler($this->response(), $this->currentPage());

        // A little weird, but we need to parse the output HTML to
        // figure out the specifics of where the error occurred.
        // There might be an easier way to figure this out.

        $crawler = $crawler->filter('.exception_title');
        $exception = $crawler->filter('abbr')->html();
        $location = $crawler->filter('a')->extract('title')[0];

        $message .= "\n\n{$exception} on {$location}";

        throw new PHPUnitException($message);
    }

    /**
     * Converts form files to UploadedFile instances.
     *
     * @param  Form $form
     * @return array
     */
    protected function convertFormFiles(Form $form)
    {
        $files = $form->getFiles();
        $names = array_keys($files);

        $files = array_map(function ($file, $name) {
            if (isset($this->files[$name])) {
                $absolutePath = $this->files[$name];

                $file = new UploadedFile(
                    $file['tmp_name'],
                    basename($absolutePath),
                    $file['type'],
                    $file['size'],
                    $file['error'],
                    true
                );
            }

            return $file;
        }, $files, $names);

        return array_combine($names, $files);
    }
}
