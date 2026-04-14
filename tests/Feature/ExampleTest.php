<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $this->assertContains($response->status(), [200, 302]);
});
