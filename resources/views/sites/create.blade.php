<x-app-layout>
    <x-slot name="title">Add a site</x-slot>

    <x-page width="max-w-xl">
        <div>
            <a href="{{ route('sites.index') }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900">
                <i class="bx bx-left-arrow-alt text-base"></i>
                Sites
            </a>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-zinc-900">Add a site</h1>
            <p class="mt-1 text-sm text-zinc-500">You’ll get a snippet to paste on the site after saving.</p>
        </div>

        <x-card>
            <form method="POST" action="{{ route('sites.store') }}" class="space-y-6">
                @csrf

                <div>
                    <x-input-label for="name" value="Site name" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="domain" value="Domain" />
                    <x-text-input id="domain" name="domain" type="text" class="mt-1 block w-full" value="{{ old('domain') }}" placeholder="example.com" required />
                    <p class="mt-1.5 text-xs text-zinc-400">No https:// — just the host, e.g. example.com</p>
                    <x-input-error :messages="$errors->get('domain')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="timezone" value="Timezone" />
                    <x-timezone-select id="timezone" name="timezone" :selected="old('timezone', 'UTC')" class="mt-1" />
                    <x-input-error :messages="$errors->get('timezone')" class="mt-2" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Add site</x-primary-button>
                </div>
            </form>
        </x-card>
    </x-page>
</x-app-layout>
