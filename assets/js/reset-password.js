document.addEventListener('DOMContentLoaded', () => {
    // Supabase sends the access token in the URL fragment (#)
    const hash = window.location.hash.substring(1); // Remove the '#'
    const params = new URLSearchParams(hash);
    const accessToken = params.get('access_token');

    if (accessToken) {
        const tokenInput = document.getElementById('access_token_input');
        if (tokenInput) {
            tokenInput.value = accessToken;
        }
    } else {
        const form = document.querySelector('form[action*="reset_password"]');
        if (form) {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.textContent = 'الرابط غير صالح أو منتهي الصلاحية';
            }

            let messageDiv = document.querySelector('.message');
            if (!messageDiv) {
                messageDiv = document.createElement('div');
                messageDiv.className = 'message error';
                messageDiv.textContent = 'رابط إعادة تعيين كلمة المرور غير صالح أو مفقود.';
                const formCard = form.closest('.form-card');
                if (formCard) {
                    formCard.insertBefore(messageDiv, form);
                }
            }
        }
    }
});
