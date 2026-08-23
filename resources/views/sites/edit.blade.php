<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit {{ $site->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('sites.update', $site) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Site name" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $site->name) }}" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="timezone" value="Timezone" />
                        <select id="timezone" name="timezone" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @foreach (timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}" @selected(old('timezone', $site->timezone) === $tz)>{{ $tz }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('timezone')" class="mt-2" />
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>Save</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="font-semibold text-red-700 mb-4">Danger zone</h3>
                <form method="POST" action="{{ route('sites.destroy', $site) }}" onsubmit="return confirm('Delete this site and all of its analytics data? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>Delete Site</x-danger-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
