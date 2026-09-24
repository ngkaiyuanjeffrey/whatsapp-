<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

function adminJson(bool $success, string $message, mixed $data = null, int $status = 200): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data], JSON_THROW_ON_ERROR);
    exit;
}

// 确保默认管理员用户存在（系统自初始化）
try {
    $adminCheck = $pdo->query('SELECT id FROM users LIMIT 1')->fetch();
    if (!$adminCheck) {
        $defaultPasswordHash = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->prepare('INSERT INTO users (username, password_hash, role, is_active) VALUES (?, ?, "admin", 1)')
            ->execute(['admin', $defaultPasswordHash]);
    }
} catch (\Throwable $e) {
    // ignore
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $input['action'] ?? '';

// 1. 获取仪表盘统计数据
if ($action === 'stats') {
    $now = date('Y-m-d H:i:s');
    
    // Workers stats (心跳在 45 秒内视为在线)
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_workers,
            SUM(CASE WHEN last_heartbeat_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 45 SECOND) AND is_active = 1 THEN 1 ELSE 0 END) as online_workers,
            SUM(CASE WHEN whatsapp_status = 'connected' AND last_heartbeat_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 45 SECOND) THEN 1 ELSE 0 END) as connected_workers
        FROM workers
    ");
    $workerStats = $stmt->fetch() ?: ['total_workers' => 0, 'online_workers' => 0, 'connected_workers' => 0];

    // Contacts count
    $contactsCount = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE is_active = 1")->fetchColumn();

    // Campaigns count
    $campaignsCount = (int)$pdo->query("SELECT COUNT(*) FROM campaigns")->fetchColumn();
    $activeCampaigns = (int)$pdo->query("SELECT COUNT(*) FROM campaigns WHERE status IN ('queued', 'running')")->fetchColumn();

    // Jobs stats
    $jobsStmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM message_jobs
        GROUP BY status
    ");
    $jobCounts = [
        'pending' => 0,
        'processing' => 0,
        'sent' => 0,
        'failed' => 0,
        'cancelled' => 0,
        'total' => 0,
    ];
    while ($row = $jobsStmt->fetch()) {
        $jobCounts[$row['status']] = (int)$row['count'];
        $jobCounts['total'] += (int)$row['count'];
    }

    // 最近 10 条任务动态
    $recentJobsStmt = $pdo->query("
        SELECT j.id, j.status, j.rendered_message, j.created_at, j.completed_at, j.last_error,
               c.name as contact_name, c.phone as contact_phone,
               camp.name as campaign_name
        FROM message_jobs j
        JOIN contacts c ON j.contact_id = c.id
        LEFT JOIN campaigns camp ON j.campaign_id = camp.id
        ORDER BY j.id DESC
        LIMIT 10
    ");
    $recentJobs = $recentJobsStmt->fetchAll();

    adminJson(true, 'Stats fetched', [
        'workers' => $workerStats,
        'contacts_count' => $contactsCount,
        'campaigns' => [
            'total' => $campaignsCount,
            'active' => $activeCampaigns,
        ],
        'jobs' => $jobCounts,
        'recent_jobs' => $recentJobs,
        'server_time' => date('Y-m-d H:i:s'),
    ]);
}

// 2. Workers 列表
if ($action === 'workers') {
    $stmt = $pdo->query("
        SELECT 
            id, name, is_active, last_heartbeat_at, whatsapp_status, 
            current_job_id, worker_version, created_at,
            CASE 
                WHEN last_heartbeat_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 45 SECOND) AND is_active = 1 THEN 'online'
                ELSE 'offline'
            END AS connection_state
        FROM workers
        ORDER BY id DESC
    ");
    $workers = $stmt->fetchAll();
    adminJson(true, 'Workers retrieved', $workers);
}

