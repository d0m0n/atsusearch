<?php

it('redirects root to search page', function () {
    $response = $this->get('/');

    $response->assertRedirect('/search');
});
