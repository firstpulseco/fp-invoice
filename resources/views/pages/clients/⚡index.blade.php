<?php

use App\Models\Client;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Clients')] class extends Component {
    use WithPagination;

    public string $search = '';
    public ?int $editingClientId = null;
    public string $companyName = '';
    public string $contactName = '';
    public string $email = '';
    public string $phone = '';
    public string $streetAddress = '';
    public string $addressLine2 = '';
    public string $city = '';
    public string $region = '';
    public string $postalCode = '';

    #[Computed]
    public function clients(): LengthAwarePaginator
    {
        return Client::query()
            ->when($this->search, fn ($query) => $query->where(function ($query): void {
                $query->where('company_name', 'like', "%{$this->search}%")
                    ->orWhere('contact_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->withCount('invoices')
            ->orderBy('company_name')
            ->paginate(12);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(Client $client): void
    {
        $this->editingClientId = $client->id;
        $this->companyName = $client->company_name;
        $this->contactName = $client->contact_name ?? '';
        $this->email = $client->email ?? '';
        $this->phone = $client->phone ?? '';
        $this->streetAddress = $client->street_address ?? '';
        $this->addressLine2 = $client->address_line_2 ?? '';
        $this->city = $client->city ?? '';
        $this->region = $client->region ?? '';
        $this->postalCode = $client->postal_code ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'companyName' => ['required', 'string', 'max:255'],
            'contactName' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'streetAddress' => ['nullable', 'string', 'max:255'],
            'addressLine2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'postalCode' => ['nullable', 'string', 'max:255'],
        ]);

        Client::query()->updateOrCreate(
            ['id' => $this->editingClientId],
            [
                'company_name' => $validated['companyName'],
                'contact_name' => $validated['contactName'] ?: null,
                'email' => $validated['email'] ?: null,
                'phone' => $validated['phone'] ?: null,
                'street_address' => $validated['streetAddress'] ?: null,
                'address_line_2' => $validated['addressLine2'] ?: null,
                'city' => $validated['city'] ?: null,
                'region' => $validated['region'] ?: null,
                'postal_code' => $validated['postalCode'] ?: null,
            ],
        );

        $this->resetForm();
        unset($this->clients);
        Flux::toast(variant: 'success', text: 'Client saved.');
    }

    public function delete(Client $client): void
    {
        $client->delete();

        if ($this->editingClientId === $client->id) {
            $this->resetForm();
        }

        unset($this->clients);
        Flux::toast(variant: 'success', text: 'Client removed. Existing invoices are unchanged.');
    }

    public function resetForm(): void
    {
        $this->reset(['editingClientId', 'companyName', 'contactName', 'email', 'phone', 'streetAddress', 'addressLine2', 'city', 'region', 'postalCode']);
        $this->resetValidation();
    }
}; ?>

<div class="mx-auto w-full max-w-7xl py-6 sm:py-10">
    <header class="flex flex-col gap-6 pb-8 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">DIRECTORY</p>
            <h1 class="text-4xl font-medium tracking-[-0.04em] sm:text-5xl">Clients</h1>
        </div>
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search clients" class="w-full sm:w-72" />
    </header>

    <div class="grid gap-10 pt-8 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <section>
            @if ($this->clients->isEmpty())
                <div class="border-t border-zinc-300 py-16 dark:border-zinc-700">
                    <p class="text-xl tracking-tight">{{ $search ? 'No Clients Match Your Search.' : 'No Clients Yet.' }}</p>
                    <p class="mt-2 text-sm text-zinc-500">Add a client to begin creating invoices.</p>
                </div>
            @else
                <div class="divide-y divide-zinc-300 border-y border-zinc-300 dark:divide-zinc-700 dark:border-zinc-700">
                    @foreach ($this->clients as $client)
                        <article wire:key="client-{{ $client->id }}" class="grid gap-4 py-5 sm:grid-cols-[1fr_1fr_auto] sm:items-center">
                            <div>
                                <h2 class="font-medium tracking-tight">{{ $client->company_name }}</h2>
                                <p class="mt-1 text-sm text-zinc-500">{{ $client->contact_name ?: 'No contact person' }}</p>
                            </div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                <p>{{ $client->email ?: 'No email' }}</p>
                                <p class="mt-1">{{ $client->city ? $client->city.($client->region ? ', '.$client->region : '') : 'No location' }}</p>
                            </div>
                            <div class="flex gap-1 justify-self-start sm:justify-self-end">
                                <flux:button wire:click="edit({{ $client->id }})" variant="ghost" size="sm">Edit</flux:button>
                                <flux:button wire:click="delete({{ $client->id }})" wire:confirm="Remove this client? Existing invoices will keep their saved billing information." variant="ghost" size="sm">Remove</flux:button>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="mt-6">{{ $this->clients->links() }}</div>
            @endif
        </section>

        <aside class="self-start border-t-2 border-zinc-950 pt-5 dark:border-white lg:sticky lg:top-8">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-xl font-medium tracking-tight">{{ $editingClientId ? 'Edit Client' : 'New Client' }}</h2>
                @if ($editingClientId)
                    <flux:button wire:click="resetForm" variant="ghost" size="sm">Cancel</flux:button>
                @endif
            </div>
            <form wire:submit="save" class="grid gap-5">
                <flux:input wire:model="companyName" label="Company or organization" required />
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                    <flux:input wire:model="contactName" label="Contact person" />
                    <flux:input wire:model="email" label="Email" type="email" />
                    <flux:input wire:model="phone" label="Phone" />
                    <flux:input wire:model="streetAddress" label="Street address" />
                    <flux:input wire:model="addressLine2" label="Address line 2" />
                    <flux:input wire:model="city" label="City" />
                    <flux:input wire:model="region" label="State or region" />
                    <flux:input wire:model="postalCode" label="Postal code" />
                </div>
                <flux:button type="submit" variant="primary" class="mt-2 w-full">{{ $editingClientId ? 'Save changes' : 'Add client' }}</flux:button>
            </form>
        </aside>
    </div>
</div>