// 3. 创建 Worker 并生成 Token
if ($action === 'create_worker') {
    $name = trim((string)($input['name'] ?? ''));
    if ($name === '') {
        adminJson(false, 'Worker name is required', null, 422);
    }

    // 检查重名
    $check = $pdo->prepare("SELECT id FROM workers WHERE name = ?");
    $check->execute([$name]);
    if ($check->fetch()) {
        adminJson(false, 'Worker name already exists', null, 409);
    }

    // 生成随机 32 字符 Token
    $plainToken = bin2hex(random_bytes(16));
    $tokenHash = hash('sha256', $plainToken);

    $stmt = $pdo->prepare("INSERT INTO workers (name, token_hash, is_active, whatsapp_status) VALUES (?, ?, 1, 'unknown')");
    $stmt->execute([$name, $tokenHash]);
    $workerId = (int)$pdo->lastInsertId();

    adminJson(true, 'Worker created successfully', [
        'id' => $workerId,
        'name' => $name,
        'token' => $plainToken,
        'config_sample' => "[api]\nbase_url = http://localhost/Whatsapp%20Bot%20Contral%20Panel/web/api/worker.php\nworker_token = {$plainToken}\n\n[worker]\npoll_seconds = 5\nversion = 1.0.0",
    ]);
}

// 4. 启停 Worker
if ($action === 'toggle_worker') {
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        adminJson(false, 'Invalid worker ID', null, 422);
    }
    $stmt = $pdo->prepare("UPDATE workers SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    adminJson(true, 'Worker status toggled');
}

// 5. 删除 Worker
if ($action === 'delete_worker') {
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        adminJson(false, 'Invalid worker ID', null, 422);
    }
    $stmt = $pdo->prepare("DELETE FROM workers WHERE id = ?");
    $stmt->execute([$id]);
    adminJson(true, 'Worker deleted');
}

