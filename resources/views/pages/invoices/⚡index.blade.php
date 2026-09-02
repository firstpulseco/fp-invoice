<?php

use App\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Invoices')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';

    #[Computed]
    public function invoices(): LengthAwarePaginator
    {
        return Invoice::query()
            ->when($this->search, fn ($query) => $query->where(function ($query): void {
                $query->where('number', 'like', "%{$this->search}%")
                    ->orWhere('client_company_name', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(15);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="mx-auto w-full max-w-7xl py-6 sm:py-10">
    <header class="flex flex-col gap-7 pb-8 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">OVERVIEW</p>
            <h1 class="text-4xl font-medium tracking-[-0.04em] sm:text-5xl">Invoices</h1>
        </div>
        <flux:button :href="route('invoices.create')" variant="primary" icon="plus" wire:navigate>New invoice</flux:button>
    </header>

    <div class="flex flex-col gap-4 py-6 sm:flex-row sm:items-center sm:justify-between">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Client or invoice number" class="w-full sm:max-w-sm" />
        <flux:select wire:model.live="status" class="w-full sm:w-44">
            <flux:select.option value="">All statuses</flux:select.option>
            @foreach (InvoiceStatus::cases() as $invoiceStatus)
                <flux:select.option :value="$invoiceStatus->value">{{ $invoiceStatus->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if ($this->invoices->isEmpty())
        <section class="border-y border-zinc-300 py-20 dark:border-zinc-700">
            <p class="text-2xl font-medium tracking-tight">{{ $search || $status ? 'No invoices match those filters.' : 'Your first invoice starts here.' }}</p>
            <p class="mt-3 max-w-lg text-zinc-500">Create a clear, polished invoice and let the application handle the calculations.</p>
            @unless ($search || $status)
                <flux:button :href="route('invoices.create')" variant="outline" class="mt-7" wire:navigate>Create an invoice</flux:button>
            @endunless
        </section>
    @else
        <div class="overflow-x-auto border-y border-zinc-300 dark:border-zinc-700">
            <table class="w-full min-w-[760px] border-collapse text-left">
                <thead>
                    <tr class="text-xs font-semibold tracking-[0.14em] text-zinc-500">
                        <th class="py-4 pr-5">Invoice</th>
                        <th class="px-5 py-4">Client</th>
                        <th class="px-5 py-4">Issued</th>
                        <th class="px-5 py-4">Due</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="py-4 pl-5 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-300 dark:divide-zinc-700">
                    @foreach ($this->invoices as $invoice)
                        <tr wire:key="invoice-{{ $invoice->id }}" class="hover:bg-ice/60 dark:hover:bg-slate group transition-colors">
                            <td class="py-5 pr-5">
                                <a href="{{ route('invoices.show', $invoice) }}" wire:navigate class="font-semibold tracking-tight underline decoration-transparent underline-offset-4 transition group-hover:decoration-current">#{{ $invoice->number }}</a>
                            </td>
                            <td class="px-5 py-5 font-medium">{{ $invoice->client_company_name }}</td>
                            <td class="px-5 py-5 text-sm text-zinc-600 dark:text-zinc-400">{{ $invoice->invoice_date->format('M j, Y') }}</td>
                            <td class="px-5 py-5 text-sm text-zinc-600 dark:text-zinc-400">{{ $invoice->due_date->format('M j, Y') }}</td>
                            <td class="px-5 py-5">
                                <span class="inline-flex items-center gap-2 text-sm">
                                    @php($statusColor = match ($invoice->status) {
                                        InvoiceStatus::Paid => 'bg-paid',
                                        InvoiceStatus::Sent => 'bg-ocean',
                                        InvoiceStatus::Void => 'bg-void',
                                        default => 'bg-slate',
                                    })
                                    <span class="{{ $statusColor }} size-1.5 rounded-full"></span>{{ $invoice->status->label() }}
                                </span>
                            </td>
                            <td class="py-5 pl-5 text-right font-semibold tabular-nums">${{ number_format((float) $invoice->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $this->invoices->links() }}</div>
    @endif
</div>
