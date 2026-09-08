<?php
/**
 * Determines whether a student can use the Code IDE.
 *
 * A scheduled practice/exam session takes priority. Once the most recent
 * session has ended, the IDE stays locked for five minutes before reopening
 * in self-practice mode. Self-practice mode never creates a submission.
 */
function getStudentCodeAccess($db, $student_id, $lop = '') {
    $access = [
        'allowed' => false,
        'mode' => 'locked',
        'active_session' => null,
        'last_completed_session' => null,
        'practice_available_at' => null,
    ];

    $student_id = (int)$student_id;
    if ($student_id <= 0) {
        return $access;
    }

    if ($lop === '') {
        $stmt_student = $db->prepare("SELECT lop FROM students WHERE id = ?");
        $stmt_student->bind_param("i", $student_id);
        $stmt_student->execute();
        $student = $stmt_student->get_result()->fetch_assoc();
        $stmt_student->close();
        $lop = $student['lop'] ?? '';
    }

    $now_str = date('Y-m-d H:i:s');
    $stmt_active = $db->prepare("
        SELECT * FROM practice_sessions
        WHERE (lop = ? OR lop = 'ALL')
          AND is_enabled = 1
          AND start_time <= ?
          AND end_time >= ?
        ORDER BY end_time DESC
        LIMIT 1
    ");
    $stmt_active->bind_param("sss", $lop, $now_str, $now_str);
    $stmt_active->execute();
    $access['active_session'] = $stmt_active->get_result()->fetch_assoc();
    $stmt_active->close();

    if ($access['active_session']) {
        $access['allowed'] = true;
        $access['mode'] = 'session';
        return $access;
    }

    // Khi không có giờ thi/thực hành active, sinh viên luôn được phép vào tự luyện tập (self-practice mode)
    $access['allowed'] = true;
    $access['mode'] = 'practice';
    return $access;
}

/**
 * Verify that a student may submit to or download the exam for a practice session.
 * Returns the active session row on success, or null on failure.
 */
function getValidatedActivePracticeSession($db, $student_id, $session_id, $lop = '') {
    $student_id = (int)$student_id;
    $session_id = (int)$session_id;
    if ($student_id <= 0 || $session_id <= 0) {
        return null;
    }

    $access = getStudentCodeAccess($db, $student_id, $lop);
    if ($access['mode'] !== 'session' || !$access['active_session']) {
        return null;
    }

    if ((int)$access['active_session']['id'] !== $session_id) {
        return null;
    }

    return $access['active_session'];
}
