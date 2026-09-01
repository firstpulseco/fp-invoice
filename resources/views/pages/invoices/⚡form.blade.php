<?php

use App\InvoiceStatus;
use App\Models\BusinessSetting;
use App\Models\Client;
use App\Models\Invoice;
use App\PricingType;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Invoice Editor')] class extends Component {
    public ?Invoice $invoice = null;
    public ?int $clientId = null;
    public ?int $originalClientId = null;
    public string $number = '';
    public string $invoiceDate = '';
    public string $dueDate = '';
    public string $status = 'draft';
    public string $defaultHourlyRate = '0.00';

    /** @var array<int, array{key: string, description: string, details: string, pricingType: string, hourlyRate: string, hours: string, amount: string}> */
    public array $items = [];

    public function mount(?Invoice $invoice = null): void
    {
        $this->invoice = $invoice;
        $settings = BusinessSetting::query()->first();
        $this->defaultHourlyRate = $settings?->default_hourly_rate ?? '0.00';

        if ($invoice) {
            $invoice->load('items');
            $this->clientId = $invoice->client_id;
            $this->originalClientId = $invoice->client_id;
            $this->number = $invoice->number;
            $this->invoiceDate = $invoice->invoice_date->toDateString();
            $this->dueDate = $invoice->due_date->toDateString();
            $this->status = $invoice->status->value;
            $this->items = $invoice->items->map(fn ($item): array => [
                'key' => (string) $item->id,
                'description' => $item->description,
                'details' => $item->details ?? '',
                'pricingType' => $item->pricing_type->value,
                'hourlyRate' => $item->hourly_rate ?? $this->defaultHourlyRate,
                'hours' => $item->hours ?? '1.00',
                'amount' => $item->amount,
            ])->all();

            return;
        }

        $this->number = $this->nextInvoiceNumber();
        $this->invoiceDate = now()->toDateString();
        $this->dueDate = now()->addDays(30)->toDateString();
        $this->addItem();
    }

    #[Computed]
    public function clients(): Collection
    {
        return Client::query()
            ->when($this->originalClientId, fn ($query) => $query->withTrashed()->where(function ($query): void {
                $query->whereNull('deleted_at')->orWhereKey($this->originalClientId);
            }))
            ->orderBy('company_name')
            ->get();
    }

    #[Computed]
    public function total(): float
    {
        return round(collect($this->items)->sum(fn (array $item): float => $this->itemTotal($item)), 2);
    }

    public function addItem(): void
    {
        $this->items[] = [
            'key' => (string) str()->uuid(),
            'description' => '',
            'details' => '',
            'pricingType' => PricingType::Hourly->value,
            'hourlyRate' => $this->defaultHourlyRate,
            'hours' => '1.00',
            'amount' => '0.00',
        ];

        unset($this->total);
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) === 1) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
        unset($this->total);
    }

    public function updatedItems(): void
    {
        unset($this->total);
    }

    public function save(bool $preview = false): void
    {
        $validated = $this->validate([
            'clientId' => ['required', 'integer', 'exists:clients,id'],
            'number' => ['required', 'string', 'max:50', Rule::unique('invoices', 'number')->ignore($this->invoice?->id)],
            'invoiceDate' => ['required', 'date'],
            'dueDate' => ['required', 'date', 'after_or_equal:invoiceDate'],
            'status' => ['required', Rule::enum(InvoiceStatus::class)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.details' => ['nullable', 'string', 'max:2000'],
            'items.*.pricingType' => ['required', Rule::enum(PricingType::class)],
            'items.*.hourlyRate' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'multiple_of:1'],
            'items.*.hours' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'multiple_of:0.25'],
            'items.*.amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
        ]);

        foreach ($validated['items'] as $index => $item) {
            if ($item['pricingType'] === PricingType::Hourly->value && ($item['hourlyRate'] === null || $item['hours'] === null)) {
                $this->addError("items.{$index}.hourlyRate", 'Hourly items need a rate and hours.');

                return;
            }

            if ($item['pricingType'] === PricingType::Fixed->value && $item['amount'] === null) {
                $this->addError("items.{$index}.amount", 'Fixed-price items need an amount.');

                return;
            }
        }

        $invoice = DB::transaction(function () use ($validated): Invoice {
            $client = Client::withTrashed()->findOrFail($validated['clientId']);
            $settings = BusinessSetting::query()->first();
            $invoiceData = [
                'client_id' => $client->id,
                'number' => $validated['number'],
                'invoice_date' => $validated['invoiceDate'],
                'due_date' => $validated['dueDate'],
                'status' => $validated['status'],
                'total' => $this->total,
            ];

            if (! $this->invoice || $this->originalClientId !== $client->id) {
                $invoiceData = [...$invoiceData, ...$this->clientSnapshot($client)];
            }

            if (! $this->invoice) {
                $invoiceData = [...$invoiceData, ...$this->businessSnapshot($settings)];
            }

            $invoice = $this->invoice ?? new Invoice;
            $invoice->fill($invoiceData)->save();
            $invoice->items()->delete();

            foreach ($validated['items'] as $position => $item) {
                $pricingType = PricingType::from($item['pricingType']);
                $invoice->items()->create([
                    'position' => $position,
                    'description' => $item['description'],
                    'details' => $item['details'] ?: null,
                    'pricing_type' => $pricingType,
                    'hourly_rate' => $pricingType === PricingType::Hourly ? $item['hourlyRate'] : null,
                    'hours' => $pricingType === PricingType::Hourly ? $item['hours'] : null,
                    'amount' => $this->itemTotal($item),
                ]);
            }

            return $invoice;
        });

        if ($preview) {
            $this->redirectRoute('invoices.show', ['invoice' => $invoice], navigate: true);

            return;
        }

        $this->invoice = $invoice;
        $this->originalClientId = $invoice->client_id;
        $editUrl = route('invoices.edit', $invoice, absolute: false);

        $this->js('window.history.replaceState({}, "", '.json_encode($editUrl, JSON_THROW_ON_ERROR).')');
        Flux::toast(variant: 'success', text: 'Invoice saved.');
    }

    /** @return array<string, mixed> */
    private function clientSnapshot(Client $client): array
    {
        return [
            'client_company_name' => $client->company_name,
            'client_contact_name' => $client->contact_name,
            'client_email' => $client->email,
            'client_phone' => $client->phone,
            'client_street_address' => $client->street_address,
            'client_address_line_2' => $client->address_line_2,
            'client_city' => $client->city,
            'client_region' => $client->region,
            'client_postal_code' => $client->postal_code,
        ];
    }

    /** @return array<string, mixed> */
    private function businessSnapshot(?BusinessSetting $settings): array
    {
        $paymentMethods = $settings?->payment_methods;

        if (! $paymentMethods && ($settings?->payment_method_name || $settings?->payment_details)) {
            $paymentMethods = [[
                'name' => $settings->payment_method_name ?? '',
                'details' => $settings->payment_details ?? '',
            ]];
        }

        return [
            'business_name' => $settings?->business_name,
            'business_logo_path' => $settings?->logo_path,
            'business_street_address' => $settings?->street_address,
            'business_city' => $settings?->city,
            'business_region' => $settings?->region,
            'business_postal_code' => $settings?->postal_code,
            'business_phone' => $settings?->phone,
            'business_email' => $settings?->email,
            'payment_terms' => $settings?->payment_terms ?? 'Due within 30 days',
            'payment_method_name' => $settings?->payment_method_name,
            'payment_details' => $settings?->payment_details,
            'payment_methods' => $paymentMethods,
            'invoice_footer' => $settings?->invoice_footer,
        ];
    }

    /** @param array{pricingType: string, hourlyRate?: mixed, hours?: mixed, amount?: mixed} $item */
    public function itemTotal(array $item): float
    {
        if (($item['pricingType'] ?? '') === PricingType::Hourly->value) {
            return round((float) ($item['hourlyRate'] ?? 0) * (float) ($item['hours'] ?? 0), 2);
        }

        return round((float) ($item['amount'] ?? 0), 2);
    }

    private function nextInvoiceNumber(): string
    {
        $highestNumber = Invoice::query()->pluck('number')
            ->filter(fn (string $number): bool => ctype_digit($number))
            ->map(fn (string $number): int => (int) $number)
            ->max() ?? 0;

        return str_pad((string) ($highestNumber + 1), 3, '0', STR_PAD_LEFT);
    }
}; ?>

