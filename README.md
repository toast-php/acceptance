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
composer require --dev gentry/acceptance
```

This will also install the PhantomJS headless browser, which requires the PHP
BZ2 module. It's listed as a dependency, so Composer will complain if you don't
have it already.

Add the following to (the root of) your application's `composer.json`:

```json
"scripts": {
    "post-install-cmd": [
        "PhantomInstaller\\Installer::installPhantomJS"
    ],
    "post-update-cmd": [
        "PhantomInstaller\\Installer::installPhantomJS"
    ]
}
```

You could also call this manually, but this is handy. Run `composer update`. The
`phantomjs` binary is now installed into your `vendor/bin` directory. Create a
`bin` directory in your application's root if you don't have one yet, and
symlink the executable there.

> This is needed due to an apparent bug in the PhpPhantomJs package. It's
> _supposed_ to get its location from Composer, only it seems to be hardcoded.

Note that the PhantomInstaller requires PHP's BZ2 module (its `composer.json`
doesn't explicitly state that, without it the installation will fail with no
meaningful error message).

## Preparing your project
Your project needs to be made Toast-aware. For regular tests we did that via
the `getenv('TOAST')` check; for HTTP calls it is similar. Toast's browser
passes these variables in headers, and they are thus available as
`$_SERVER['HTTP_TOAST_something']` entries in the server superglobal.

In a central place - this can be any place depending on your project, as long as
you're 100% sure every called page will run that bit of code - place a check for
these headers and make sure your application understands it's running in test
mode if they're set.

> So, in test mode, use a mock database etc.

## Writing an acceptance test
To write these tests, we'll make use of the `Toast\Acceptance\Browser` object.
This is a wrapper around PHP PhantomJS with some convenience methods.

```php
<?php

class Test
{
    /**
     * Going to grab an external page
     */
    public function getAPage()
    {
        $browser = new Toast\Acceptance\Browser;
        yield asset($browser->get('http://example.com/')->getStatus() == 200);
    }
}
```

The `get` and `post` methods on the Browser return a "response" object. This
tells us stuff about the page we just retrieved, like the HTTP status code we
were checking in this example. You can also get the full page contents, inspect
all headers etc. See the PHP PhantomJS API documentation for all options.

## Sessions and cookies
PhantomJS (and, therefore, the Toast `Browser`) can also handle sessions (so
you can test stuff like logging in and performing restricted operations).

While we could have jumped through hoops and tried to read the session ID
actually set by a request, we chose to "fixate" the required session ID instead
on the `Browser` object. This is much more convenient for a number of reasons:

1. It's simpler, to begin with - no parsing of cookie jars.
2. You have _complete_ control over sessions.
3. You don't have to do an extra request before `POST`ing to a restricted page
   to first get the ID, perform a login etc.
4. Even better: assuming you have access to the session data, since you can know
   the ID in advance you can setup a logged in user!

To use sessions you'll need the additional `gentry/cache` package:

```sh
$ composer require --dev gentry/cache
```

When you construct the `Browser` object you can pass an optional `$sessionid`
parameter. This is the session ID used:

```php
<?php

// ...
$browser = new Toast\Acceptance\Browser('abcd');
// ...
```

> When passing a custom id, it's up to you to make sure your test script and
> your online application can somehow access the same session storage. If your
> application uses [database sessions](http://cesession.monomelodies.nl) that
> won't be an issue; if you're using PHP sessions both the CLI tests and the
> test site instance will need access to the same `tmp` directory.

Custom session IDs are useful if either your session handler is picky about the
format of the session ID, _or_ when testing with multiple users (see below).

To use a custom session name, set the static `Browser::$sessionname` string to
whatever you need. Toast makes the reasonable assumption that session names are
constant within an appliction.

If you don't explicitly set a session ID, chances are your application will try
to set one (which Toast will ignore).

For most applications, a session ID as generated by `session_id()` will suffice.
So as a shorthand, you may pass `true` as a contructor parameter to use this
default.

## Testing with multiple concurrent users
A handy propery of the `Browser` object accepting a random session id is the
possibility for an acceptance test to actually test interaction between
different users of your application. E.g., user John logs in and sends a message
to Mary; when Mary opens her message center, she should see the new message.

An acceptance test with multiple users would be implemented something like this:

```php
<?php

// ...
// We let PHP generate random session ids.
$john = new Browser(session_id());
$mary = new Browser(session_id());

// Here you would first make sure both users are logged in; how that mechanism
// works exactly is up to your application of course.

// John sends his message
$response = $john->post('/message/', ['to' => 'Mary', 'body' => 'Hi Mary!']);
yield assert($response->getStatus() == 200);
// Mary opens the message page
$response = $mary->get('/message/');
yield assert(strpos($response->getContent(), 'Unread: 1'));
// Empty mock database, so this message got ID 1
$response = $mary->get('/message/1/');
yield assert(strpos($response->getContent(), 'From: John'));
// Mary read it, so now unread should be 0 again
$response = $mary->get('/message/');
yield assert(strpos($response->getContent(), 'Unread: 0'));
// And finally John would see the message was read:
$response = $john->get('/message/1/');
yield assert(strpos($response->getContent(), 'Status: read'));
```

