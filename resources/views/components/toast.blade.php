@if (session('status'))
    <div class="toast-stack" x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 4000)">
        <div class="toast toast-success" x-show="visible" x-transition role="status">
            {{ session('status') }}
        </div>
    </div>
@endif
