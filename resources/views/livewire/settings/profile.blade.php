<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name, email, and fitness profile')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <flux:input wire:model="username" :label="__('Username')" type="text" autocomplete="username" placeholder="Optional" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <flux:separator />

            <flux:heading size="sm">Fitness Profile</flux:heading>
            <flux:text class="text-sm text-zinc-500 -mt-4">Used to calculate personalised macro targets on the Nutrition and Dashboard pages.</flux:text>

            <flux:field>
                <flux:label>Fitness Goal</flux:label>
                <flux:select wire:model="fitnessGoal" placeholder="Select a goal...">
                    <flux:select.option value="">— Not set —</flux:select.option>
                    <flux:select.option value="Bulking">Bulking (+10% calories)</flux:select.option>
                    <flux:select.option value="Maintaining">Maintaining</flux:select.option>
                    <flux:select.option value="Cutting">Cutting (-10% calories)</flux:select.option>
                </flux:select>
                <flux:error name="fitnessGoal" />
            </flux:field>

            <flux:field>
                <flux:label>Body Weight (lbs)</flux:label>
                <flux:input wire:model="bodyWeightLbs" type="number" min="50" max="1000" step="0.1" placeholder="e.g. 185" />
                <flux:description>Used with your fitness goal to calculate protein, carb, and fat targets.</flux:description>
                <flux:error name="bodyWeightLbs" />
            </flux:field>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>
