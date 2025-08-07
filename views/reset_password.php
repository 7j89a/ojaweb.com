<!-- واجهة المستخدم -->
<div class="form-container">
    <div class="form-card interactive-card reveal-on-scroll">
        <div class="form-header">
            <div class="form-icon">🔒</div>
            <h2 class="form-title">تعيين كلمة مرور جديدة</h2>
            <p class="form-subtitle">أدخل كلمة المرور الجديدة أدناه.</p>
        </div>
        <?php if (!empty($message)): ?>
            <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST" action="?action=reset_password">
            <input type="hidden" name="access_token" id="access_token_input" value="">
            <div class="form-group">
                <label class="form-label">كلمة المرور الجديدة:</label>
                <input type="password" name="password" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">تأكيد كلمة المرور:</label>
                <input type="password" name="password_confirm" class="form-input" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">تغيير كلمة المرور</button>
        </form>
    </div>
</div>

<script src="assets/js/reset-password.js"></script>
