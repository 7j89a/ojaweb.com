<?php 
// **THE FIX IS HERE**: Check for the correct session variable structure.
if (isset($_SESSION['user']) && isset($_SESSION['user']['phone'])): 
    $quiz_history = get_user_quiz_history($_SESSION['user']['phone']);
    $user_stats = get_user_stats($quiz_history);
?>
<div class="profile-container">
    <div class="profile-card interactive-card reveal-on-scroll">
        <div class="profile-avatar">👤</div>
        <h2 class="profile-name"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></h2>
    </div>

    <div class="profile-stats reveal-on-scroll">
        <div class="profile-stat-item"><div class="stat-value" data-target="<?= $user_stats['total_quizzes'] ?>">0</div><div class="stat-title">اختبارات مكتملة</div></div>
        <div class="profile-stat-item"><div class="stat-value" data-target="<?= round($user_stats['average_score']) ?>">0</div><div class="stat-title">متوسط النتيجة %</div></div>
    </div>

    <div class="activity-history reveal-on-scroll">
        <h3>سجل الاختبارات</h3>
        <?php if (empty($quiz_history)): ?>
            <div class="empty-state"><div class="empty-state-icon">📜</div><p class="empty-state-text">لم تقم بأي اختبار بعد. ابدأ الآن!</p></div>
        <?php else: ?>
            <div class="table-responsive" style="overflow-x: auto;">
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>الاختبار</th>
                            <th>النموذج</th>
                            <th>النتيجة</th>
                            <th>النسبة</th>
                            <th>التاريخ</th>
                            <th>الوقت</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($quiz_history as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['courses']['title'] ?? 'اختبار غير معروف') ?></td>
                            <td><?= htmlspecialchars($item['quiz_models']['title'] ?? 'نموذج غير معروف') ?></td>
                            <td><?= $item['score'] ?> / <?= $item['total_questions'] ?></td>
                            <td><span class="badge <?= $item['percentage'] >= 50 ? 'badge-success' : 'badge-danger' ?>"><?= $item['percentage'] ?>%</span></td>
                            <td><?= date('Y-m-d', strtotime($item['completed_at'])) ?></td>
                            <td><?= date('h:i A', strtotime($item['completed_at'])) ?></td>
                            <td style="display: flex; gap: 0.5rem;">
                                <a href="?view=quiz_review&session_id=<?= $item['session_id'] ?>" class="btn btn-secondary btn-sm">مراجعة</a>
                                <form action="?view=profile" method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_quiz_history">
                                    <input type="hidden" name="session_id" value="<?= htmlspecialchars($item['session_id']) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('هل تريد حذف تقدم للاختبار هذا اللذي قدمته بتاريخ <?= date('Y-m-d', strtotime($item['completed_at'])) ?>؟')">حذف</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php else: 
    header("Location: ?view=login");
    exit();
endif; ?>
