<?php

use App\InvoiceStatus;
use App\Models\BusinessSetting;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\PricingType;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('guests are redirected to sign in and users land on invoices', function () {
    $this->get(route('invoices.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('invoices.index'))
        ->assertSee('Invoices');
});

test('the application supports white light mode and black dark mode surfaces', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('invoices.index'))
        ->assertSee('bg-white dark:bg-black', escape: false)
        ->assertSee('$flux.appearance', escape: false);
});

test('invoice editor headings use title case', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('invoices.create'))
        ->assertSee('NEW INVOICE')
        ->assertSee('SERVICES')
        ->assertSee('Line Items')
        ->assertSee('INVOICE TOTAL')
        ->assertSee('step="1"', escape: false)
        ->assertDontSee('border-b border-zinc-300 pb-8', escape: false)
        ->assertSeeInOrder(['Line Items', 'Description', 'Add Item', 'INVOICE TOTAL']);
});

test('application eyebrow labels use uppercase', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('invoices.index'))
        ->assertSee('OVERVIEW')
        ->assertDontSee('border-b border-zinc-300 pb-8', escape: false);
    $this->get(route('clients.index'))
        ->assertSee('DIRECTORY')
        ->assertDontSee('border-b border-zinc-300 pb-8', escape: false);
    $this->get(route('business.edit'))
        ->assertSee('CONFIGURATION')
        ->assertDontSee('border-b border-zinc-300 pb-8', escape: false);
});