<div class="mx-auto w-full max-w-7xl py-6 sm:py-10">
    <header class="flex flex-col gap-5 pb-8 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-xs font-semibold tracking-[0.2em] text-zinc-500">{{ $invoice ? 'Edit Invoice' : 'New Invoice' }}</p>
            <h1 class="text-4xl font-medium tracking-[-0.04em] sm:text-5xl">Invoice #{{ $number }}</h1>
        </div>
        <a href="{{ $invoice ? route('invoices.show', $invoice) : route('invoices.index') }}" wire:navigate class="text-sm font-medium underline decoration-zinc-400 underline-offset-4">Cancel</a>
    </header>

    @if ($this->clients->isEmpty())
        <div class="border-breeze bg-cloud dark:border-slate dark:bg-slate mt-8 border p-6">
            <p class="font-medium">Add a Client Before Creating an Invoice.</p>
            <flux:button :href="route('clients.index')" variant="primary" class="mt-4" wire:navigate>Go to clients</flux:button>
        </div>
    @endif

    <form wire:submit="save" class="pt-10">
        <section class="grid gap-6 lg:grid-cols-5">
            <flux:select wire:model="clientId" label="Client" required>
                <flux:select.option value="">Choose a client</flux:select.option>
                @foreach ($this->clients as $client)
                    <flux:select.option wire:key="client-option-{{ $client->id }}" :value="$client->id">{{ $client->company_name }}{{ $client->trashed() ? ' (removed)' : '' }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="number" label="Invoice number" required />
            <flux:input wire:model="invoiceDate" label="Invoice date" type="date" required />
            <flux:input wire:model="dueDate" label="Due date" type="date" required />
            <flux:select wire:model="status" label="Status" required>
                @foreach (InvoiceStatus::cases() as $invoiceStatus)
                    <flux:select.option :value="$invoiceStatus->value">{{ $invoiceStatus->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </section>

        <section class="mt-10">
            <div class="border-b-2 border-zinc-950 pb-4 dark:border-white">
                <p class="text-xs font-semibold tracking-[0.2em] text-zinc-500">Services</p>
                <h2 class="mt-2 text-2xl font-medium tracking-tight">Line Items</h2>
            </div>

            <div class="divide-y divide-zinc-300 dark:divide-zinc-700">
                @foreach ($items as $index => $item)
                    <article wire:key="item-{{ $item['key'] }}" class="grid gap-4 py-5 xl:grid-cols-[minmax(16rem,2fr)_10rem_minmax(18rem,1.5fr)_8rem_auto] xl:items-start">
                        <div class="grid gap-3">
                            <flux:input wire:model="items.{{ $index }}.description" label="Description" required />
                            <flux:textarea wire:model="items.{{ $index }}.details" label="Details (optional)" rows="2" />
                        </div>
                        <flux:select wire:model.live="items.{{ $index }}.pricingType" label="Pricing type">
                            @foreach (PricingType::cases() as $pricingType)
                                <flux:select.option :value="$pricingType->value">{{ $pricingType->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @if ($item['pricingType'] === PricingType::Hourly->value)
                            <div class="grid grid-cols-2 gap-4">
                                <flux:input wire:model.live.debounce.250ms="items.{{ $index }}.hourlyRate" label="Hourly rate" type="number" min="0" step="1" prefix="$" />
                                <flux:input wire:model.live.debounce.250ms="items.{{ $index }}.hours" label="Hours" type="number" min="0" step="0.25" />
                            </div>
                        @else
                            <flux:input wire:model.live.debounce.250ms="items.{{ $index }}.amount" label="Fixed amount" type="number" min="0" step="0.01" prefix="$" />
                        @endif
                        <div>
                            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Amount</p>
                            <p class="mt-2 text-lg font-semibold tabular-nums">${{ number_format($this->itemTotal($item), 2) }}</p>
                        </div>
                        <flux:button type="button" wire:click="removeItem({{ $index }})" variant="ghost" icon="trash" aria-label="Remove item" :disabled="count($items) === 1" />
                    </article>
                @endforeach
            </div>

            <div class="flex justify-end py-4">
                <flux:button type="button" wire:click="addItem" variant="ghost" icon="plus">Add Item</flux:button>
            </div>
        </section>

        <section class="flex justify-end border-t-2 border-zinc-950 py-8 dark:border-white">
            <div class="flex w-full max-w-md items-baseline justify-between">
                <span class="text-sm font-semibold tracking-[0.16em] text-zinc-500">Invoice Total</span>
                <span class="text-4xl font-medium tracking-[-0.04em] tabular-nums">${{ number_format($this->total, 2) }}</span>
            </div>
        </section>

        <footer class="flex flex-col-reverse gap-3 border-t border-zinc-300 pt-6 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-700">
            <a href="{{ $invoice ? route('invoices.show', $invoice) : route('invoices.index') }}" wire:navigate class="text-sm font-medium">Cancel</a>
            <div class="flex flex-col gap-3 sm:flex-row">
                <flux:button type="submit" variant="outline">Save</flux:button>
                <flux:button type="button" wire:click="save(true)" variant="primary">Save &amp; preview</flux:button>
            </div>
        </footer>
    </form>
</div>
