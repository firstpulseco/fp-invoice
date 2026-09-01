<?php

use App\Models\BusinessSetting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('business invoice defaults can be configured', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::settings.business')
        ->set('businessName', 'First Pulse')
        ->set('streetAddress', '123 Studio Way')
        ->set('city', 'Fort Wayne')
        ->set('region', 'IN')
        ->set('postalCode', '46802')
        ->set('email', 'hello@firstpulseco.com')
        ->set('defaultHourlyRate', '80')
        ->set('paymentTerms', 'Payment due within 30 days')
        ->set('paymentMethods', [
            ['key' => 'ach', 'name' => 'ACH transfer', 'details' => 'Send payment to the account on file.'],
            ['key' => 'check', 'name' => 'Check', 'details' => 'Mail to 123 Studio Way.'],
        ])
        ->set('invoiceFooter', 'Thank you for your business.')
        ->call('save')
        ->assertHasNoErrors();

    $settings = BusinessSetting::query()->sole();

    expect($settings->business_name)->toBe('First Pulse')
        ->and($settings->default_hourly_rate)->toBe('80.00')
        ->and($settings->payment_methods)->toBe([
            ['name' => 'ACH transfer', 'details' => 'Send payment to the account on file.'],
            ['name' => 'Check', 'details' => 'Mail to 123 Studio Way.'],
        ])
        ->and($settings->invoice_footer)->toBe('Thank you for your business.');
});

test('the configured business name is used in the page title', function () {
    BusinessSetting::factory()->create(['business_name' => 'First Pulse']);

    $this->get(route('login'))
        ->assertSee('Log In - First Pulse')
        ->assertSee('Log In to Your Account');
});

test('payment methods are optional', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::settings.business')
        ->set('businessName', 'No Payment Setup')
        ->set('defaultHourlyRate', '80')
        ->set('paymentTerms', 'Due within 30 days')
        ->set('paymentMethods', [])
        ->call('save')
        ->assertHasNoErrors();

    expect(BusinessSetting::query()->sole()->payment_methods)->toBeNull();
});

test('default hourly rates require whole-dollar increments', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::settings.business')
        ->set('businessName', 'First Pulse')
        ->set('defaultHourlyRate', '80.50')
        ->set('paymentTerms', 'Due within 30 days')
        ->call('save')
        ->assertHasErrors(['defaultHourlyRate']);

    expect(BusinessSetting::query()->count())->toBe(0);
});

test('the shared mark uses the uploaded business logo', function () {
    Storage::fake('public');
    Storage::disk('public')->put('logos/brand.png', 'logo-contents');
    $settings = BusinessSetting::factory()->create(['logo_path' => 'logos/brand.png']);
    $logoUrl = route('branding.logo', ['v' => $settings->updated_at->getTimestamp()]);

    $this->get(route('login'))
        ->assertSee($logoUrl, escape: false);

    $this->get(route('branding.logo'))
        ->assertOk()
        ->assertHeader('cache-control', 'max-age=3600, public');
});

test('the shared mark falls back when the configured logo is unavailable', function () {
    Storage::fake('public');
    BusinessSetting::factory()->create(['logo_path' => 'logos/missing.png']);

    $this->get(route('login'))
        ->assertSee('bg-sky', escape: false)
        ->assertDontSee(route('branding.logo'), escape: false);

    $this->get(route('branding.logo'))->assertNotFound();
});
