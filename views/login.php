<div class="form-container">
    <div class="form-card interactive-card reveal-on-scroll">
        <div class="form-header"><div class="form-icon">🔐</div><h2 class="form-title">تسجيل الدخول</h2><p class="form-subtitle">مرحباً بعودتك! أدخل بياناتك للمتابعة.</p></div>
        <?php if (!empty($message)): ?> <div class="message <?= $message_type ?>"><?= $message ?></div> <?php endif; ?>
        <form method="POST" action="?">
            <input type="hidden" name="action" value="login">
            <?php if (isset($_GET['redirect_url'])): ?><input type="hidden" name="redirect_url" value="<?= htmlspecialchars($_GET['redirect_url']) ?>"><?php endif; ?>
            <div class="form-group"><label class="form-label">البريد الإلكتروني:</label><input type="email" name="email" class="form-input" required></div>
            <div class="form-group"><label class="form-label">كلمة المرور:</label><input type="password" name="password" class="form-input" required></div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">🚀 تسجيل الدخول</button>
        </form>
        <div style="text-align: center; margin-top: 2rem;"><a href="?view=register" style="color: var(--color-accent); text-decoration: none; font-weight: 600;">ليس لديك حساب؟ سجل الآن</a></div>
        <div style="text-align: center; margin-top: 1rem;"><a href="?view=password_reset_request" style="color: var(--color-secondary); text-decoration: none;">هل نسيت كلمة المرور؟</a></div>
    </div>
</div>
