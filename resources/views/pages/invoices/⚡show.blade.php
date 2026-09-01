<?php

use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\PricingType;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Invoice Preview')] class extends Component {
    public Invoice $invoice;
    public ?string $businessLogoUrl = null;

    /** @var array<int, array{name: string, details: string}> */
    public array $paymentMethods = [];

    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice->load('items');
        $settings = BusinessSetting::query()->first([
            'logo_path',
            'payment_method_name',
            'payment_details',
            'payment_methods',
            'updated_at',
        ]);

        if ($settings?->logo_path && Storage::disk('public')->exists($settings->logo_path)) {
            $this->businessLogoUrl = route('branding.logo', [
                'v' => $settings->updated_at?->getTimestamp(),
            ]);
        }

        $this->paymentMethods = $settings
            ? ($settings->payment_methods ?? [])
            : ($invoice->payment_methods ?? []);
        $paymentSource = $settings ?? $invoice;

        if (! $this->paymentMethods && ($paymentSource->payment_method_name || $paymentSource->payment_details)) {
            $this->paymentMethods = [[
                'name' => $paymentSource->payment_method_name ?? '',
                'details' => $paymentSource->payment_details ?? '',
            ]];
        }
    }
}; ?>

<div class="invoice-preview-shell bg-ice dark:bg-slate -m-6 min-h-screen p-4 sm:-m-8 sm:p-8 lg:-m-10 lg:p-10">
    <div class="invoice-toolbar mx-auto mb-5 flex w-full max-w-[8.5in] flex-wrap items-center justify-between gap-3">
        <a href="{{ route('invoices.index') }}" wire:navigate class="text-sm font-medium underline decoration-zinc-500 underline-offset-4">Back to invoices</a>
        <div class="flex gap-2">
            <flux:button :href="route('invoices.edit', $invoice)" variant="outline" icon="pencil" wire:navigate>Edit</flux:button>
            <flux:button type="button" variant="primary" icon="printer" onclick="window.print()">Print</flux:button>
        </div>
    </div>

    <article class="invoice-sheet text-ink mx-auto min-h-[11in] w-full max-w-[8.5in] bg-white px-[0.55in] py-[0.5in] shadow-xl sm:px-[0.65in]">
        <header class="border-ink grid grid-cols-2 gap-8 border-b-2 pb-7">
            <div class="flex min-h-12 items-start">
                @if ($businessLogoUrl)
                    <img src="{{ $businessLogoUrl }}" alt="{{ $invoice->business_name }}" class="max-h-12 max-w-48 object-contain object-left" />
                @else
                    <p class="text-xl font-semibold">{{ $invoice->business_name ?: 'Your business' }}</p>
                @endif
            </div>
            <div class="pr-1 text-right">
                <p class="text-[0.65rem] font-semibold">Invoice</p>
                <p class="mt-1 text-4xl font-medium tracking-[-0.05em]">#{{ $invoice->number }}</p>
            </div>
        </header>

        <section class="grid grid-cols-2 gap-x-8 gap-y-8 py-8">
            <div>
                <p class="invoice-kicker">Bill To</p>
                <p class="mt-3 text-balance text-lg font-semibold">{{ $invoice->client_company_name }}</p>
                <div class="mt-2 text-sm leading-5 text-zinc-700">
                    @if ($invoice->client_contact_name)<p>{{ $invoice->client_contact_name }}</p>@endif
                    @if ($invoice->client_street_address)<p>{{ $invoice->client_street_address }}</p>@endif
                    @if ($invoice->client_address_line_2)<p>{{ $invoice->client_address_line_2 }}</p>@endif
                    @if ($invoice->client_city || $invoice->client_region || $invoice->client_postal_code)
                        <p>{{ collect([$invoice->client_city, $invoice->client_region])->filter()->join(', ') }} {{ $invoice->client_postal_code }}</p>
                    @endif
                    @if ($invoice->client_email)<p class="mt-1">{{ $invoice->client_email }}</p>@endif
                    @if ($invoice->client_phone)<p>{{ $invoice->client_phone }}</p>@endif
                </div>
            </div>
            <div class="grid grid-cols-2 content-start gap-x-8 gap-y-4">
                <div>
                    <p class="invoice-kicker">Issued</p>
                    <p class="mt-1 text-sm font-medium">{{ $invoice->invoice_date->format('F j, Y') }}</p>
                </div>
                <div>
                    <p class="invoice-kicker">Due</p>
                    <p class="mt-1 text-sm font-medium">{{ $invoice->due_date->format('F j, Y') }}</p>
                </div>
                <div class="col-span-2 border-t border-zinc-300 pt-4">
                    <p class="invoice-kicker">Total Due</p>
                    <p class="mt-1 text-3xl font-medium tracking-[-0.04em] tabular-nums">${{ number_format((float) $invoice->total, 2) }}</p>
                </div>
            </div>
        </section>

        <section>
            <div class="border-ink border-b-2 pb-2">
                <p class="invoice-kicker">Services</p>
            </div>
            <div class="invoice-services">
                <div class="grid grid-cols-[minmax(0,1fr)_5.5rem_4.5rem_6.5rem] gap-4 border-b border-zinc-400 py-2 text-[0.62rem] font-semibold text-zinc-600">
                    <span>Description</span>
                    <span class="text-right">Rate</span>
                    <span class="text-right">Hours</span>
                    <span class="text-right">Amount</span>
                </div>
                @foreach ($invoice->items as $item)
                    <div wire:key="preview-item-{{ $item->id }}" class="invoice-service grid grid-cols-[minmax(0,1fr)_5.5rem_4.5rem_6.5rem] gap-4 border-b border-zinc-300 py-3">
                        <div>
                            <p class="min-w-0 break-words text-balance text-sm font-semibold hyphens-none">{{ $item->description }}</p>
                            @if ($item->details)<p class="mt-0.5 min-w-0 break-words text-balance text-xs leading-4 text-zinc-600 hyphens-none">{{ $item->details }}</p>@endif
                            @if ($item->pricing_type === PricingType::Fixed)<p class="mt-1 text-[0.62rem] font-semibold text-zinc-500">Fixed Price</p>@endif
                        </div>
                        <p class="text-right text-sm tabular-nums">{{ $item->pricing_type === PricingType::Hourly ? '$'.number_format((float) $item->hourly_rate, 2) : '—' }}</p>
                        <p class="text-right text-sm tabular-nums">{{ $item->pricing_type === PricingType::Hourly ? rtrim(rtrim(number_format((float) $item->hours, 2), '0'), '.') : '—' }}</p>
                        <p class="text-right text-sm font-semibold tabular-nums">${{ number_format((float) $item->amount, 2) }}</p>
                    </div>
                @endforeach
            </div>
            <div class="border-ink ml-auto grid w-full max-w-[18rem] grid-cols-2 items-baseline border-b-2 py-4">
                <span class="invoice-kicker">Total</span>
                <span class="text-right text-2xl font-semibold tracking-[-0.04em] tabular-nums">${{ number_format((float) $invoice->total, 2) }}</span>
            </div>
        </section>

        <section class="invoice-payment border-ink mt-8 grid grid-cols-2 gap-8 border-t pt-5">
            <div>
                <p class="invoice-kicker">Payment Methods</p>
                <div class="mt-3 grid gap-3">
                    @forelse ($paymentMethods as $index => $paymentMethod)
                        <div wire:key="invoice-payment-method-{{ $index }}">
                            <p class="text-sm font-semibold">{{ $paymentMethod['name'] }}</p>
                            @if ($paymentMethod['details'])<p class="text-navy mt-1 whitespace-pre-line text-xs leading-4">{{ $paymentMethod['details'] }}</p>@endif
                        </div>
                    @empty
                        <p class="text-navy text-xs leading-5">Contact us for payment instructions.</p>
                    @endforelse
                </div>
            </div>
            <div>
                <p class="invoice-kicker">From</p>
                <p class="mt-3 text-sm font-semibold">{{ $invoice->business_name ?: 'Your business' }}</p>
                <div class="mt-1 text-xs leading-4 text-zinc-700">
                    @if ($invoice->business_street_address)<p>{{ $invoice->business_street_address }}</p>@endif
                    @if ($invoice->business_city || $invoice->business_region || $invoice->business_postal_code)
                        <p>{{ collect([$invoice->business_city, $invoice->business_region])->filter()->join(', ') }} {{ $invoice->business_postal_code }}</p>
                    @endif
                    @if ($invoice->business_email)<p>{{ $invoice->business_email }}</p>@endif
                    @if ($invoice->business_phone)<p>{{ $invoice->business_phone }}</p>@endif
                </div>
            </div>
        </section>

        <footer class="invoice-footer mt-8 flex items-end justify-between gap-8 border-t border-zinc-300 pt-4 text-[0.65rem] leading-4 text-zinc-600">
            <p class="max-w-md">{{ $invoice->invoice_footer }}</p>
            <p class="text-ink shrink-0 text-right font-medium">{{ $invoice->payment_terms }}</p>
        </footer>
    </article>
</div>
