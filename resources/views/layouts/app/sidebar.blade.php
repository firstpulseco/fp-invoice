<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="text-ink dark:text-cloud min-h-screen bg-white dark:bg-black">
        <flux:sidebar sticky collapsible="mobile" class="border-breeze bg-ice dark:border-slate dark:bg-slate border-e">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('invoices.index') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group class="grid gap-1">
                    <flux:sidebar.item icon="document-text" :href="route('invoices.index')" :current="request()->routeIs('invoices.*')" wire:navigate>
                        {{ __('Invoices') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('clients.index')" :current="request()->routeIs('clients.*')" wire:navigate>
                        {{ __('Clients') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('business.edit')" :current="request()->routeIs('business.*')" wire:navigate>
                        {{ __('Settings') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <div class="hidden px-2 pb-3 lg:block">
                <p class="sr-only" id="sidebar-appearance-label">{{ __('Appearance') }}</p>
                <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" aria-labelledby="sidebar-appearance-label">
                    <flux:radio value="light" icon="sun" aria-label="{{ __('Light mode') }}" />
                    <flux:radio value="dark" icon="moon" aria-label="{{ __('Dark mode') }}" />
                    <flux:radio value="system" icon="computer-desktop" aria-label="{{ __('System appearance') }}" />
                </flux:radio.group>
            </div>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="border-breeze bg-white dark:border-slate dark:bg-black lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:button
                x-data
                x-on:click="$flux.appearance = $flux.appearance === 'dark' ? 'light' : 'dark'"
                variant="ghost"
                icon="moon"
                aria-label="{{ __('Toggle dark mode') }}"
            />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
