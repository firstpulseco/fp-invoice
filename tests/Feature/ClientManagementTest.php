<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Livewire\Livewire;

test('a client can be added and updated', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::clients.index')
        ->set('companyName', 'Fieldwork Studio')
        ->set('contactName', 'Taylor Field')
        ->set('email', 'taylor@example.com')
        ->call('save')
        ->assertHasNoErrors();

    $client = Client::query()->firstOrFail();

    Livewire::test('pages::clients.index')
        ->call('edit', $client)
        ->set('phone', '260-555-0100')
        ->call('save')
        ->assertHasNoErrors();

    expect($client->refresh()->phone)->toBe('260-555-0100');
});

test('removing a client does not remove or change its invoices', function () {
    $this->actingAs(User::factory()->create());
    $client = Client::factory()->create(['company_name' => 'Archived Client']);
    $invoice = Invoice::factory()->for($client)->create([
        'client_company_name' => 'Archived Client',
        'client_street_address' => '12 Saved Street',
    ]);

    Livewire::test('pages::clients.index')
        ->call('delete', $client)
        ->assertHasNoErrors();

    $this->assertSoftDeleted($client);
    $this->assertModelExists($invoice);
    expect($invoice->refresh()->client_company_name)->toBe('Archived Client')
        ->and($invoice->client_street_address)->toBe('12 Saved Street');
});
