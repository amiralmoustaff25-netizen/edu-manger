<?php

it('redirects the root route to the login page for guests', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});
