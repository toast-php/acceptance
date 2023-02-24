<?php

use Gentry\Gentry\Wrapper;

/** Tests for browser. Note: run php -S 127.0.0.1:8000 files/Server.php alongside! */
return function () : Generator {
    $browser = Wrapper::createObject(Toast\Acceptance\Browser::class, 'google-chrome');
    /** `get` returns a "page" */
    yield function () use ($browser) {
        $page = $browser->get('http://localhost:8000');
        $value = $page->evaluate('document.querySelector("h1").innerHTML')->getReturnValue();
        assert(trim($value) === 'Hello world');
    };

    // Note: to run this test, first do php -S 127.0.0.1:8000 files/Server.php
    /** `post` correctly posts data to a page */
    yield function () use ($browser) {
        $page = $browser->post('http://localhost:8000', 'form', ['foo' => 'bar']);
        $value = $page->evaluate('document.querySelector("h1").innerHTML')->getReturnValue();
        assert($value == 'bar');
    };

};

