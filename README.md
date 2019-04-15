# Acceptance
Acceptance tests for the Toast test framework

One notch up from integration tests, acceptance tests should test your entire
application. That means actually using a web browser for most web applications.

> One could argue that a CLI test is also a form of an acceptance test, with the
> command line acting as a stand-in for a browser. Semantics, semantics...

Toast is written in PHP which (duh) gets compiled on the server. Websites
consist of HTML, CSS and Javascript. Hence, you'll need a headless browser like
PhantomJS to run your acceptance tests.

## Prerequisites
Install the acceptance module:

```bash
composer require --dev toast/acceptance
```

Acceptance testing requires Chrome or Chromium Headless to be installed.

## Preparing your project
Your project needs to be made Toast-aware. For regular tests we did that via
the `getenv('TOAST')` check; for HTTP calls it is similar. Toast's browser
passes this variable as `$_COOKIE['TOAST']`.

In a central place - this can be any place depending on your project, as long as
you're 100% sure every called page will run that bit of code - place a check for
these headers and make sure your application understands it's running in test
mode if they're set.

> So, in test mode, use a mock database etc.

Note: you probably also want to only check this on a development URL -
production should of course ignore the `$_COOKIE['TOAST']` variable.

The `TOAST_CLIENT` environment variable is also passed in this manner.

## Writing an acceptance test
To write these tests, we'll make use of the `Toast\Acceptance\Browser` object.
This is a wrapper around Headless Chromium PHP with some convenience methods.

```php
<?php

/** Example browser test */
return function () : Generator {
    /** We can grab an external page */
    yield function () {
        $browser = new Toast\Acceptance\Browser;
        $page = $browser->get('http://example.com');
        $ev
        assert($browser->get('http://example.com/')->getStatus() == 200);
    };
};
```

The `get` and `post` methods on the Browser return a `Page` object. See the
[https://github.com/chrome-php/headless-chromium-php](Headless Chromium
documentation) for more info on how to use this.

## Posting data
Using the `post` method requires some additional parameters:

- A querySelector string to identify the form we want to post;
- An array of key/value pairs of data to post;
- Optionally, a querySelector string to submit the form. This defaults to
  `button[type=submit]`.

## Testing with multiple concurrent users
A handy propery of the `Browser` object accepting a random session id is the
possibility for an acceptance test to actually test interaction between
different users of your application. E.g., user John logs in and sends a message
to Mary; when Mary opens her message center, she should see the new message.

An acceptance test with multiple users would be implemented something like this:

```php
<?php

// ...
$john = new Browser('chrome', 1);
$mary = new Browser('chrome', 2);

// Here you would first make sure both users are logged in; how that mechanism
// works exactly is up to your application of course.

// John sends his message
$johnPage = $john->post('/message/', 'form', ['to' => 'Mary', 'body' => 'Hi Mary!']);
// Mary opens the message page
$maryPage = $mary->get('/message/');
assert(strpos($maryPage->evaluate("document.querySelector('body').innerHTML")->getReturnValue(), 'Unread: 1') !== false);
// Empty mock database, so this message got ID 1
$maryPage = $mary->get('/message/1/');
assert(strpos($maryPage->evaluate("document.querySelector('body').innerHTML")->getReturnValue(), 'From: John') !== false);
// Mary read it, so now unread should be 0 again
$maryPage = $mary->get('/message/');
assert(strpos($maryPage->evaluate("document.querySelector('body').innerHTML")->getReturnValue(), 'Unread: 0') !== false);
// And finally John would see the message was read:
$johnPage = $john->get('/message/1/');
assert(strpos($johnPage->evaluate("document.querySelector('body').innerHTML")->getReturnValue(), 'Status: read') !== false);
```

