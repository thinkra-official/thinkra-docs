@if(session('success'))
    <div class="mb-5 rounded-xl border border-emerald-200/80 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm shadow-sm">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-5 rounded-xl border border-red-200/80 bg-red-50 px-4 py-3 text-red-800 text-sm shadow-sm">
        <ul class="list-disc pr-5 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
