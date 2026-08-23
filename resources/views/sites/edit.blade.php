<x-app-layout>
    <x-slot name="title">Settings · {{ $site->name }}</x-slot>

    <x-page width="max-w-xl">
        <div>
            <a href="{{ route('sites.show', $site) }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900">
                <i class="bx bx-left-arrow-alt text-base"></i>
                {{ $site->name }}
            </a>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-zinc-900">Site settings</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ $site->domain }}</p>
        </div>

        <x-card>
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
                    <x-timezone-select id="timezone" name="timezone" :selected="old('timezone', $site->timezone)" class="mt-1" />
                    <x-input-error :messages="$errors->get('timezone')" class="mt-2" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Save</x-primary-button>
                </div>
            </form>
        </x-card>

        <x-card class="ring-red-100">
            <h2 class="text-sm font-semibold text-red-700">Danger zone</h2>
            <p class="mt-1 text-sm text-zinc-500">Deletes this site and every event, rollup, and breakdown attached to it.</p>
            <form method="POST" action="{{ route('sites.destroy', $site) }}" class="mt-4" onsubmit="return confirm('Delete this site and all of its analytics data? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <x-danger-button>Delete site</x-danger-button>
            </form>
        </x-card>
    </x-page>
</x-app-layout>
