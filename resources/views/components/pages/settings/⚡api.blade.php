<?php

use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Manage API')] class extends Component
{
    public bool $apiIsDisabled = true;

    public string $identifier = '';

    public string $token = '';
    
    public function mount(): void
    {
        // Check if the user already has an identifier and token set, if so they have API access enabled, otherwise they dont
        $user = auth()->user();
        if ($user->api_identifier && $user->api_token) {
            $this->apiIsDisabled = false;
            $this->identifier = $user->api_identifier;
            $this->token = $user->api_token;
        }
    }

    public function enableApiAccess(): void
    {
        // Generate a identifier and token
        $this->identifier = $this->regenerateIdentifier();
        $this->token = $this->regenerateToken();
        
        // Save these against the logged in user model
        $user = auth()->user();
        $user->api_identifier = $this->identifier;
        $user->api_token = $this->token;
        $user->save();

        // Update the view
        $this->apiIsDisabled = false;
    }

    public function disableApiAccess(): void
    {
        // Clear the identifier and token from the logged in user model
        $user = auth()->user();
        $user->api_identifier = null;
        $user->api_token = null;
        $user->save();

        // Update the view
        $this->reset(['apiIsDisabled', 'identifier', 'token']);
    }

    public function regenerateToken(): string
    {
        // Generate a new random token but ensure its unique against all other values in users.api_token
        $newToken = Str::random(28);
        while (User::where('api_token', $newToken)->exists()) {
            $newToken = Str::random(28);
        }

        return $newToken;
    }

    public function regenerateIdentifier(): string
    {
        // Generate a new random identifier but ensure its unique against all other values in users.api_identifier
        $newIdentifier = Str::uuid()->toString();
        while (User::where('api_identifier', $newIdentifier)->exists()) {
            $newIdentifier = Str::uuid()->toString();
        }
        
        return $newIdentifier;
    }
};
?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('API Access')" :subheading=" __('Manage your API access')">

        <div class="space-y-6">
            <div class="flex items-center gap-3 rounded-2xl border p-4 {{ $apiIsDisabled ? 'border-zinc-200 dark:border-white/10' : 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/10' }}">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $apiIsDisabled ? 'bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-400' : 'bg-emerald-500 text-white' }}" aria-hidden="true">
                    <flux:icon :icon="$apiIsDisabled ? 'lock-closed' : 'signal'" variant="solid" class="size-5" />
                </span>
                <div>
                    <p class="font-semibold">{{ $apiIsDisabled ? 'API access is off' : 'API access is on' }}</p>
                    <flux:text class="text-sm">Manage your API identifier and token.</flux:text>
                </div>
            </div>

            <flux:field>
                <flux:label>Identifier</flux:label>
                <flux:input.group>
                    <flux:input wire:model="identifier" placeholder="Identifier" readonly copyable aria-label="API identifier" />

                    <flux:input.group.suffix
                        wire:click="regenerateIdentifier"
                        class="hover:bg-zinc-300/90 dark:hover:bg-zinc-500/90 cursor-pointer"
                        role="button"
                        aria-label="Regenerate identifier"
                    >
                        <flux:icon.arrow-path />
                    </flux:input.group.suffix>
                </flux:input.group>
            </flux:field>

            <flux:field>
                <flux:label>Token</flux:label>
                <flux:input.group>
                    <flux:input wire:model="token" placeholder="Token" readonly copyable aria-label="API token" />

                    <flux:input.group.suffix
                        wire:click="regenerateToken"
                        class="hover:bg-zinc-300/90 dark:hover:bg-zinc-500/90 cursor-pointer"
                        role="button"
                        aria-label="Regenerate token"
                    >
                        <flux:icon.arrow-path />
                    </flux:input.group.suffix>
                </flux:input.group>
            </flux:field>

            <flux:separator variant="subtle" />

            @if ($apiIsDisabled)
                {{-- Enable API Access --}}
                <div>
                    <flux:heading size="lg" level="2">Enable API Access</flux:heading>

                    <flux:text class="mt-1 text-sm">
                        You may enable API access if you wish for your events to be accessible through the API in addition to the web interface.
                    </flux:text>

                    <flux:button
                        variant="primary"
                        icon="signal"
                        class="mt-4"
                        wire:click="enableApiAccess"
                    >
                        Enable API Access
                    </flux:button>
                </div>
            @else
                {{-- Disable API Access --}}
                <div>
                    <flux:heading size="lg" level="2">Disable API Access</flux:heading>

                    <flux:text class="mt-1 text-sm">
                        You may disable API access if you wish for your events to only be accessible through the web interface.
                    </flux:text>

                    <flux:button
                        variant="danger"
                        icon="signal-slash"
                        class="mt-4"
                        wire:click="disableApiAccess"
                    >
                        Disable API Access
                    </flux:button>
                </div>
            @endif
        </div>
    </x-settings.layout>
</section>
