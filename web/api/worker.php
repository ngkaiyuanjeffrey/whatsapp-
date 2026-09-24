<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

function jsonResponse(bool $success, string $message, mixed $data = null, int $status = 200): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data], JSON_THROW_ON_ERROR);
    exit;
}

$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!preg_match('/^Bearer ([A-Za-z0-9._-]+)$/', $authorization, $matches)) {
    jsonResponse(false, 'Unauthorized', null, 401);
}

$tokenHash = hash('sha256', $matches[1]);
$stmt = $pdo->prepare('SELECT * FROM workers WHERE token_hash = :token_hash AND is_active = 1 LIMIT 1');
$stmt->execute(['token_hash' => $tokenHash]);
$worker = $stmt->fetch();
if (!$worker) {
    jsonResponse(false, 'Unauthorized', null, 401);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $input['action'] ?? '';

if ($action === 'heartbeat') {
    $status = in_array($input['whatsapp_status'] ?? 'unknown', ['unknown', 'qr_required', 'connected', 'disconnected'], true)
        ? $input['whatsapp_status'] : 'unknown';
    $stmt = $pdo->prepare('UPDATE workers SET last_heartbeat_at = UTC_TIMESTAMP(), whatsapp_status = :status, worker_version = :version WHERE id = :id');
    $stmt->execute(['status' => $status, 'version' => $input['worker_version'] ?? null, 'id' => $worker['id']]);
    jsonResponse(true, 'Heartbeat accepted');
}

if ($action === 'claim') {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('
        SELECT j.id, j.rendered_message, c.phone, c.name AS contact_name
        FROM message_jobs j
        JOIN contacts c ON j.contact_id = c.id
        WHERE j.status = "pending"
          AND (j.scheduled_at IS NULL OR j.scheduled_at <= UTC_TIMESTAMP())
          AND (j.next_attempt_at IS NULL OR j.next_attempt_at <= UTC_TIMESTAMP())
        ORDER BY j.id ASC
        LIMIT 1
        FOR UPDATE
    ');
    $stmt->execute();
    $job = $stmt->fetch();

    if (!$job) {
        $pdo->commit();
        jsonResponse(true, 'No pending jobs', null);
    }

    $claimStmt = $pdo->prepare('
        UPDATE message_jobs
        SET status = "processing",
            worker_id = :worker_id,
            claimed_at = UTC_TIMESTAMP(),
            attempts = attempts + 1
        WHERE id = :id
    ');
    $claimStmt->execute([
        'worker_id' => $worker['id'],
        'id' => $job['id'],
    ]);

    $updateWorkerStmt = $pdo->prepare('UPDATE workers SET current_job_id = :job_id WHERE id = :worker_id');
    $updateWorkerStmt->execute(['job_id' => $job['id'], 'worker_id' => $worker['id']]);

    $pdo->commit();

    jsonResponse(true, 'Job claimed', [
        'job_id' => (int) $job['id'],
        'phone' => $job['phone'],
        'contact_name' => $job['contact_name'],
        'message' => $job['rendered_message'],
    ]);
}

if ($action === 'report') {
    $jobId = filter_var($input['job_id'] ?? null, FILTER_VALIDATE_INT);
    $result = $input['result'] ?? '';
    if (!$jobId || !in_array($result, ['sent', 'failed'], true)) {
        jsonResponse(false, 'Invalid report', null, 422);
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT * FROM message_jobs WHERE id = :job_id FOR UPDATE');
    $stmt->execute(['job_id' => $jobId]);
    $job = $stmt->fetch();
    if (!$job || $job['status'] !== 'processing' || (int) $job['worker_id'] !== (int) $worker['id']) {
        $pdo->rollBack();
        jsonResponse(false, 'Job is not owned by this worker or is no longer reportable', null, 409);
    }

    $newStatus = $result === 'sent' ? 'sent' : ((int) $job['attempts'] < (int) $job['max_attempts'] ? 'pending' : 'failed');
    $stmt = $pdo->prepare('UPDATE message_jobs SET status = :status, worker_id = NULL, claimed_at = NULL, completed_at = CASE WHEN :final = 1 THEN UTC_TIMESTAMP() ELSE NULL END, last_error = :error WHERE id = :id AND status = "processing" AND worker_id = :worker_id');
    $stmt->execute(['status' => $newStatus, 'final' => $newStatus === 'sent' || $newStatus === 'failed' ? 1 : 0, 'error' => $result === 'failed' ? (string) ($input['error'] ?? 'Worker reported failure') : null, 'id' => $jobId, 'worker_id' => $worker['id']]);

    $updateWorkerStmt = $pdo->prepare('UPDATE workers SET current_job_id = NULL WHERE id = :worker_id');
    $updateWorkerStmt->execute(['worker_id' => $worker['id']]);

    $pdo->commit();
    jsonResponse(true, 'Result recorded', ['status' => $newStatus]);
}

jsonResponse(false, 'Unknown action', null, 404);
