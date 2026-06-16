@props(['value'])


<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-[#E6CD8A] mb-1.5']) }}>
    {{ $value ?? $slot }}
</label>