test('an invoice can mix hourly and fixed price items', function () {
    $this->actingAs(User::factory()->create());
    $client = Client::factory()->create([
        'company_name' => 'Acme Studio',
        'street_address' => '100 Main Street',
    ]);
    BusinessSetting::factory()->create([
        'business_name' => 'First Pulse',
        'default_hourly_rate' => 100,
        'payment_terms' => 'Due within 30 days',
        'payment_methods' => [
            ['name' => 'ACH transfer', 'details' => 'Account ending in 1234'],
            ['name' => 'Check', 'details' => 'Mail to the studio'],
        ],
    ]);

    $component = Livewire::test('pages::invoices.form')
        ->set('clientId', $client->id)
        ->set('number', '001')
        ->set('invoiceDate', '2026-09-01')
        ->set('dueDate', '2026-10-01')
        ->set('status', InvoiceStatus::Sent->value)
        ->set('items', [
            [
                'key' => 'hourly-item',
                'description' => 'Design revisions',
                'details' => 'Second round',
                'pricingType' => PricingType::Hourly->value,
                'hourlyRate' => '100',
                'hours' => '2.25',
                'amount' => '0',
            ],
            [
                'key' => 'fixed-item',
                'description' => 'Printing setup',
                'details' => '',
                'pricingType' => PricingType::Fixed->value,
                'hourlyRate' => '0',
                'hours' => '0',
                'amount' => '75',
            ],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertDispatched('toast-show');

    $invoice = Invoice::query()->firstOrFail();

    expect($invoice->total)->toBe('300.00')
        ->and($invoice->client_company_name)->toBe('Acme Studio')
        ->and($invoice->client_street_address)->toBe('100 Main Street')
        ->and($invoice->business_name)->toBe('First Pulse')
        ->and($invoice->payment_methods)->toHaveCount(2)
        ->and($invoice->items()->count())->toBe(2)
        ->and($component->get('invoice')->is($invoice))->toBeTrue();

    $this->assertDatabaseHas('invoice_items', [
        'invoice_id' => $invoice->id,
        'description' => 'Design revisions',
        'amount' => 225,
    ]);

    $component
        ->set('status', InvoiceStatus::Paid->value)
        ->call('save')
        ->assertNoRedirect();

    expect(Invoice::query()->count())->toBe(1)
        ->and($invoice->refresh()->status)->toBe(InvoiceStatus::Paid);
});

test('hourly invoice items require quarter-hour increments', function () {
    $this->actingAs(User::factory()->create());
    $client = Client::factory()->create();

    Livewire::test('pages::invoices.form')
        ->set('clientId', $client->id)
        ->set('items.0.description', 'Design work')
        ->set('items.0.hourlyRate', '100')
        ->set('items.0.hours', '0.10')
        ->call('save')
        ->assertHasErrors(['items.0.hours']);

    expect(Invoice::query()->count())->toBe(0);
});

test('hourly rates require whole-dollar increments', function () {
    $this->actingAs(User::factory()->create());
    $client = Client::factory()->create();

    Livewire::test('pages::invoices.form')
        ->set('clientId', $client->id)
        ->set('items.0.description', 'Design work')
        ->set('items.0.hourlyRate', '100.50')
        ->set('items.0.hours', '1')
        ->call('save')
        ->assertHasErrors(['items.0.hourlyRate']);

    expect(Invoice::query()->count())->toBe(0);
});

test('invoice preview displays each snapshotted payment method', function () {
    $this->actingAs(User::factory()->create());
    $invoice = Invoice::factory()->create([
        'payment_methods' => [
            ['name' => 'ACH transfer', 'details' => 'Account ending in 1234'],
            ['name' => 'Check', 'details' => 'Mail to the studio'],
        ],
    ]);
    InvoiceItem::factory()->for($invoice)->create([
        'position' => 0,
        'description' => 'Identity Design',
        'details' => 'Final design package',
    ]);

    $this->get(route('invoices.show', $invoice))
        ->assertSee('invoice-sheet text-ink mx-auto min-h-[11in] w-full max-w-[8.5in] bg-white', escape: false)
        ->assertSee('pr-1 text-right', escape: false)
        ->assertSee('border-b border-zinc-300 py-3', escape: false)
        ->assertSee('border-b-2 pb-7', escape: false)
        ->assertSee('gap-x-8 gap-y-8 py-8', escape: false)
        ->assertSee('invoice-payment border-ink mt-8', escape: false)
        ->assertSee('break-words text-balance', escape: false)
        ->assertDontSee('text-pretty', escape: false)
        ->assertSee('hyphens-none', escape: false)
        ->assertDontSee('hyphens-auto', escape: false)
        ->assertDontSee('tracking-[0.24em]', escape: false)
        ->assertDontSee('tracking-[0.16em]', escape: false)
        ->assertDontSee('tracking-[0.15em]', escape: false)
        ->assertSee('ACH transfer')
        ->assertSee('Account ending in 1234')
        ->assertSee('Check')
        ->assertSee('Mail to the studio');
});

test('invoice previews use the current business logo', function () {
    $this->actingAs(User::factory()->create());
    Storage::fake('public');
    Storage::disk('public')->put('logos/current.png', 'current-logo');
    $settings = BusinessSetting::factory()->create([
        'logo_path' => 'logos/current.png',
    ]);
    $invoice = Invoice::factory()->create([
        'business_name' => 'Archived Studio',
        'business_logo_path' => 'logos/previous.png',
    ]);
    $logoUrl = route('branding.logo', ['v' => $settings->updated_at->getTimestamp()]);

    $this->get(route('invoices.show', $invoice))
        ->assertSee('src="'.$logoUrl.'" alt="Archived Studio"', escape: false)
        ->assertDontSee('logos/previous.png', escape: false);
});

test('invoice previews use the current payment methods', function () {
    $this->actingAs(User::factory()->create());
    BusinessSetting::factory()->create([
        'payment_method_name' => 'Zelle',
        'payment_details' => 'ID: firstpulse',
        'payment_methods' => [
            ['name' => 'Zelle', 'details' => 'ID: firstpulse'],
        ],
    ]);
    $invoice = Invoice::factory()->create([
        'payment_method_name' => 'Zelle',
        'payment_details' => 'Zelle ID: firstpulse',
        'payment_methods' => [
            ['name' => 'Zelle', 'details' => 'Zelle ID: firstpulse'],
        ],
    ]);

    $this->get(route('invoices.show', $invoice))
        ->assertSee('ID: firstpulse')
        ->assertDontSee('Zelle ID: firstpulse');
});

test('editing an invoice preserves its original client snapshot', function () {
    $this->actingAs(User::factory()->create());
    $client = Client::factory()->create([
        'company_name' => 'Original Company',
        'street_address' => '10 Original Avenue',
    ]);
    $invoice = Invoice::factory()->for($client)->create([
        'number' => '010',
        'client_company_name' => 'Original Company',
        'client_street_address' => '10 Original Avenue',
        'total' => 100,
    ]);
    InvoiceItem::factory()->for($invoice)->create([
        'position' => 0,
        'description' => 'Identity work',
        'amount' => 100,
    ]);
    $client->update([
        'company_name' => 'Renamed Company',
        'street_address' => '99 New Street',
    ]);

    Livewire::test('pages::invoices.form', ['invoice' => $invoice])
        ->assertSee('EDIT INVOICE')
        ->set('status', InvoiceStatus::Paid->value)
        ->call('save')
        ->assertHasNoErrors();

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->client_company_name)->toBe('Original Company')
        ->and($invoice->client_street_address)->toBe('10 Original Avenue');
});

test('invoice search matches number and client and status filtering works', function () {
    $this->actingAs(User::factory()->create());
    Invoice::factory()->create([
        'number' => '101',
        'client_company_name' => 'Northstar Arts',
        'status' => InvoiceStatus::Draft,
    ]);
    Invoice::factory()->create([
        'number' => '102',
        'client_company_name' => 'Other Client',
        'status' => InvoiceStatus::Paid,
    ]);

    Livewire::test('pages::invoices.index')
        ->set('search', 'Northstar')
        ->assertSee('Northstar Arts')
        ->assertDontSee('Other Client')
        ->set('search', '')
        ->set('status', InvoiceStatus::Paid->value)
        ->assertSee('Other Client')
        ->assertDontSee('Northstar Arts');
});
