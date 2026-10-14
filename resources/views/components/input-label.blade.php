@props(['value'])


<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-m-gold2 mb-1.5']) }}>
    {{ $value ?? $slot }}
</label>
