document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.ver-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);

            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = 'Ocultar';
            } else {
                input.type = 'password';
                btn.textContent = 'Ver';
            }
        });
    });
});
