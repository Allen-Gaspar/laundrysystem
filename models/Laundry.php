<?php

class Laundry
{
    private PDO $db;

    public function __construct(?PDO $pdo = null)
    {
        $this->db = $pdo ?? Database::getConnection();
    }


    /** @return array<int, array<string, mixed>> */
    public function allServices(bool $activeOnly = false): array
    {
        $sql = 'SELECT id, name, description, price_per_kg, is_active, created_at FROM services';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY id DESC';
        return $this->db->query($sql)->fetchAll();
    }

    public function getService(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, description, price_per_kg, is_active FROM services WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createService(string $name, string $description, float $pricePerKg, bool $active = true): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO services (name, description, price_per_kg, is_active) VALUES (:n,:d,:p,:a)'
        );
        $stmt->execute([
            'n' => $name,
            'd' => $description,
            'p' => $pricePerKg,
            'a' => $active ? 1 : 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateService(int $id, string $name, string $description, float $pricePerKg, bool $active): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE services SET name = :n, description = :d, price_per_kg = :p, is_active = :a WHERE id = :id'
        );
        return $stmt->execute([
            'n' => $name,
            'd' => $description,
            'p' => $pricePerKg,
            'a' => $active ? 1 : 0,
            'id' => $id,
        ]);
    }

    public function deleteService(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM services WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }


    /** @return array<int, array<string, mixed>> */
    public function allMachines(): array
    {
        return $this->db
            ->query('SELECT id, name, machine_type, status, created_at FROM machines ORDER BY id ASC')
            ->fetchAll();
    }

