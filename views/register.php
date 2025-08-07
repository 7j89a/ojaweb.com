<div class="form-container">
    <div class="form-card interactive-card reveal-on-scroll">
        <div class="form-header"><div class="form-icon">📝</div><h2 class="form-title">إنشاء حساب جديد</h2><p class="form-subtitle">انضم إلينا وابدأ رحلة التعلم.</p></div>
        <?php if (!empty($message)): ?> <div class="message <?= $message_type ?>"><?= $message ?></div> <?php endif; ?>
        <form method="POST" action="?">
            <input type="hidden" name="action" value="register">
            <div class="form-group"><label class="form-label">الاسم الأول:</label><input type="text" name="first_name" class="form-input" required></div>
            <div class="form-group"><label class="form-label">الاسم الثاني (الأب):</label><input type="text" name="middle_name" class="form-input" required></div>
            <div class="form-group"><label class="form-label">اسم العائلة:</label><input type="text" name="last_name" class="form-input" required></div>
            <div class="form-group"><label class="form-label">البريد الإلكتروني:</label><input type="email" name="email" class="form-input" required></div>
            <div class="form-group">
                <label class="form-label" for="phone">رقم الهاتف:</label>
                <input type="tel" id="phone" name="phone" class="form-input" required pattern="^07[789]\d{7}$" title="الرجاء إدخال رقم هاتف أردني صحيح (079/078/077).">
                <div id="phone-error" class="form-error-message" style="display: none; color: var(--color-error); margin-top: 0.5rem; font-size: 0.9rem;"></div>
            </div>
            <div class="form-group"><label class="form-label">كلمة المرور:</label><input type="password" name="password" class="form-input" required minlength="6"></div>
            <div class="form-group"><label class="form-label">تأكيد كلمة المرور:</label><input type="password" name="confirm_password" class="form-input" required></div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">✨ إنشاء الحساب</button>
        </form>
        <div style="text-align: center; margin-top: 2rem;"><a href="?view=login" style="color: var(--color-accent); text-decoration: none; font-weight: 600;">لديك حساب بالفعل؟ سجل دخولك</a></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action="?"]');
    const phoneInput = document.getElementById('phone');
    const phoneError = document.getElementById('phone-error');

    const validatePhone = () => {
        const phoneRegex = /^07[789]\d{7}$/;
        const phoneNumber = phoneInput.value;

        if (phoneNumber && !phoneRegex.test(phoneNumber)) {
            phoneError.textContent = 'ادخل رقم هاتف فعال وصحيح';
            phoneError.style.display = 'block';
            phoneInput.classList.add('is-invalid');
            return false;
        } else {
            phoneError.style.display = 'none';
            phoneInput.classList.remove('is-invalid');
            return true;
        }
    };

    phoneInput.addEventListener('input', validatePhone);

    form.addEventListener('submit', function(event) {
        if (!validatePhone()) {
            event.preventDefault(); // Prevent form submission if phone is invalid
            phoneInput.focus();
        }
    });
});
</script>

<style>
.form-input.is-invalid {
    border-color: var(--color-error);
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.25);
}
</style>
