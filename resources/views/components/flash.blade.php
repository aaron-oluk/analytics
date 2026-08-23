@if (session('status'))
    <div class="flex items-start gap-3 rounded-xl bg-teal-50 px-4 py-3 text-sm text-teal-800 ring-1 ring-teal-100">
        <i class="bx bx-check-circle mt-0.5 text-lg"></i>
        <p>{{ session('status') }}</p>
    </div>
@endif