    public function getMachine(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, machine_type, status FROM machines WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createMachine(string $name, string $type, string $status = 'available'): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO machines (name, machine_type, status) VALUES (:n,:t,:s)'
        );
        $stmt->execute(['n' => $name, 't' => $type, 's' => $status]);
        return (int) $this->db->lastInsertId();
    }

    public function updateMachine(int $id, string $name, string $type, string $status): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE machines SET name = :n, machine_type = :t, status = :s WHERE id = :id'
        );
        return $stmt->execute(['n' => $name, 't' => $type, 's' => $status, 'id' => $id]);
    }

    public function deleteMachine(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM machines WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /** Available machines matching type (washer for washing phase, etc.) */
    public function availableMachinesByType(string $machineType): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, machine_type, status FROM machines WHERE machine_type = :t AND status = 'available' ORDER BY id"
        );
        $stmt->execute(['t' => $machineType]);
        return $stmt->fetchAll();
    }


    /** @return array<int, array<string, mixed>> */
    public function allInventory(): array
    {
        return $this->db
            ->query('SELECT id, item_name, quantity, unit, reorder_level, updated_at FROM inventory ORDER BY item_name')
            ->fetchAll();
    }

    /** Items at or below reorder level */
    public function getLowStockItems(): array
    {
        $stmt = $this->db->query(
            'SELECT id, item_name, quantity, unit, reorder_level FROM inventory WHERE quantity <= reorder_level ORDER BY item_name'
        );
        return $stmt->fetchAll();
    }

    public function createInventoryItem(string $name, float $qty, string $unit, float $reorder): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO inventory (item_name, quantity, unit, reorder_level) VALUES (:n,:q,:u,:r)'
        );
        $stmt->execute(['n' => $name, 'q' => $qty, 'u' => $unit, 'r' => $reorder]);
        return (int) $this->db->lastInsertId();
    }

    public function updateInventoryItem(int $id, string $name, float $qty, string $unit, float $reorder): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE inventory SET item_name = :n, quantity = :q, unit = :u, reorder_level = :r WHERE id = :id'
        );
        return $stmt->execute(['n' => $name, 'q' => $qty, 'u' => $unit, 'r' => $reorder, 'id' => $id]);
    }

    public function deleteInventoryItem(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM inventory WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }


    public function generateTrackingCode(): string
    {
        return 'LND-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    public function createBooking(int $userId, int $serviceId, float $weightKg, ?string $notes = null): array
    {
        $svc = $this->getService($serviceId);
        if (!$svc || !(int) $svc['is_active']) {
            throw new InvalidArgumentException('Invalid service');
        }
        $amount = round((float) $svc['price_per_kg'] * $weightKg, 2);
        $code = $this->generateTrackingCode();
        $stmt = $this->db->prepare(
            'INSERT INTO transactions (tracking_code, user_id, service_id, weight_kg, amount, status, notes)
             VALUES (:c,:u,:s,:w,:a,\'pending\',:n)'
        );
        $stmt->execute([
            'c' => $code,
            'u' => $userId,
            's' => $serviceId,
            'w' => $weightKg,
            'a' => $amount,
            'n' => $notes ?? '',
        ]);
        $id = (int) $this->db->lastInsertId();
        return ['id' => $id, 'tracking_code' => $code, 'amount' => $amount];
    }

    public function getTransactionByTracking(string $code): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, s.name AS service_name, s.price_per_kg, u.full_name, u.phone, u.email, m.name AS machine_name
             FROM transactions t
             JOIN services s ON s.id = t.service_id
             JOIN users u ON u.id = t.user_id
             LEFT JOIN machines m ON m.id = t.machine_id
             WHERE t.tracking_code = :c LIMIT 1'
        );
        $stmt->execute(['c' => $code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getTransactionForUser(int $transactionId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, s.name AS service_name FROM transactions t
             JOIN services s ON s.id = t.service_id
             WHERE t.id = :id AND t.user_id = :u LIMIT 1'
        );
        $stmt->execute(['id' => $transactionId, 'u' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listTransactionsForCustomer(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.id, t.tracking_code, t.weight_kg, t.amount, t.status, t.created_at, t.started_at, t.completed_at, s.name AS service_name
             FROM transactions t
             JOIN services s ON s.id = t.service_id
             WHERE t.user_id = :u
             ORDER BY t.id DESC'
        );
        $stmt->execute(['u' => $userId]);
        return $stmt->fetchAll();
    }

    public function getCurrentOrderForUser(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, s.name AS service_name
             FROM transactions t
             JOIN services s ON s.id = t.service_id
             WHERE t.user_id = :u AND t.status NOT IN (\'completed\',\'cancelled\')
             ORDER BY t.id DESC LIMIT 1'
        );
        $stmt->execute(['u' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Admin/staff board */
    public function listIncomingBookings(): array
    {
        $stmt = $this->db->query(
            'SELECT t.*, s.name AS service_name, u.full_name, u.phone, u.email, m.name AS machine_name
             FROM transactions t
             JOIN services s ON s.id = t.service_id
             JOIN users u ON u.id = t.user_id
             LEFT JOIN machines m ON m.id = t.machine_id
             WHERE t.status NOT IN (\'completed\',\'cancelled\')
             ORDER BY t.created_at ASC'
        );
        return $stmt->fetchAll();
    }

    public function listAllBookingsForAdmin(int $limit = 200): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, s.name AS service_name, u.full_name, u.phone
             FROM transactions t
             JOIN services s ON s.id = t.service_id
             JOIN users u ON u.id = t.user_id
             ORDER BY t.id DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Updates status; frees machine on completed/cancelled; SMS when Ready.
     */
    public function updateStatus(int $transactionId, string $newStatus, ?SmsService $sms = null, ?EmailService $emailService = null): bool
    {
        $allowed = ['pending', 'washing', 'drying', 'ready', 'completed', 'cancelled'];
        if (!in_array($newStatus, $allowed, true)) {
            throw new InvalidArgumentException('Invalid status');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'SELECT t.*, u.phone AS customer_phone FROM transactions t
                 JOIN users u ON u.id = t.user_id WHERE t.id = :id'
            );
            $stmt->execute(['id' => $transactionId]);
            $row = $stmt->fetch();
            if (!$row) {
                $this->db->rollBack();
                return false;
            }

            $oldMachineId = $row['machine_id'] ? (int) $row['machine_id'] : null;

            $setStarted = '';
            $setEnded = '';
            if (in_array($newStatus, ['washing', 'drying', 'ready', 'completed'], true)) {
                $setStarted = ", started_at = COALESCE(started_at, NOW())";
            }
            if (in_array($newStatus, ['completed', 'cancelled'], true)) {
                $setEnded = ", completed_at = NOW()";
            } else {
                $setEnded = ", completed_at = NULL";
            }
            $upd = $this->db->prepare("UPDATE transactions SET status = :st{$setStarted}{$setEnded} WHERE id = :id");
            $upd->execute(['st' => $newStatus, 'id' => $transactionId]);

            if (in_array($newStatus, ['completed', 'cancelled'], true) && $oldMachineId) {
                $this->db->prepare(
                    "UPDATE machines SET status = 'available' WHERE id = :id"
                )->execute(['id' => $oldMachineId]);
                $this->db->prepare(
                    'UPDATE transactions SET machine_id = NULL WHERE id = :id'
                )->execute(['id' => $transactionId]);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        if ($newStatus === 'ready' && $sms !== null) {
            $phone = (string) $row['customer_phone'];
            $amount = (float) $row['amount'];
            $cfg = require dirname(__DIR__) . '/config/config.php';
            $base = $cfg['app']['base_url'] ?: self::detectBaseUrl();
            $link = rtrim($base, '/') . '/customer/invoice.php?code=' . urlencode($row['tracking_code']);
            $sms->send(
                $phone,
                'Your laundry is ready for pickup! Total: P' . number_format($amount, 2)
                . ' Invoice: ' . $link
            );
            $this->db->prepare(
                'UPDATE transactions SET invoice_sent = 1 WHERE id = :id'
            )->execute(['id' => $transactionId]);
        }

        if ($emailService !== null) {
            $fresh = $this->getTransactionById($transactionId);
            if ($fresh) {
                $emailService->sendStatusUpdated($fresh);
            }
        }

        return true;
    }

    public function getTransactionById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, s.name AS service_name, s.price_per_kg, u.full_name, u.phone, u.email, m.name AS machine_name
             FROM transactions t
             JOIN services s ON s.id = t.service_id
             JOIN users u ON u.id = t.user_id
             LEFT JOIN machines m ON m.id = t.machine_id
             WHERE t.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function detectBaseUrl(): string
    {
        require_once dirname(__DIR__) . '/includes/paths.php';
        return base_url();
    }

    /** Assign machine + set in_use; optional AJAX */
    public function assignMachine(int $transactionId, ?int $machineId): bool
    {
        $this->db->beginTransaction();
        try {
            $tx = $this->db->prepare('SELECT id, machine_id, status FROM transactions WHERE id = :id');
            $tx->execute(['id' => $transactionId]);
            $t = $tx->fetch();
            if (!$t) {
                $this->db->rollBack();
                return false;
            }

            $oldId = $t['machine_id'] ? (int) $t['machine_id'] : null;

            if ($oldId) {
                $this->db->prepare(
                    "UPDATE machines SET status = 'available' WHERE id = :id"
                )->execute(['id' => $oldId]);
            }

            if ($machineId === null) {
                $this->db->prepare(
                    'UPDATE transactions SET machine_id = NULL WHERE id = :id'
                )->execute(['id' => $transactionId]);
                $this->db->commit();
                return true;
            }

            $m = $this->db->prepare('SELECT id, status FROM machines WHERE id = :id');
            $m->execute(['id' => $machineId]);
            $machine = $m->fetch();
            if (!$machine || $machine['status'] !== 'available') {
                $this->db->rollBack();
                return false;
            }

            $this->db->prepare(
                "UPDATE machines SET status = 'in_use' WHERE id = :id"
            )->execute(['id' => $machineId]);

            $this->db->prepare(
                'UPDATE transactions SET machine_id = :mid WHERE id = :id'
            )->execute(['mid' => $machineId, 'id' => $transactionId]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    /** @return array{labels: string[], values: float[]} */
    public function salesByDay(int $days = 14): array
    {
        $days = max(1, min(366, $days));
        $sql = "SELECT DATE(created_at) AS d, COALESCE(SUM(amount),0) AS total
             FROM transactions
             WHERE status NOT IN ('cancelled') AND created_at >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY)
             GROUP BY DATE(created_at)
             ORDER BY d ASC";
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();
        $labels = [];
        $values = [];
        foreach ($rows as $r) {
            $labels[] = $r['d'];
            $values[] = (float) $r['total'];
        }
        return ['labels' => $labels, 'values' => $values];
    }

    /** @return array{labels: string[], values: float[]} */
    public function salesByPeriod(string $period): array
    {
        if ($period === 'weekly') {
            $sql = "SELECT DATE_FORMAT(created_at, '%x-W%v') AS k, SUM(amount) AS total
                    FROM transactions
                    WHERE status != 'cancelled' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 16 WEEK)
                    GROUP BY DATE_FORMAT(created_at, '%x-W%v')
                    ORDER BY MIN(created_at)";
        } elseif ($period === 'monthly') {
            $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS k, SUM(amount) AS total
                    FROM transactions
                    WHERE status != 'cancelled' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                    ORDER BY MIN(created_at)";
        } elseif ($period === 'yearly') {
            $sql = "SELECT DATE_FORMAT(created_at, '%Y') AS k, SUM(amount) AS total
                    FROM transactions
                    WHERE status != 'cancelled'
                    GROUP BY DATE_FORMAT(created_at, '%Y')
                    ORDER BY MIN(created_at)";
        } else {
            $sql = "SELECT DATE(created_at) AS k, SUM(amount) AS total
                    FROM transactions
                    WHERE status != 'cancelled' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY DATE(created_at)";
        }
        $rows = $this->db->query($sql)->fetchAll();
        $labels = [];
        $values = [];
        foreach ($rows as $r) {
            $labels[] = (string) $r['k'];
            $values[] = (float) $r['total'];
        }
        return ['labels' => $labels, 'values' => $values];
    }

    public function salesToday(): float
    {
        $stmt = $this->db->query(
            "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'"
        );
        return (float) $stmt->fetchColumn();
    }

    public function countPendingBookings(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM transactions WHERE status = 'pending'"
        );
        return (int) $stmt->fetchColumn();
    }
}
