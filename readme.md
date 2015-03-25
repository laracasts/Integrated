# Integrated

Simple, intuitive integration testing with PHPUnit. For the projects where you're less interested in fancy tools and discussions with the business, and more concerned with just ensuring that the dang thing works. :) 

- [Installation](#step-1-install)
- [Extend](#step-2-extend)
- [Examples](#step-3-write-some-integration-tests)
- [API](#api)
- [Extras](#extras)
- [FAQ](#faq)


## Usage

### Step 1: Install

Install through Composer.

```
composer require laracasts/integrated --dev
```

### Step 2: Extend

Currently, you have the option of using a Goutte extension, for general PHP applications, or a Laravel-specific extension. For the former, simply create a PHPUnit test class, and extend `Laracasts\Integrated\Extensions\Goutte`.

```php
<?php // tests/ExampleTest.php

use Laracasts\Integrated\Extensions\Goutte as IntegrationTest;

class ExampleTest extends IntegrationTest {}
```

On the other hand, if you're working with Laravel, have your main `tests/TestCase.php` class extend `Laracasts\Integrated\Extensions\Laravel`, like so:

```php
<?php // tests/ExampleTest.php

class ExampleTest extends TestCase {}
```

```php
<?php // tests/TestCase.php

use Laracasts\Integrated\Extensions\Laravel as IntegrationTest;

class TestCase extends IntegrationTest {}
```

### Step 3: Write Some Integration Tests

That should mostly do it!

If you're using the Goutte extension, you'll of course need to boot up a server. By default, this package will assume a base url of "http://localhost:8888". Should need to modify this (likely the case), either set a `$baseUrl` on your unit test class (or a parent class)...

```php
class ExampleTest extends IntegrationTest {
  protected $baseUrl = 'http://localhost:1234';
}
```

...or set a `baseUrl` property in a `integrated.json` file in your project root.

```js
{
    "baseUrl": "http://localhost:1234"
}
```

On the other hand, if you're using the Laravel-specific extension, no server needs to be running. You can ignore the base url. This also comes with the bonus of *very fast tests*!

Here are some examples to get you started:

```php
<?php

use Laracasts\Integrated\Extensions\Laravel as IntegrationTest;

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

## API

If you'd like to dig into the API examples from above a bit more, here is what each method call accomplishes.

#### `visit($uri)`

This will perform a `GET` request to the given `$uri`, while also triggering an assertion to guarantee that a 200 status code was returned.

```php
$this->visit('/page');
```

#### `see($text)`

To verify that the current page contains the given text, you'll want to use the `see` method.

```php
$this->visit('/page')
     ->see('Hello World');
```

> Tip: The word "and" may be prepended to any method call to help with readability. As such, if you wish, you may write: `$this->visit('/page')->andSee('Hello World');`.

#### `click($linkText)` or `follow($linkText)`

To simulate the behavior of clicking a link on the page, the `click` method is your friend.

```php
$this->visit('/page')
     ->click('Follow Me');
```

While it's easiest if you pass the text content of the desired anchor tag to the `click` method (like "Sign Up"), you may also use the anchor tag's `name` or `id` attributes if you wish.

Behind the scenes, this package will determine that destination of the link (the "href"), and make a new "GET" request, accordingly. Alternatively, you may use the `follow()` method. Same thing.


#### `seePageIs($uri)` and `onPage($uri)`

In many situations, it can prove useful to make an assertion against the current url.

```php
$this->visit('/page')
     ->click('Follow Me')
     ->seePageIs('/next-page');
```

Alternatively, if it offers better readability, you may use the `onPage` method instead. Both are equivalent in functionality. This is especially true when it follows a `see` assertion call.

```php
$this->visit('/page')
     ->click('Follow Me')
     ->andSee('You are on the next page')
     ->onPage('/next-page');
```

#### `type($text, $selector)` or `fill($text, $selector)`

If you need to type something into an input field, one option is to use the `type` method, like so:

```php
$this->visit('search')
     ->type('Total Recall', '#q');
```

Simply provide the value for the input, and a CSS selector for us to hunt down the input that you're looking for. You may pass an id, element name, or an input with the given "name" attribute. The `fill` method is an alias that does the same thing.

#### `tick($name)` or `check($name)`

To "tick" a checkbox, call the `tick` method, and pass either the id or the name of the input.

```php
$this->visit('newsletter')
     ->tick('opt-in')
     ->press('Save');
```

The `check` method is an alias for `tick`. Use either.

#### `select($selectName, $optionValue)`

This method allows you to select an option from a dropdown. You only need to provide the name of the `select` element, and the `value` attribute from the desired `option` tag.

```php
$this->visit('signup')
     ->select('plan', 'monthly')
     ->press('Sign Up');
```

The following HTML would satisfy the example above:

```html
<form method="POST" action="...">
  <select name="plan">
    <option value="monthly">Monthly</option>
    <option value="yearly">Yearly</option>
  </select>

  <input type="submit" value="Sign Up">
</form>
```

#### `press($submitText)`

Not to be confused with `click`, the `press` method is used to submit a form with a submit button that has the given text.

```php
$this->visit('search')
     ->type('Total Recall', '#q')
     ->press('Search Now');
```

When called, this package will handle the process of submitting the form, and following any applicable redirects. This means, we could combine some of previous examples to form a full integration test.

```php
$this->visit('/search')
     ->type('Total Recall', '#q')
     ->press('Search Now')
     ->andSee('Search results for "Total Recall"')
     ->onPage('/search/results');
```

#### `submitForm($submitText, $formData)`

For situations where multiple form inputs must be filled out, you might choose to forego multiple `type()` calls, and instead use the `submitForm` method.

```php
$this->visit('/search')
     ->submitForm('Search Now', ['q' => 'Total Recall']);
```

This method offers a more compact option, which will both populate and submit the form.

Take special note of the second argument,  which is for the form data. You'll want to pass an associative array, where each key refers to the "name" of an input (not the element name, but the "name" attribute). As such, this test would satisfy the following form:

```html
<form method="POST" action="/search/results">
  <input type="text" name="q" placeholder="Search for something...">
  <input type="submit" value="Search Now">
</form>
```

#### `seeInDatabase($table, $data)` or `verifyInDatabase($table, $data)`

For situations when you want to peek inside the database to verify that a certain record/row exists, `seeInDatabase` or its alias `verifyInDatabase` will do the trick nicely.

```php
$data = ['description' => 'Finish documentation'];

$this->visit('/tasks')
     ->submitForm('Create Task', $data)
     ->verifyInDatabase('tasks', $data);
```

When calling `verifyInDatabase`, as the two arguments, provide the name of the table you're interested in, and an array of any attributes for the query.

**Important:** If using the Laravel-specific extension, this package will use your existing database configuration. There's nothing more for you to do. However, if using the Goutte extension for a general PHP project, you'll need to create a `integrated.json` file in your project root, and then specify your database connection string. Here's a couple examples:

**SQLite Config**

```js
{
    "pdo": {
        "connection": "sqlite:storage/database.sqlite",
        "username": "",
        "password": ""
    }
}
```

**MySQL Config**

```js
{
    "pdo": {
        "connection": "mysql:host=localhost;dbname=myDatabase"
        "username": "homestead",
        "password": "secret"
    }
}
```

#### `dump()`

When you want to quickly spit out the response content from the most recent request, call the `dump` method, like so:

```php
$this->visit('/page')->dump();
```

This will both dump the response content to the console, and save it to a `tests/logs/output.txt` file, for your review. Please note that this method will `die`. So no tests beyond this call will be fired. Nonetheless, it's great for the times when you need a better look at what you're working with, temporarily of course.

## Extras

### Database Transactions

If you're using the Laravel extension of this package, then you may also pull in a trait, which automatically sets up database transactions. By including this trait, after each test completes, your database will be "rolled back" to its original state. For example, if one test you write requires you to populate a table with a few rows. Well, after that test finishes, this trait will clear out those rows automatically.

Use it, like so:

```php
<?php

use Laracasts\Integrated\Extensions\Laravel as IntegrationTest;
use Laracasts\Integrated\Services\Laravel\DatabaseTransactions;

class ExampleTest extends IntegrationTest {
  use DatabaseTransactions;
}
```

Done!

### TestDummy

To help with RAD, this package includes the "laracasts/testdummy" package out of the box. For integration tests that hit a database, you'll likely want this anyways. Refer to the [TestDummy](https://github.com/laracasts/TestDummy) documentation for a full overview, but, in short, it gives you a very simple way to build up and/or persist your entities (like your Eloquent models), for the purposes of testing.

Think of it as your way of saying, "*Well, assuming that I have these records in my database table*, when I yadayada".

```php
use Laracasts\TestDummy\Factory as TestDummy;

// ...

/** @test */
function it_shows_posts()
{
  TestDummy::create('App\Post', ['title' => 'Example Post']);

  $this->visit('/posts')->andSee('Example Post');
}
```

## FAQ

#### Can I test JavaScript with this method?

Not quite yet, however, a Selenium extension is in the works for this package. Check back soon.

