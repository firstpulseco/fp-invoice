<?php

test('home redirects to invoices', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('invoices.index'));
});
