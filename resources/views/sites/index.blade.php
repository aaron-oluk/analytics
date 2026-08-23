<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Your Sites</h2>
            <a href="{{ route('sites.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                + Add Site
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-md text-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg divide-y">
                @forelse ($sites as $site)
                    <a href="{{ route('sites.show', $site) }}" class="block p-6 hover:bg-gray-50">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-semibold text-gray-900">{{ $site->name }}</div>
                                <div class="text-sm text-gray-500">{{ $site->domain }}</div>
                            </div>
                            <span class="text-gray-400">&rarr;</span>
                        </div>
                    </a>
                @empty
                    <div class="p-6 text-gray-500">
                        No sites yet. <a href="{{ route('sites.create') }}" class="text-gray-900 underline">Add your first site</a> to get a tracking snippet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
