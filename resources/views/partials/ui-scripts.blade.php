<script>
    // Show/Hide toggles: each button targets the input in data-target.
    document.querySelectorAll('.toggle-password').forEach((toggle) => {
        const input = document.getElementById(toggle.dataset.target);
        if (! input) return;

        toggle.addEventListener('click', () => {
            const hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            toggle.classList.toggle('is-visible', hidden);
            toggle.setAttribute('aria-pressed', hidden ? 'true' : 'false');
            toggle.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
        });
    });

    // Loading state: disable the submit button once the form is submitted.
    document.querySelectorAll('form[data-loading]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            if (! button) return;

            button.disabled = true;
            button.classList.add('is-loading');
            button.textContent = button.dataset.loadingText || 'Please wait…';
        });
    });
</script>
