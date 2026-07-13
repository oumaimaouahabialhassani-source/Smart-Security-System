@props(['title' => 'Delete', 'message' => 'Are you sure?'])

{{-- Opened by any .js-delete button carrying data-action + data-name. --}}
<div class="modal-backdrop" id="delete-modal" hidden>
    <div class="modal" role="alertdialog" aria-modal="true" aria-labelledby="delete-modal-title">
        <h3 class="modal-title" id="delete-modal-title">{{ $title }}</h3>
        <p class="modal-text">{{ $message }}</p>
        <p class="modal-target" id="delete-modal-name"></p>
        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" data-close-modal>Cancel</button>
            <form method="POST" id="delete-modal-form">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('delete-modal');
            const form = document.getElementById('delete-modal-form');
            const nameEl = document.getElementById('delete-modal-name');

            let opener = null;

            document.querySelectorAll('.js-delete').forEach((btn) => {
                btn.addEventListener('click', () => {
                    form.action = btn.dataset.action;
                    nameEl.textContent = btn.dataset.name;
                    modal.hidden = false;
                    opener = btn;
                    // Move keyboard focus into the dialog; restored on close.
                    modal.querySelector('[data-close-modal]').focus();
                });
            });

            const close = () => {
                modal.hidden = true;
                opener?.focus();
                opener = null;
            };

            modal.addEventListener('click', (e) => {
                if (e.target === modal || e.target.closest('[data-close-modal]')) close();
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.hidden) close();
            });
        })();
    </script>
    @endpush
@endonce
