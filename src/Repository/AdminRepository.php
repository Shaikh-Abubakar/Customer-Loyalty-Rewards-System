<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

/** Read model and low-level persistence for administrator use cases. */
final class AdminRepository
{
    public function __construct(private PDO $pdo) {}

    public function dashboard(): array
    {
        $sql = "SELECT
            (SELECT COUNT(*) FROM users WHERE role='customer') AS customers_total,
            (SELECT COUNT(*) FROM users WHERE role='customer' AND status='active') AS customers_active,
            (SELECT COUNT(*) FROM products) AS products_total,
            (SELECT COUNT(*) FROM codes) AS codes_total,
            (SELECT COUNT(*) FROM codes WHERE status='claimed') AS codes_claimed,
            (SELECT COALESCE(SUM(points_delta),0) FROM points_ledger WHERE entry_type='claim') AS points_awarded,
            (SELECT COUNT(*) FROM rewards WHERE is_active=1 AND stock_quantity>0) AS rewards_available,
            (SELECT COUNT(*) FROM redemptions WHERE status='pending') AS redemptions_pending,
            (SELECT COUNT(*) FROM batches WHERE status='active') AS batches_active,
            (SELECT COUNT(*) FROM points_ledger WHERE entry_type='claim' AND created_at >= UTC_DATE()) AS claims_today,
            (SELECT COALESCE(SUM(points_delta),0) FROM points_ledger WHERE entry_type='claim') AS points_issued,
            (SELECT COALESCE(-SUM(points_delta),0) FROM points_ledger WHERE entry_type='redemption') AS points_spent,
            (SELECT COUNT(*) FROM fraud_events WHERE review_status='open') AS fraud_open";
        return $this->pdo->query($sql)->fetch() ?: [];
    }

    public function recentAdminActivity(int $limit = 8): array
    {
        $limit = max(1, min(20, $limit));
        return $this->pdo->query(
            "SELECT a.id, a.action, a.entity_type, a.entity_id, a.reason, a.created_at,
                    COALESCE(u.email, 'System / initial setup') AS actor_email
             FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_user_id
             ORDER BY a.id DESC LIMIT {$limit}"
        )->fetchAll();
    }

    public function recentLoyaltyActivity(int $limit = 8): array
    {
        $limit = max(1, min(20, $limit));
        return $this->pdo->query(
            "SELECT pl.id, pl.entry_type, pl.points_delta, pl.created_at, cp.full_name, u.email,
                    COALESCE(r.name, b.batch_code, pl.reason, pl.entry_type) AS reference_label
             FROM points_ledger pl
             INNER JOIN users u ON u.id=pl.user_id
             LEFT JOIN customer_profiles cp ON cp.user_id=u.id
             LEFT JOIN redemptions rd ON rd.id=pl.redemption_id
             LEFT JOIN rewards r ON r.id=rd.reward_id
             LEFT JOIN codes c ON c.id=pl.claim_code_id
             LEFT JOIN batches b ON b.id=c.batch_id
             ORDER BY pl.id DESC LIMIT {$limit}"
        )->fetchAll();
    }

