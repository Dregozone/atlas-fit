<div class="flex items-start gap-8 max-md:flex-col">
    <div class="w-full md:w-[220px] md:shrink-0">
        <flux:navlist aria-label="{{ __('Settings') }}" class="max-md:flex max-md:flex-row max-md:gap-1 max-md:overflow-x-auto max-md:pb-1">
            <flux:navlist.item icon="user-circle" :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item icon="shield-check" :href="route('security.edit')" :current="request()->routeIs('security.edit')" wire:navigate>{{ __('Security') }}</flux:navlist.item>
            <flux:navlist.item icon="swatch" :href="route('appearance.edit')" :current="request()->routeIs('appearance.edit')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
            <flux:navlist.item icon="code-bracket" :href="route('settings.api')" :current="request()->routeIs('settings.api')" wire:navigate>{{ __('API') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:card class="w-full flex-1 !rounded-2xl">
        <flux:heading size="lg" level="2">{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </flux:card>
</div>
