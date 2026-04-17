<button {{ $attributes->merge(['type' => 'submit', 'class' => 'cq-btn-primary']) }}>
    {{ $slot }}
</button>