    public function products(string $search = '', ?string $status = null): array
    {
        $sql = "SELECT p.id,p.sku,p.name,p.description,p.default_points,p.is_active,p.created_at,p.updated_at,
                       COALESCE(b.batch_count,0) AS batch_count
                FROM products p LEFT JOIN (SELECT product_id,COUNT(*) batch_count FROM batches GROUP BY product_id) b
                  ON b.product_id=p.id WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (p.sku LIKE :search_sku OR p.name LIKE :search_name)';
            $params['search_sku'] = '%' . $search . '%';
            $params['search_name'] = '%' . $search . '%';
        }
        if ($status === 'active' || $status === 'inactive') {
            $sql .= ' AND p.is_active=:active';
            $params['active'] = $status === 'active' ? 1 : 0;
        }
        $sql .= ' ORDER BY p.id DESC LIMIT 250';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function product(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function activeProducts(): array
    {
        return $this->pdo->query('SELECT id,sku,name,default_points FROM products WHERE is_active=1 ORDER BY name')->fetchAll();
    }

    public function batches(string $search = '', ?string $status = null): array
    {
        $sql = "SELECT b.id,b.batch_code,b.product_id,b.points_per_code,b.planned_quantity,b.generated_count,b.status,
                       b.created_at,b.generated_at,b.exported_at,b.printed_at,b.activated_at,b.voided_at,
                       p.sku,p.name AS product_name,
                       (SELECT COUNT(*) FROM codes c WHERE c.batch_id=b.id AND c.status='claimed') AS claimed_count,
                       (SELECT COUNT(*) FROM codes c WHERE c.batch_id=b.id AND c.status='unclaimed') AS unclaimed_count
                FROM batches b INNER JOIN products p ON p.id=b.product_id WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (b.batch_code LIKE :search_batch OR p.sku LIKE :search_sku OR p.name LIKE :search_name)';
            $params['search_batch'] = '%' . $search . '%';
            $params['search_sku'] = '%' . $search . '%';
            $params['search_name'] = '%' . $search . '%';
        }
        $allowed = ['draft','generated','exported','printed','active','voided'];
        if ($status !== null && in_array($status, $allowed, true)) {
            $sql .= ' AND b.status=:status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY b.id DESC LIMIT 250';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function batch(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.*,p.sku,p.name AS product_name,p.is_active AS product_active,
                    (SELECT COUNT(*) FROM codes c WHERE c.batch_id=b.id AND c.status='claimed') AS claimed_count,
                    (SELECT COUNT(*) FROM codes c WHERE c.batch_id=b.id AND c.status='unclaimed') AS unclaimed_count,
                    (SELECT COUNT(*) FROM codes c WHERE c.batch_id=b.id AND c.status='voided') AS voided_count,
                    ce.status AS export_status,ce.exported_at AS recorded_export_at
             FROM batches b INNER JOIN products p ON p.id=b.product_id
             LEFT JOIN code_exports ce ON ce.batch_id=b.id WHERE b.id=:id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function customers(string $search = '', ?string $status = null): array
    {
        $sql = "SELECT u.id,u.email,u.status,u.created_at,u.last_login_at,cp.full_name,cp.phone,
                       COALESCE(l.balance,0) AS points_balance,
                       COALESCE(cl.claim_count,0) AS claim_count
                FROM users u LEFT JOIN customer_profiles cp ON cp.user_id=u.id
                LEFT JOIN (SELECT user_id,SUM(points_delta) balance FROM points_ledger GROUP BY user_id) l ON l.user_id=u.id
                LEFT JOIN (SELECT user_id,COUNT(*) claim_count FROM points_ledger WHERE entry_type='claim' GROUP BY user_id) cl ON cl.user_id=u.id
                WHERE u.role='customer'";
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (u.email LIKE :search_email OR cp.full_name LIKE :search_name)';
            $params['search_email'] = '%' . $search . '%';
            $params['search_name'] = '%' . $search . '%';
        }
        if (in_array($status, ['active','suspended','closed'], true)) {
            $sql .= ' AND u.status=:status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY u.id DESC LIMIT 250';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function customer(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.id,u.email,u.status,u.created_at,u.updated_at,u.last_login_at,
                    cp.full_name,cp.phone,cp.contact_details,cp.anonymised_at,
                    COALESCE((SELECT SUM(points_delta) FROM points_ledger WHERE user_id=u.id),0) AS points_balance
             FROM users u LEFT JOIN customer_profiles cp ON cp.user_id=u.id
             WHERE u.id=:id AND u.role='customer' LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function customerLedger(int $id): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT pl.id,pl.entry_type,pl.points_delta,pl.reason,pl.created_at,pl.reverses_entry_id,
                    rd.id AS redemption_ref,r.name AS reward_name,b.batch_code
             FROM points_ledger pl LEFT JOIN redemptions rd ON rd.id=pl.redemption_id
             LEFT JOIN rewards r ON r.id=rd.reward_id
             LEFT JOIN codes c ON c.id=pl.claim_code_id LEFT JOIN batches b ON b.id=c.batch_id
             WHERE pl.user_id=:id ORDER BY pl.id DESC LIMIT 100"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    public function customerRedemptions(int $id): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT rd.id,rd.points_cost,rd.status,rd.requested_at,rd.fulfilled_at,rd.cancelled_at,
                    rd.fulfilment_note,rd.cancel_reason,r.name AS reward_name
             FROM redemptions rd INNER JOIN rewards r ON r.id=rd.reward_id
             WHERE rd.user_id=:id ORDER BY rd.id DESC LIMIT 100'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    public function customerStatusEvents(int $id): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.action,e.reason_category,e.reason_text,e.created_at,u.email AS actor_email
             FROM account_status_events e INNER JOIN users u ON u.id=e.acted_by_user_id
             WHERE e.user_id=:id ORDER BY e.id DESC LIMIT 50'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    public function rewards(): array
    {
        return $this->pdo->query('SELECT id,name,description,points_cost,stock_quantity,is_active,created_at,updated_at FROM rewards ORDER BY id DESC LIMIT 250')->fetchAll();
    }

    public function reward(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rewards WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function redemptions(string $search = '', ?string $status = null): array
    {
        $sql = "SELECT rd.id,rd.user_id,rd.reward_id,rd.points_cost,rd.status,rd.requested_at,
                       rd.fulfilled_at,rd.cancelled_at,cp.full_name,u.email,r.name AS reward_name
                FROM redemptions rd INNER JOIN users u ON u.id=rd.user_id
                LEFT JOIN customer_profiles cp ON cp.user_id=u.id
                INNER JOIN rewards r ON r.id=rd.reward_id WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (u.email LIKE :search_email OR cp.full_name LIKE :search_name OR r.name LIKE :search_reward OR CAST(rd.id AS CHAR)=:exact)';
            $params['search_email'] = '%' . $search . '%';
            $params['search_name'] = '%' . $search . '%';
            $params['search_reward'] = '%' . $search . '%';
            $params['exact'] = $search;
        }
        if (in_array($status, ['pending','fulfilled','cancelled'], true)) {
            $sql .= ' AND rd.status=:status';
            $params['status'] = $status;
        }
        $sql .= " ORDER BY CASE rd.status WHEN 'pending' THEN 0 ELSE 1 END,rd.id DESC LIMIT 250";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function redemption(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT rd.*,u.email,cp.full_name,r.name AS reward_name
             FROM redemptions rd INNER JOIN users u ON u.id=rd.user_id
             LEFT JOIN customer_profiles cp ON cp.user_id=u.id
             INNER JOIN rewards r ON r.id=rd.reward_id WHERE rd.id=:id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function fraudEvents(?string $status = 'open', ?string $severity = null, string $search = '', ?string $eventType = null): array
    {
        $sql = "SELECT f.id,f.event_type,f.severity,f.user_id,f.ip_address,f.batch_id,f.code_id,
                       f.details,f.review_status,f.reviewed_at,f.review_note,f.created_at,
                       u.email AS customer_email,cp.full_name,b.batch_code
                FROM fraud_events f LEFT JOIN users u ON u.id=f.user_id
                LEFT JOIN customer_profiles cp ON cp.user_id=u.id LEFT JOIN batches b ON b.id=f.batch_id
                WHERE 1=1";
        $params = [];
        if (in_array($status, ['open','reviewed','dismissed','escalated'], true)) {
            $sql .= ' AND f.review_status=:status';
            $params['status'] = $status;
        }
        if (in_array($severity, ['info','warning','high'], true)) {
            $sql .= ' AND f.severity=:severity';
            $params['severity'] = $severity;
        }
        $eventTypes=['login_bruteforce','repeated_invalid_codes','claim_rate_exceeded','many_accounts_one_ip','voided_batch_code_claimed','redemption_burst','other'];
        if ($eventType!==null && in_array($eventType,$eventTypes,true)) {
            $sql .= ' AND f.event_type=:event_type';
            $params['event_type'] = $eventType;
        }
        if ($search !== '') {
            $sql .= ' AND (f.event_type LIKE :search_type OR f.ip_address LIKE :search_ip OR u.email LIKE :search_email OR cp.full_name LIKE :search_name OR b.batch_code LIKE :search_batch OR CAST(f.user_id AS CHAR)=:search_user_id OR CAST(f.batch_id AS CHAR)=:search_batch_id OR CAST(f.code_id AS CHAR)=:search_code_id)';
            $params['search_type'] = '%' . $search . '%';
            $params['search_ip'] = '%' . $search . '%';
            $params['search_email'] = '%' . $search . '%';
            $params['search_name'] = '%' . $search . '%';
            $params['search_batch'] = '%' . $search . '%';
            $params['search_user_id'] = $search;
            $params['search_batch_id'] = $search;
            $params['search_code_id'] = $search;
        }
        $sql .= ' ORDER BY FIELD(f.severity,\'high\',\'warning\',\'info\'),f.id DESC LIMIT 250';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function auditLogs(string $action = '', string $entity = '', string $search = ''): array
    {
        $sql = "SELECT a.id,a.action,a.entity_type,a.entity_id,a.reason,a.details,a.ip_address,a.correlation_id,a.created_at,
                       u.email AS actor_email
                FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_user_id WHERE 1=1";
        $params = [];
        if ($action !== '') { $sql .= ' AND a.action=:action'; $params['action'] = $action; }
        if ($entity !== '') { $sql .= ' AND a.entity_type=:entity'; $params['entity'] = $entity; }
        if ($search !== '') {
            $sql .= ' AND (u.email LIKE :search_email OR a.reason LIKE :search_reason OR a.details LIKE :search_details OR CAST(a.entity_id AS CHAR)=:exact)';
            $params['search_email'] = '%' . $search . '%';
            $params['search_reason'] = '%' . $search . '%';
            $params['search_details'] = '%' . $search . '%'; $params['exact'] = $search;
        }
        $sql .= ' ORDER BY a.id DESC LIMIT 300';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function reportClaimsByBatch(string $from, string $to): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.id,b.batch_code,p.sku,p.name AS product_name,COUNT(c.id) AS claimed_codes,
                    COALESCE(SUM(pl.points_delta),0) AS points_issued
             FROM batches b INNER JOIN products p ON p.id=b.product_id
             LEFT JOIN codes c ON c.batch_id=b.id AND c.status='claimed'
             LEFT JOIN points_ledger pl ON pl.claim_code_id=c.id
             WHERE (c.claimed_at IS NULL OR (c.claimed_at >= :from_date AND c.claimed_at < DATE_ADD(:to_date,INTERVAL 1 DAY)))
             GROUP BY b.id,b.batch_code,p.sku,p.name ORDER BY b.id DESC"
        );
        $stmt->execute(['from_date' => $from, 'to_date' => $to]);
        return $stmt->fetchAll();
    }

    public function reportPoints(string $from, string $to): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(CASE WHEN entry_type='claim' THEN points_delta ELSE 0 END),0) AS issued,
                    COALESCE(-SUM(CASE WHEN entry_type='redemption' THEN points_delta ELSE 0 END),0) AS spent,
                    COALESCE(SUM(CASE WHEN entry_type='reversal' THEN points_delta ELSE 0 END),0) AS reversed,
                    COUNT(*) AS entries
             FROM points_ledger WHERE created_at >= :from_date AND created_at < DATE_ADD(:to_date,INTERVAL 1 DAY)"
        );
        $stmt->execute(['from_date' => $from, 'to_date' => $to]);
        return $stmt->fetch() ?: [];
    }

    public function reportRedemptions(string $from, string $to): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT status,COUNT(*) AS total,COALESCE(SUM(points_cost),0) AS points_total
             FROM redemptions WHERE requested_at >= :from_date AND requested_at < DATE_ADD(:to_date,INTERVAL 1 DAY)
             GROUP BY status ORDER BY status"
        );
        $stmt->execute(['from_date' => $from, 'to_date' => $to]);
        return $stmt->fetchAll();
    }

    public function reconciliationRuns(): array
    {
        return $this->pdo->query('SELECT rr.*,u.email AS actor_email FROM reconciliation_runs rr INNER JOIN users u ON u.id=rr.run_by_user_id ORDER BY rr.id DESC LIMIT 50')->fetchAll();
    }

    public function reconciliationFindings(?int $runId = null): array
    {
        $sql = 'SELECT f.*,u.email AS customer_email FROM reconciliation_findings f LEFT JOIN users u ON u.id=f.user_id';
        if ($runId !== null) {
            $stmt = $this->pdo->prepare($sql . ' WHERE f.run_id=:run_id ORDER BY f.id');
            $stmt->execute(['run_id' => $runId]);
            return $stmt->fetchAll();
        }
        return $this->pdo->query($sql . " WHERE f.status='open' ORDER BY f.id DESC LIMIT 200")->fetchAll();
    }
}
