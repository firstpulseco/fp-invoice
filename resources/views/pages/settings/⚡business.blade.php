<?php

use App\Models\BusinessSetting;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Business Settings')] class extends Component {
    use WithFileUploads;

    public ?int $settingId = null;
    public string $businessName = '';
    public string $existingLogoPath = '';
    public $logo = null;
    public string $streetAddress = '';
    public string $city = '';
    public string $region = '';
    public string $postalCode = '';
    public string $phone = '';
    public string $email = '';
    public string $defaultHourlyRate = '0.00';
    public string $paymentTerms = 'Due within 30 days';
    /** @var array<int, array{key: string, name: string, details: string}> */
    public array $paymentMethods = [];
    public string $invoiceFooter = '';

    public function mount(): void
    {
        $settings = BusinessSetting::query()->first();

        if (! $settings) {
            return;
        }

        $this->settingId = $settings->id;
        $this->businessName = $settings->business_name ?? '';
        $this->existingLogoPath = $settings->logo_path ?? '';
        $this->streetAddress = $settings->street_address ?? '';
        $this->city = $settings->city ?? '';
        $this->region = $settings->region ?? '';
        $this->postalCode = $settings->postal_code ?? '';
        $this->phone = $settings->phone ?? '';
        $this->email = $settings->email ?? '';
        $this->defaultHourlyRate = $settings->default_hourly_rate;
        $this->paymentTerms = $settings->payment_terms;
        $paymentMethods = $settings->payment_methods;

        if (! $paymentMethods && ($settings->payment_method_name || $settings->payment_details)) {
            $paymentMethods = [[
                'name' => $settings->payment_method_name ?? '',
                'details' => $settings->payment_details ?? '',
            ]];
        }

        $this->paymentMethods = collect($paymentMethods ?? [])->map(fn (array $method): array => [
            'key' => (string) str()->uuid(),
            'name' => $method['name'] ?? '',
            'details' => $method['details'] ?? '',
        ])->all();
        $this->invoiceFooter = $settings->invoice_footer ?? '';
    }

    public function addPaymentMethod(): void
    {
        $this->paymentMethods[] = [
            'key' => (string) str()->uuid(),
            'name' => '',
            'details' => '',
        ];
    }

    public function removePaymentMethod(int $index): void
    {
        unset($this->paymentMethods[$index]);
        $this->paymentMethods = array_values($this->paymentMethods);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'businessName' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'streetAddress' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'postalCode' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'defaultHourlyRate' => ['required', 'numeric', 'min:0', 'max:999999.99', 'multiple_of:1'],
            'paymentTerms' => ['required', 'string', 'max:255'],
            'paymentMethods' => ['array'],
            'paymentMethods.*.name' => ['required', 'string', 'max:255'],
            'paymentMethods.*.details' => ['nullable', 'string', 'max:5000'],
            'invoiceFooter' => ['nullable', 'string', 'max:5000'],
        ]);

        $logoPath = $this->existingLogoPath ?: null;

        if ($this->logo) {
            $logoPath = $this->logo->store('logos', 'public');
        }

        $paymentMethods = collect($validated['paymentMethods'])->map(fn (array $method): array => [
            'name' => $method['name'],
            'details' => $method['details'] ?: '',
        ])->values()->all();
        $primaryPaymentMethod = $paymentMethods[0] ?? null;

        $settings = BusinessSetting::query()->updateOrCreate(
            ['id' => $this->settingId],
            [
                'business_name' => $validated['businessName'],
                'logo_path' => $logoPath,
                'street_address' => $validated['streetAddress'] ?: null,
                'city' => $validated['city'] ?: null,
                'region' => $validated['region'] ?: null,
                'postal_code' => $validated['postalCode'] ?: null,
                'phone' => $validated['phone'] ?: null,
                'email' => $validated['email'] ?: null,
                'default_hourly_rate' => $validated['defaultHourlyRate'],
                'payment_terms' => $validated['paymentTerms'],
                'payment_method_name' => $primaryPaymentMethod['name'] ?? null,
                'payment_details' => $primaryPaymentMethod['details'] ?? null,
                'payment_methods' => $paymentMethods ?: null,
                'invoice_footer' => $validated['invoiceFooter'] ?: null,
            ],
        );

        $this->settingId = $settings->id;
        $this->existingLogoPath = $settings->logo_path ?? '';
        $this->logo = null;
        Flux::toast(variant: 'success', text: 'Business settings saved.');
    }
}; ?>

