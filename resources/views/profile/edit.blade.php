<x-app-layout>
    <x-slot name="title">{{ __('Profile') }}</x-slot>

    <x-page width="max-w-3xl">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">{{ __('Profile') }}</h1>
            <p class="mt-1 text-sm text-zinc-500">Account details for your dashboard login.</p>
        </div>

        <x-card>
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </x-card>

        <x-card>
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </x-card>

        <x-card>
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </x-card>
    </x-page>
</x-app-layout>
