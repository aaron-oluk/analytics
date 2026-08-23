<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add a Site</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('sites.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Site name" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="domain" value="Domain (no https://, e.g. example.com)" />
                        <x-text-input id="domain" name="domain" type="text" class="mt-1 block w-full" value="{{ old('domain') }}" required />
                        <x-input-error :messages="$errors->get('domain')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="timezone" value="Timezone" />
                        <select id="timezone" name="timezone" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @foreach (timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}" @selected(old('timezone', 'UTC') === $tz)>{{ $tz }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('timezone')" class="mt-2" />
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>Add Site</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
