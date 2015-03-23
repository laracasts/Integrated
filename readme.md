# Integrated

> Please note this is still early in development.

## Usage

### Step 1: Install

Install through Composer.

```
composer require laracasts/integrated --dev
```

### Step 2: Extend

Within a PHPUnit test class, extend either `Laracasts\Integrated\Goutte` for general PHP applications, or `Laracasts\Integrated\Laravel`, if you use Laravel.

```php
<?php // tests/ExampleTest.php

use Laracasts\Integrated\Laravel as IntegrationTest;

class ExampleTest extends IntegrationTest {}
```

### Step 3: Write Some Integration Tests

That should mostly do it!

If you're using the Goutte extension, you'll of course need to boot up a server. By default, this package will assume a base url of "http://localhost:8888". Should need to modify this (likely the case), set a `$baseUrl` on your unit test class (or a parent class), like so:

```
class ExampleTest extends IntegrationTest {
  protected $baseUrl = 'http://localhost:1234';
}
```

On the other hand, if you're using the Laravel-specific extension, no server needs to be running. You can ignore the base url. This also comes with the bonus of *very fast tests*!

Here are some examples to get you started:

```php
<?php

use Laracasts\Integrated\Laravel as IntegrationTest;

class ExampleTest extends IntegrationTest
{

    /** @test */
   public function it_verifies_that_pages_load_properly()
   {
       $this->visit('/');
   }

    /** @test */
    public function it_verifies_the_current_page()
    {
        $this->visit('/some-page')
             ->seePageIs('some-page');
    }

    /** @test */
   public function it_follows_links()
   {
       $this->visit('/page-1')
            ->click('Follow Me')
            ->andSee('You are on Page 2')
            ->onPage('/page-2');
   }

    /** @test */
   public function it_submits_forms()
   {
       $this->visit('page-with-form')
            ->submitForm('Submit', ['title' => 'Foo Title'])
            ->andSee('You entered Foo Title')
            ->onPage('/page-with-form-results');

        // Another way to write it.
        $this->visit('page-with-form')
             ->type('Foo Title', '#title')
             ->press('Submit')
             ->see('You Entered Foo Title');
   }

    /** @test */
   public function it_verifies_information_in_the_database()
   {
       $this->visit('database-test')
            ->type('Testing', 'name')
            ->press('Save to Database')
            ->verifyInDatabase('things', ['name' => 'Testing']);
   }
}
```

### Integration Methods

#### `visit($uri)`

This will perform a `GET` request to the given $uri, while also triggering an assertion to guarantee that a 200 status code was returned.

### FAQ

#### Can I test JavaScript with this method?

No. For client-side interactions, you'll want to use something like Selenium.

