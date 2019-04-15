<?php

use Gentry\Gentry\Wrapper;

/** Testsuite for Toast\Acceptance\Browser */
return function () : Generator {
    $browser = Wrapper::createObject(Toast\Acceptance\Browser::class, 'chromium');
    /** get yields true */
    yield function () use ($browser) {
        $page = $browser->get('file://'.__DIR__.'/files/get.html');
        $value = $page->evaluate('document.querySelector("body").innerHTML')->getReturnValue();
        assert(trim($value) === '<h1>Hello world</h1>');
    };

    /** post yields true */
    yield function () use ($browser) {
        $page = $browser->post('http://localhost:8000', 'form', ['foo' => 'bar']);
        $value = $page->evaluate('document.querySelector("h1").innerHTML')->getReturnValue();
        assert($value == 'bar');
    };

};

