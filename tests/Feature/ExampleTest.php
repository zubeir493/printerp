<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    expect($response->status())->toBeIn([200, 302]);
});