// 6. 联系人列表
if ($action === 'contacts') {
    $search = trim((string)($_GET['q'] ?? ''));
    if ($search !== '') {
        $stmt = $pdo->prepare("
            SELECT id, name, phone, company, is_active, created_at
            FROM contacts
            WHERE name LIKE :q OR phone LIKE :q OR company LIKE :q
            ORDER BY id DESC
            LIMIT 200
        ");
        $stmt->execute(['q' => "%{$search}%"]);
    } else {
        $stmt = $pdo->query("SELECT id, name, phone, company, is_active, created_at FROM contacts ORDER BY id DESC LIMIT 200");
    }
    $contacts = $stmt->fetchAll();
    adminJson(true, 'Contacts fetched', $contacts);
}

// 7. 保存/更新联系人
if ($action === 'save_contact') {
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    $name = trim((string)($input['name'] ?? ''));
    $phone = trim((string)($input['phone'] ?? ''));
    $company = trim((string)($input['company'] ?? ''));

    if ($name === '' || $phone === '') {
        adminJson(false, 'Name and phone are required', null, 422);
    }

    // 格式化手机号
    $phone = preg_replace('/[^\d+]/', '', $phone);

    if ($id) {
        // 更新
        $stmt = $pdo->prepare("UPDATE contacts SET name = ?, phone = ?, company = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $company ?: null, $id]);
        adminJson(true, 'Contact updated');
    } else {
        // 新建（插入或更新）
        $stmt = $pdo->prepare("
            INSERT INTO contacts (name, phone, company, is_active)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE name = VALUES(name), company = VALUES(company), is_active = 1
        ");
        $stmt->execute([$name, $phone, $company ?: null]);
        adminJson(true, 'Contact saved', ['id' => (int)$pdo->lastInsertId()]);
    }
}

// 8. 批量导入联系人
if ($action === 'import_contacts') {
    $rawText = (string)($input['raw'] ?? '');
    if (trim($rawText) === '') {
        adminJson(false, 'No content provided to import', null, 422);
    }

    $lines = preg_split('/\r\n|\r|\n/', $rawText);
    $inserted = 0;
    $errors = [];

    $stmt = $pdo->prepare("
        INSERT INTO contacts (name, phone, company, is_active)
        VALUES (?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE name = VALUES(name), company = VALUES(company), is_active = 1
    ");

    foreach ($lines as $index => $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        // 支持逗号、Tab、分号分割
        $parts = preg_split('/[,;\t]/', $line);
        $name = trim($parts[0] ?? '');
        $phone = trim($parts[1] ?? '');
        $company = trim($parts[2] ?? '');

        // 容错：如果第一项是纯数字，可能是手机号，反转
        if (preg_match('/^\+?\d{8,}$/', $name) && !preg_match('/^\+?\d{8,}$/', $phone)) {
            $temp = $name;
            $name = $phone ?: 'Contact ' . substr($temp, -4);
            $phone = $temp;
        }

        $phone = preg_replace('/[^\d+]/', '', $phone);
        if ($name === '' || strlen($phone) < 6) {
            $errors[] = "Line " . ($index + 1) . ": invalid format";
            continue;
        }

        try {
            $stmt->execute([$name, $phone, $company ?: null]);
            $inserted++;
        } catch (\PDOException $e) {
            $errors[] = "Line " . ($index + 1) . ": " . $e->getMessage();
        }
    }

    adminJson(true, "Successfully imported {$inserted} contacts", ['imported' => $inserted, 'errors' => $errors]);
}

// 9. 删除联系人
if ($action === 'delete_contact') {
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        adminJson(false, 'Invalid contact ID', null, 422);
    }
    // 先检查是否有关联消息
    $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
    try {
        $stmt->execute([$id]);
        adminJson(true, 'Contact deleted');
    } catch (\PDOException $e) {
        adminJson(false, 'Cannot delete contact because message records exist for this contact', null, 400);
    }
}

// 10. 广播营销活动列表 (Campaigns)
if ($action === 'campaigns') {
    $stmt = $pdo->query("
        SELECT 
            c.id, c.name, c.message_template, c.status, c.scheduled_at, c.created_at,
            COUNT(j.id) as total_jobs,
            SUM(CASE WHEN j.status = 'sent' THEN 1 ELSE 0 END) as sent_jobs,
            SUM(CASE WHEN j.status = 'failed' THEN 1 ELSE 0 END) as failed_jobs,
            SUM(CASE WHEN j.status = 'processing' THEN 1 ELSE 0 END) as processing_jobs,
            SUM(CASE WHEN j.status = 'pending' THEN 1 ELSE 0 END) as pending_jobs
        FROM campaigns c
        LEFT JOIN message_jobs j ON c.id = j.campaign_id
        GROUP BY c.id
        ORDER BY c.id DESC
    ");
    $campaigns = $stmt->fetchAll();
    adminJson(true, 'Campaigns fetched', $campaigns);
}

// 11. 创建并启动营销活动 (Create Campaign)
if ($action === 'create_campaign') {
    $name = trim((string)($input['name'] ?? ''));
    $template = trim((string)($input['message_template'] ?? ''));
    $scheduledAt = !empty($input['scheduled_at']) ? $input['scheduled_at'] : null;
    $launchNow = (bool)($input['launch_now'] ?? true);

    if ($name === '' || $template === '') {
        adminJson(false, 'Campaign name and message template are required', null, 422);
    }

    $adminId = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn() ?: 1;

    $pdo->beginTransaction();
    $status = $launchNow ? 'queued' : 'draft';
    $stmt = $pdo->prepare("
        INSERT INTO campaigns (name, message_template, status, scheduled_at, created_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $template, $status, $scheduledAt, $adminId]);
    $campaignId = (int)$pdo->lastInsertId();

    $jobsCreated = 0;
    if ($launchNow) {
        // 获取所有活跃联系人
        $contactsStmt = $pdo->query("SELECT id, name, phone, company FROM contacts WHERE is_active = 1");
        $contacts = $contactsStmt->fetchAll();

        $jobInsert = $pdo->prepare("
            INSERT INTO message_jobs (campaign_id, contact_id, rendered_message, status, scheduled_at)
            VALUES (?, ?, ?, 'pending', ?)
        ");

        foreach ($contacts as $contact) {
            // 模板变量渲染：{name}, {phone}, {company}
            $rendered = str_replace(
                ['{name}', '{phone}', '{company}'],
                [$contact['name'], $contact['phone'], $contact['company'] ?? ''],
                $template
            );

            $jobInsert->execute([$campaignId, $contact['id'], $rendered, $scheduledAt]);
            $jobsCreated++;
        }

        // 如果生成了任务，将活动标为 running
        if ($jobsCreated > 0) {
            $pdo->prepare("UPDATE campaigns SET status = 'running' WHERE id = ?")->execute([$campaignId]);
        }
    }

    $pdo->commit();
    adminJson(true, "Campaign created with {$jobsCreated} queued jobs", ['id' => $campaignId, 'jobs_created' => $jobsCreated]);
}

// 12. 更新活动状态（运行/暂停/取消）
if ($action === 'update_campaign_status') {
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    $status = $input['status'] ?? '';
    if (!$id || !in_array($status, ['queued', 'running', 'paused', 'cancelled', 'completed'], true)) {
        adminJson(false, 'Invalid campaign or status', null, 422);
    }

    $pdo->prepare("UPDATE campaigns SET status = ? WHERE id = ?")->execute([$status, $id]);

    if ($status === 'cancelled') {
        // 取消所有未发送的任务
        $pdo->prepare("UPDATE message_jobs SET status = 'cancelled' WHERE campaign_id = ? AND status = 'pending'")->execute([$id]);
    }

    adminJson(true, "Campaign status updated to {$status}");
}

// 13. 任务队列列表 (Message Jobs)
if ($action === 'jobs') {
    $filterStatus = $_GET['status'] ?? 'all';
    $campaignId = filter_var($_GET['campaign_id'] ?? null, FILTER_VALIDATE_INT);

    $sql = "
        SELECT 
            j.id, j.campaign_id, j.contact_id, j.rendered_message, j.status,
            j.attempts, j.max_attempts, j.worker_id, j.claimed_at, j.scheduled_at,
            j.last_error, j.completed_at, j.created_at,
            c.name as contact_name, c.phone as contact_phone, c.company as contact_company,
            w.name as worker_name,
            camp.name as campaign_name
        FROM message_jobs j
        JOIN contacts c ON j.contact_id = c.id
        LEFT JOIN workers w ON j.worker_id = w.id
        LEFT JOIN campaigns camp ON j.campaign_id = camp.id
        WHERE 1=1
    ";
    $params = [];

    if ($filterStatus !== 'all' && in_array($filterStatus, ['pending', 'processing', 'sent', 'failed', 'cancelled'], true)) {
        $sql .= " AND j.status = :status";
        $params['status'] = $filterStatus;
    }

    if ($campaignId) {
        $sql .= " AND j.campaign_id = :campaign_id";
        $params['campaign_id'] = $campaignId;
    }

    $sql .= " ORDER BY j.id DESC LIMIT 150";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();

    adminJson(true, 'Jobs fetched', $jobs);
}

// 14. 重试失败的 Job
if ($action === 'retry_job') {
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        adminJson(false, 'Invalid job ID', null, 422);
    }
    $stmt = $pdo->prepare("
        UPDATE message_jobs 
        SET status = 'pending', attempts = 0, last_error = NULL, worker_id = NULL, claimed_at = NULL 
        WHERE id = ? AND status IN ('failed', 'cancelled')
    ");
    $stmt->execute([$id]);
    adminJson(true, 'Job requeued successfully');
}

// 15. 取消待发 Job
if ($action === 'cancel_job') {
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        adminJson(false, 'Invalid job ID', null, 422);
    }
    $stmt = $pdo->prepare("UPDATE message_jobs SET status = 'cancelled' WHERE id = ? AND status = 'pending'");
    $stmt->execute([$id]);
    adminJson(true, 'Job cancelled');
}

// 16. 快速即时单发 (Direct Send)
if ($action === 'direct_send') {
    $phone = trim((string)($input['phone'] ?? ''));
    $name = trim((string)($input['name'] ?? 'Recipient'));
    $message = trim((string)($input['message'] ?? ''));

    if ($phone === '' || $message === '') {
        adminJson(false, 'Phone number and message are required', null, 422);
    }

    $phone = preg_replace('/[^\d+]/', '', $phone);

    // 查找或创建联系人
    $stmt = $pdo->prepare("SELECT id FROM contacts WHERE phone = ? LIMIT 1");
    $stmt->execute([$phone]);
    $contactId = $stmt->fetchColumn();

    if (!$contactId) {
        $insertContact = $pdo->prepare("INSERT INTO contacts (name, phone, is_active) VALUES (?, ?, 1)");
        $insertContact->execute([$name, $phone]);
        $contactId = (int)$pdo->lastInsertId();
    }

    // 插入消息任务
    $stmt = $pdo->prepare("
        INSERT INTO message_jobs (campaign_id, contact_id, rendered_message, status)
        VALUES (NULL, ?, ?, 'pending')
    ");
    $stmt->execute([$contactId, $message]);
    $jobId = (int)$pdo->lastInsertId();

    adminJson(true, 'Message queued successfully', ['job_id' => $jobId]);
}

// 17. 模拟 Worker 处理（用于在没有启动真实 Python 自动化时，本地直观测试队列流转）
if ($action === 'simulate_worker') {
    // 查找一个 pending 的任务
    $stmt = $pdo->query("
        SELECT id, rendered_message 
        FROM message_jobs 
        WHERE status = 'pending' 
        ORDER BY id ASC 
        LIMIT 1
    ");
    $job = $stmt->fetch();
    if (!$job) {
        adminJson(false, 'No pending jobs in queue to simulate', null, 404);
    }

    // 查找或创建一个活跃 worker
    $workerId = $pdo->query("SELECT id FROM workers WHERE is_active = 1 LIMIT 1")->fetchColumn();
    if (!$workerId) {
        // 创建一个模拟 Worker
        $token = bin2hex(random_bytes(16));
        $pdo->prepare("INSERT INTO workers (name, token_hash, is_active, last_heartbeat_at, whatsapp_status, worker_version) VALUES ('Simulated Worker', ?, 1, UTC_TIMESTAMP(), 'connected', 'demo-1.0')")
            ->execute([hash('sha256', $token)]);
        $workerId = (int)$pdo->lastInsertId();
    } else {
        // 刷新该 worker 心跳与状态
        $pdo->prepare("UPDATE workers SET last_heartbeat_at = UTC_TIMESTAMP(), whatsapp_status = 'connected' WHERE id = ?")
            ->execute([$workerId]);
    }

    // 标记为 sent 成功
    $updateStmt = $pdo->prepare("
        UPDATE message_jobs
        SET status = 'sent',
            worker_id = ?,
            attempts = attempts + 1,
            claimed_at = UTC_TIMESTAMP(),
            completed_at = UTC_TIMESTAMP(),
            last_error = NULL
        WHERE id = ?
    ");
    $updateStmt->execute([$workerId, $job['id']]);

    adminJson(true, "Job #{$job['id']} simulated as SENT successfully via worker #{$workerId}", ['job_id' => $job['id']]);
}

// 18. 填充演示样例数据（一键 Seed，让空系统秒变充满数据的 WhatsApp 控制台）
if ($action === 'seed_samples') {
    $pdo->beginTransaction();

    // 示例联系人
    $sampleContacts = [
        ['Alex Tan (陈先生)', '+60123456789', 'Shopee Seller'],
        ['Sarah Wong (黄小姐)', '+60198765432', 'KL Property Hub'],
        ['David Lee (李总)', '+60172348899', 'TechSolutions Ltd'],
        ['Grace Lim (林经理)', '+60163334455', 'Fresh Foods Mart'],
        ['Kevin Zhang', '+601122334455', 'FinTech Capital'],
        ['Amanda Chong', '+60189998877', 'BioHealth Care'],
    ];

    $contactIds = [];
    $cStmt = $pdo->prepare("INSERT INTO contacts (name, phone, company, is_active) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE name=VALUES(name), company=VALUES(company)");
    foreach ($sampleContacts as $c) {
        $cStmt->execute([$c[0], $c[1], $c[2]]);
        $getId = $pdo->prepare("SELECT id FROM contacts WHERE phone = ?");
        $getId->execute([$c[1]]);
        $contactIds[] = (int)$getId->fetchColumn();
    }

    // 示例 Worker
    $workerCheck = $pdo->query("SELECT id FROM workers LIMIT 1")->fetchColumn();
    if (!$workerCheck) {
        $token = 'whatsapp_worker_demo_token_key_123';
        $pdo->prepare("
            INSERT INTO workers (name, token_hash, is_active, last_heartbeat_at, whatsapp_status, worker_version)
            VALUES ('Windows Worker #01 (Playwright)', ?, 1, UTC_TIMESTAMP(), 'connected', 'v1.4.2')
        ")->execute([hash('sha256', $token)]);
    }

    // 示例活动
    $adminId = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn() ?: 1;
    $campStmt = $pdo->prepare("
        INSERT INTO campaigns (name, message_template, status, created_by)
        VALUES (?, ?, 'running', ?)
    ");
    $template = "👋 Hello *{name}* from _{company}_!\n\nWe are pleased to inform you that our special WhatsApp promotion is now active. Please feel free to reply directly to this message if you have any questions.\n\nBest regards,\n*Customer Care Team*";
    $campStmt->execute(['Q3 Customer Loyalty Broadcast', $template, $adminId]);
    $campaignId = (int)$pdo->lastInsertId();

    // 生成几个不同状态的 Jobs
    $jobStmt = $pdo->prepare("INSERT INTO message_jobs (campaign_id, contact_id, rendered_message, status, attempts, completed_at, last_error) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    // 两个 sent
    if (isset($contactIds[0])) {
        $msg1 = str_replace(['{name}', '{company}'], ['Alex Tan (陈先生)', 'Shopee Seller'], $template);
        $jobStmt->execute([$campaignId, $contactIds[0], $msg1, 'sent', 1, date('Y-m-d H:i:s'), null]);
    }
    if (isset($contactIds[1])) {
        $msg2 = str_replace(['{name}', '{company}'], ['Sarah Wong (黄小姐)', 'KL Property Hub'], $template);
        $jobStmt->execute([$campaignId, $contactIds[1], $msg2, 'sent', 1, date('Y-m-d H:i:s'), null]);
    }
    // 一个 processing
    if (isset($contactIds[2])) {
        $msg3 = str_replace(['{name}', '{company}'], ['David Lee (李总)', 'TechSolutions Ltd'], $template);
        $jobStmt->execute([$campaignId, $contactIds[2], $msg3, 'processing', 1, null, null]);
    }
    // 两个 pending
    if (isset($contactIds[3])) {
        $msg4 = str_replace(['{name}', '{company}'], ['Grace Lim (林经理)', 'Fresh Foods Mart'], $template);
        $jobStmt->execute([$campaignId, $contactIds[3], $msg4, 'pending', 0, null, null]);
    }
    if (isset($contactIds[4])) {
        $msg5 = str_replace(['{name}', '{company}'], ['Kevin Zhang', 'FinTech Capital'], $template);
        $jobStmt->execute([$campaignId, $contactIds[4], $msg5, 'pending', 0, null, null]);
    }

    $pdo->commit();
    adminJson(true, 'Sample demo data seeded successfully!');
}

adminJson(false, 'Unknown admin action', null, 404);
