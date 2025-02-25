@extends('layouts.admin')

@section('title', 'مدیریت زمانبندی')

@section('content')
    <div class="p-6">
        <div id="admin-schedule"></div>
    </div>
@endsection

@push('scripts')
    <script>
        window.initialData = {
            routes: {
                specialists: '{{ route('api.specialists.index') }}',
                schedules: '{{ route('api.schedules.index') }}',
                holidays: '{{ route('api.holidays.index') }}',
                leaves: '{{ route('api.leaves.index') }}'
            }
        };
    </script>
@endpush
