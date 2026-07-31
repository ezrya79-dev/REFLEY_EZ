<div {{ $attributes->class(['table-wrap']) }}>
    <table class="table">
        @isset($head)
            <thead>{{ $head }}</thead>
        @endisset
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
