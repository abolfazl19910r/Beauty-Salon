@if (session('success'))
    <div class="mb-4 p-3 rounded-lg text-sm" style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.35); color: #6EE7B7;">{{ session('success') }}</div>
@endif
@if (session('error') || $errors->any())
    <div class="mb-4 p-3 rounded-lg text-sm" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #FCA5A5;">{{ session('error') ?? $errors->first() }}</div>
@endif