<div class="mx-auto w-full max-w-6xl py-6 sm:py-10">
    <header class="pb-8">
        <p class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">CONFIGURATION</p>
        <h1 class="text-4xl font-medium tracking-[-0.04em] sm:text-5xl">Settings</h1>
        <p class="mt-4 max-w-2xl text-zinc-600 dark:text-zinc-400">These details appear on new invoices. Logo changes apply to every invoice; existing invoices keep their original billing and payment information.</p>
    </header>

    <form wire:submit="save" class="pt-10">
        <div class="grid gap-x-14 gap-y-12 lg:grid-cols-2">
            <section>
                <h2 class="border-t-2 border-zinc-950 pt-4 text-xl font-medium tracking-tight dark:border-white">Business Identity</h2>
                <div class="mt-7 grid gap-5">
                    <flux:input wire:model="businessName" label="Business name" required />
                    <flux:field>
                        <flux:label>Logo</flux:label>
                        <input wire:model="logo" type="file" accept="image/png,image/jpeg,image/webp" class="border-breeze bg-cloud dark:border-slate dark:bg-slate block w-full rounded-lg border px-3 py-2 text-sm file:mr-4 file:border-0 file:bg-transparent file:font-medium" />
                        <flux:error name="logo" />
                    </flux:field>
                    @if ($logo)
                        <img src="{{ $logo->temporaryUrl() }}" alt="New logo preview" class="max-h-16 max-w-48 object-contain object-left" />
                    @elseif ($existingLogoPath)
                        <img src="{{ Storage::url($existingLogoPath) }}" alt="Current business logo" class="max-h-16 max-w-48 object-contain object-left" />
                    @endif
                    <flux:input wire:model="streetAddress" label="Street address" />
                    <div class="grid gap-5 sm:grid-cols-3">
                        <flux:input wire:model="city" label="City" />
                        <flux:input wire:model="region" label="State or region" />
                        <flux:input wire:model="postalCode" label="Postal code" />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:input wire:model="phone" label="Phone" />
                        <flux:input wire:model="email" label="Email" type="email" />
                    </div>
                </div>
            </section>

            <section>
                <h2 class="border-t-2 border-zinc-950 pt-4 text-xl font-medium tracking-tight dark:border-white">Invoice Defaults</h2>
                <div class="mt-7 grid gap-5">
                    <flux:input wire:model="defaultHourlyRate" label="Default hourly rate" type="number" min="0" step="1" prefix="$" />
                    <flux:input wire:model="paymentTerms" label="Normal payment terms" />
                    <div class="border-breeze dark:border-navy border-t pt-5">
                        <div class="flex items-start justify-between gap-5">
                            <div>
                                <p class="font-medium">Payment Methods</p>
                                <p class="text-navy dark:text-ice mt-1 text-sm">Optional. Add each way clients can pay.</p>
                            </div>
                            <flux:button type="button" wire:click="addPaymentMethod" variant="ghost" icon="plus" size="sm">Add method</flux:button>
                        </div>

                        <div class="mt-5 grid gap-6">
                            @foreach ($paymentMethods as $index => $paymentMethod)
                                <div wire:key="payment-method-{{ $paymentMethod['key'] }}" class="border-breeze dark:border-navy grid gap-4 border-t pt-5">
                                    <div class="flex items-end gap-3">
                                        <flux:input wire:model="paymentMethods.{{ $index }}.name" label="Payment type" placeholder="ACH transfer, check, PayPal" class="flex-1" />
                                        <flux:button type="button" wire:click="removePaymentMethod({{ $index }})" variant="ghost" icon="trash" aria-label="Remove payment method" />
                                    </div>
                                    <flux:textarea wire:model="paymentMethods.{{ $index }}.details" label="Payment details" rows="3" placeholder="Account, routing, mailing, or payment-link instructions" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <flux:textarea wire:model="invoiceFooter" label="Invoice footer or legal text" rows="4" />
                </div>
            </section>
        </div>

        <div class="mt-12 flex items-center justify-between border-t border-zinc-300 pt-6 dark:border-zinc-700">
            <p class="text-sm text-zinc-500">Account and security settings remain available from your user menu.</p>
            <flux:button type="submit" variant="primary">Save settings</flux:button>
        </div>
    </form>
</div>
