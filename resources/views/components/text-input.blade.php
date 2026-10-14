@props(['disabled' => false])


<input @disabled($disabled) {{ $attributes->merge([
    'class' => 'w-full rounded-lg px-4 py-2.5 text-sm
               bg-m-tx/5 border border-m-gold/25
               text-m-tx placeholder-m-tx/30
               focus:outline-none focus:border-m-gold focus:ring-2 focus:ring-m-gold/20
               transition-colors duration-200'
]) }}>
