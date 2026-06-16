@props(['disabled' => false])


<input @disabled($disabled) {{ $attributes->merge([
    'class' => 'w-full rounded-lg px-4 py-2.5 text-sm
               bg-white/5 border border-[#C9A24B]/25
               text-[#F8F3E9] placeholder-[#F8F3E9]/30
               focus:outline-none focus:border-[#C9A24B] focus:ring-2 focus:ring-[#C9A24B]/20
               transition-colors duration-200'
]) }}>
