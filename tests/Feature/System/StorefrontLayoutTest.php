<?php

test('storefront layout renders successfully on the home page', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('<!DOCTYPE html>', false);
});
