<?php

use Gentry\Gentry\Wrapper;

/** Testsuite for Toast\Acceptance\Browser */
return function () : Generator {
    $object = Wrapper::createObject(Toast\Acceptance\Browser::class, 'chromium');
    /** get yields true */
    yield function () use ($object) {
        $result = $object->get('MIXED');
        assert(true);
    };

    /** post yields true */
    yield function () use ($object) {
        $result = $object->post('MIXED', []);
        assert(true);
    };

};

