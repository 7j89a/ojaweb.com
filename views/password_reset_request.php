<div class="form-container">
    <div class="form-card interactive-card reveal-on-scroll">
        <div class="form-header">
            <div class="form-icon">🔑</div>
            <h2 class="form-title">إعادة تعيين كلمة المرور</h2>
            <p class="form-subtitle">أدخل بريدك الإلكتروني لإرسال رابط إعادة التعيين.</p>
        </div>
        <?php if (!empty($message)): ?>
            <div class="message <?= $message_type ?>"><?= $message ?></div>
        <?php endif; ?>
        <form method="POST" action="?action=request_password_reset">
            <div class="form-group">
                <label class="form-label">البريد الإلكتروني:</label>
                <input type="email" name="email" class="form-input" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">إرسال رابط إعادة التعيين</button>
        </form>
    </div>
</div>
