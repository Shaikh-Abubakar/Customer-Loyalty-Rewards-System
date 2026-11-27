<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\AuditLogRepository;
use App\Repository\BatchRepository;
use App\Repository\CodeGenerationRepository;
use App\Repository\HmacKeyRegistryRepository;
use App\Repository\LoginAttemptRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Support\Database;
use App\Support\Hmac;
use App\Support\Http;
use App\Support\RateLimitGuard;
use PDO;
use PDOException;
use RuntimeException;

final class BatchService
{
    private BatchRepository $batches;
    private ProductRepository $products;
    private CodeGenerationRepository $codes;
    private HmacKeyRegistryRepository $keys;
    private UserRepository $users;
    private AuditLogRepository $audit;
    private LoginAttemptRepository $loginAttempts;

    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTVWXYZ';

    public function __construct(private PDO $pdo)
    {
        $this->batches=new BatchRepository($pdo);
        $this->products=new ProductRepository($pdo);
        $this->codes=new CodeGenerationRepository($pdo);
        $this->keys=new HmacKeyRegistryRepository($pdo);
        $this->users=new UserRepository($pdo);
        $this->audit=new AuditLogRepository($pdo);
        $this->loginAttempts=new LoginAttemptRepository($pdo);
    }

    public function create(int $productId,string $batchCode,int $quantity,?int $pointsOverride,int $adminId,?string $ip,?string $correlation): int
    {
        $batchCode=trim($batchCode);
        if ($batchCode==='' || strlen($batchCode)>30 || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,29}$/D',$batchCode)) {
            throw new \InvalidArgumentException('Enter a batch label of 1–30 letters, numbers, dots, underscores or hyphens.');
        }
        if ($quantity<1 || $quantity>1000 || ($pointsOverride!==null && ($pointsOverride<1 || $pointsOverride>10000))) {
            throw new \InvalidArgumentException('Batch quantity must be 1–1,000 and points per code must be 1–10,000.');
        }
        return Database::transaction($this->pdo,function () use ($productId,$batchCode,$quantity,$pointsOverride,$adminId,$ip,$correlation): int {
            $product=$this->batches->activeProduct($productId);
            if ($product===null) { throw new RuntimeException('Choose an active product.'); }
            $points=$pointsOverride??(int)$product['default_points'];
            if ($points<1 || $points>10000) { throw new RuntimeException('The product has an invalid default points value.'); }
            try {
                $id=$this->batches->create($batchCode,$productId,$points,$quantity,$adminId);
            } catch (PDOException $exception) {
                if ((string)$exception->getCode()==='23000') {
                    throw new RuntimeException('That batch label is already in use. Choose a different label.');
                }
                throw $exception;
            }
            $this->audit->record($adminId,'batch_created','batch',$id,null,
                'Created '.$batchCode.' for product '.$product['sku'].'; planned quantity '.$quantity.'; points per code '.$points.'.',$ip,$correlation);
            return $id;
        });
    }

    public function markPrinted(int $batchId,int $adminId,?string $ip,?string $correlation): void
    {
        Database::transaction($this->pdo,function () use ($batchId,$adminId,$ip,$correlation): void {
            $batch=$this->batches->lock($batchId);
            if ($batch===null || $batch['status']!=='exported' || !$this->batches->hasRecordedExport($batchId) || !$this->batches->markPrinted($batchId)) { throw new RuntimeException('Only a batch with a successfully recorded CSV export can be marked printed. If delivery failed, void it and create a replacement.'); }
            $this->audit->record($adminId,'batch_marked_printed','batch',$batchId,null,'Marked exported batch as printed.',$ip,$correlation);
        });
    }

    public function activate(int $batchId,int $adminId,?string $ip,?string $correlation): void
    {
        Database::transaction($this->pdo,function () use ($batchId,$adminId,$ip,$correlation): void {
            $batch=$this->batches->lock($batchId);
            if ($batch===null || !$this->batches->activate($batchId,$adminId)) { throw new RuntimeException('Only a printed batch can be activated.'); }
            $this->audit->record($adminId,'batch_activated','batch',$batchId,null,'Activated batch after release checks.',$ip,$correlation);
        });
    }

    public function void(int $batchId,string $reason,int $adminId,?string $ip,?string $correlation): void
    {
        $reason=trim($reason);
        if ($reason==='' || Http::textLength($reason)>500) { throw new \InvalidArgumentException('Enter a void reason (maximum 500 characters).'); }
        Database::transaction($this->pdo,function () use ($batchId,$reason,$adminId,$ip,$correlation): void {
            $batch=$this->batches->lock($batchId);
            if ($batch===null || !$this->batches->void($batchId,$adminId,$reason)) { throw new RuntimeException('The batch was not found or is already voided.'); }
            $this->audit->record($adminId,'batch_voided','batch',$batchId,$reason,
                'Voided batch. '.$this->countRemainder($batchId).' unclaimed codes are no longer claimable. Previously credited points were not reversed.',$ip,$correlation);
        });
    }

    private function countRemainder(int $batchId): int
    {
        $stmt=$this->pdo->prepare("SELECT COUNT(*) FROM codes WHERE batch_id=:id AND status='unclaimed'");
        $stmt->execute(['id'=>$batchId]);
        return (int)$stmt->fetchColumn();
    }

    /** Generate all codes and commit their hashes before the one-time in-memory CSV is returned. */
    public function generateAndExport(int $batchId,string $password,int $adminId,?string $ip,?string $correlation): array
    {
        $attemptIp=$ip??Http::clientIp();
        $accountForLock=$this->users->findCredentials($adminId);
        $accountLockKey=is_array($accountForLock)?strtolower((string)$accountForLock['email']):'invalid-admin-'.$adminId;
        RateLimitGuard::run($this->pdo, [
            ['scope'=>'login-ip','subject'=>$attemptIp],
            ['scope'=>'login-account','subject'=>$accountLockKey],
        ], function () use ($adminId,$attemptIp,$password): void {
            $admin=$this->users->findCredentials($adminId);
            $limits=app_config('rate_limits',[]);
            $maxFailures=max(1,(int)($limits['login_failures']??5));
            $window=max(60,(int)($limits['login_window_seconds']??900));
            if ($this->loginAttempts->countFailuresByIp($attemptIp,$window)>=$maxFailures
                || $this->loginAttempts->countFailuresByUser($adminId,$window)>=$maxFailures) {
                (new FraudService($this->pdo))->recordOnce('login_bruteforce','warning',$adminId,$attemptIp,null,null,
                    'Administrator export password re-entry exceeded the configured failure threshold.',$window);
                throw new \InvalidArgumentException('Administrator password verification is temporarily locked. Try again after the security window.');
            }
            $passwordMatches=$admin!==null && ($admin['role']??'')==='admin' && ($admin['status']??'')==='active'
                && strlen($password)<=72 && password_verify($password,(string)$admin['password_hash']);
            $this->loginAttempts->record($adminId,$attemptIp,$passwordMatches);
            if (!$passwordMatches) {
                throw new \InvalidArgumentException('Administrator password could not be verified.');
            }
        });

        [$activeId,$usableKeys]=$this->verifiedUsableKeys();
        $batch=$this->batches->findWithProduct($batchId);
        if ($batch===null || $batch['status']!=='draft' || (int)$batch['planned_quantity']<1 || (int)$batch['planned_quantity']>1000) {
            throw new RuntimeException('This batch cannot be generated or has already been exported.');
        }
        if ($this->batches->hasExport($batchId)) { throw new RuntimeException('This batch already has a recorded export. Create a new batch.'); }

        $plainCodes=[];
        $hashes=[];
        $seen=[];
        $quantity=(int)$batch['planned_quantity'];
        $activeSecret=$usableKeys[$activeId];
        for ($i=0;$i<$quantity;$i++) {
            $created=false;
            for ($tries=0;$tries<20;$tries++) {
                $candidate=$this->generateCode();
                if (isset($seen[$candidate])) { continue; }
                $candidateHashes=[];
                $collision=false;
                foreach ($usableKeys as $keyId=>$secret) {
                    $digest=Hmac::codeHash($candidate,$secret);
                    if ($this->codes->existsHash($digest,(int)$keyId)) { $collision=true; break; }
                    $candidateHashes[(int)$keyId]=$digest;
                }
                if ($collision) { continue; }
                $seen[$candidate]=true;
                $plainCodes[]=$candidate;
                $hashes[]=$candidateHashes[$activeId];
                $created=true;
                break;
            }
            if (!$created) { throw new RuntimeException('Unique code generation could not complete safely.'); }
        }

        // Commit A: all code hashes and the generated state are atomic.
        Database::transaction($this->pdo,function () use ($batchId,$quantity,$hashes,$activeId,$adminId,$ip,$correlation): void {
            $locked=$this->batches->lock($batchId);
            if ($locked===null || $locked['status']!=='draft' || $this->batches->hasExport($batchId)) {
                throw new RuntimeException('The batch changed before generation. Refresh and try again.');
            }
            foreach ($hashes as $hash) { $this->codes->insert($batchId,$activeId,$hash); }
            if (!$this->batches->setGenerated($batchId,$quantity)) { throw new RuntimeException('The batch could not be marked generated.'); }
            $this->audit->record($adminId,'batch_codes_generated','batch',$batchId,null,
                'Generated '.$quantity.' code hashes; plaintext remained in request memory.',$ip,$correlation);
        });

        // Prepare the memory-only CSV before Commit B. If this fails, record the failed
        // one-time export and require the operator to void this generated batch.
        try {
            $csv=$this->buildCsv((string)$batch['batch_code'],(string)$batch['sku'],(int)$batch['points_per_code'],$plainCodes);
        } catch (\Throwable $exception) {
            try {
                Database::transaction($this->pdo,function () use ($batchId,$quantity,$adminId,$ip,$correlation): void {
                    if (!$this->batches->hasExport($batchId)) {
                        $this->batches->recordExport($batchId,$adminId,$quantity,'failed',$ip,$correlation);
                        $this->audit->record($adminId,'batch_csv_export_failed','batch',$batchId,'CSV generation failed.','No plaintext export was returned; void the batch and create a replacement.',$ip,$correlation);
                    }
                });
            } catch (\Throwable) {}
            unset($plainCodes,$hashes,$seen,$usableKeys,$activeSecret);
            throw $exception;
        }

        // Commit B: export is recorded and the batch transitions to exported before any code is sent.
        try {
            Database::transaction($this->pdo,function () use ($batchId,$quantity,$adminId,$ip,$correlation): void {
                $locked=$this->batches->lock($batchId);
                if ($locked===null || $locked['status']!=='generated' || $this->batches->hasExport($batchId)
                    || !$this->batches->markExported($batchId)) {
                    throw new RuntimeException('Export recording failed. Void this generated batch and create another.');
                }
                $this->batches->recordExport($batchId,$adminId,$quantity,'recorded',$ip,$correlation);
                $this->audit->record($adminId,'batch_csv_exported','batch',$batchId,null,
                    'One-time CSV export recorded for '.$quantity.' codes.',$ip,$correlation);
            });
        } catch (\Throwable $exception) {
            // Do not leave a generated batch eligible for a second export after a failed Commit B.
            try {
                Database::transaction($this->pdo,function () use ($batchId,$quantity,$adminId,$ip,$correlation): void {
                    if ($this->batches->hasExport($batchId)) {
                        // The database may have committed but the client may still have
                        // received an exception. Mark that export unusable; never permit
                        // the batch to be treated as a successfully delivered CSV.
                        $this->batches->updateExportStatus($batchId,'failed');
                    } else {
                        $this->batches->recordExport($batchId,$adminId,$quantity,'failed',$ip,$correlation);
                    }
                    $this->audit->record($adminId,'batch_csv_export_failed','batch',$batchId,'Export transaction failed.','No usable plaintext export was returned; void this batch and create a replacement.',$ip,$correlation);
                });
            } catch (\Throwable) {
                // The batch remains in generated state; operator must void it before replacement.
            }
            throw $exception;
        }

        unset($plainCodes,$hashes,$seen,$usableKeys,$activeSecret);
        return ['filename'=>preg_replace('/[^A-Za-z0-9_-]/','_', (string)$batch['batch_code']).'.csv','csv'=>$csv,'count'=>$quantity];
    }

    public function markDeliveryFailed(int $batchId,int $adminId,?string $ip,?string $correlation): void
    {
        Database::transaction($this->pdo,function () use ($batchId,$adminId,$ip,$correlation): void {
            $this->batches->updateExportStatus($batchId,'failed');
            $this->audit->record($adminId,'batch_csv_delivery_failed','batch',$batchId,'Browser connection was interrupted.','The batch is one-time exported; void it and create a replacement.',$ip,$correlation);
        });
    }

    /** @return array{0:int,1:array<int,string>} */
    private function verifiedUsableKeys(): array
    {
        $settings=app_config('hmac',[]);
        $activeId=(int)($settings['active_key_id']??0);
        $configured=(array)($settings['keys']??[]);
        $rows=$this->keys->findUsableKeys();
        $activeRows=array_values(array_filter($rows,static fn(array $r): bool => $r['status']==='active'));
        if (count($activeRows)!==1 || $activeId<1 || (int)$activeRows[0]['key_id']!==$activeId) {
            throw new RuntimeException('HMAC key configuration and registry do not agree. Code generation is disabled.');
        }
        $usable=[];
        foreach ($rows as $row) {
            $keyId=(int)$row['key_id'];
            if (($row['algorithm']??'')!=='HMAC-SHA256' || !isset($configured[$keyId]) || !is_string($configured[$keyId])) {
                throw new RuntimeException('A retained HMAC key is missing from protected configuration. Code generation is disabled.');
            }
            $secret=Hmac::decodeSecret($configured[$keyId]);
            if (!hash_equals((string)$row['key_check_value'],Hmac::keyCheck($secret))) {
                throw new RuntimeException('An HMAC key registry check failed. Code generation is disabled.');
            }
            $usable[$keyId]=$secret;
        }
        if (!isset($usable[$activeId])) { throw new RuntimeException('The active HMAC key is unavailable.'); }
        return [$activeId,$usable];
    }

    private function generateCode(): string
    {
        $code='';
        $last=strlen(self::ALPHABET)-1;
        for ($i=0;$i<16;$i++) { $code.=self::ALPHABET[random_int(0,$last)]; }
        return $code;
    }


    /** Uses php://memory only; no plaintext code file is created on disk. */
    private function buildCsv(string $batchCode,string $sku,int $points,array $codes): string
    {
        $stream=fopen('php://memory','w+b');
        if ($stream===false) { throw new RuntimeException('The in-memory export buffer is unavailable.'); }
        fputcsv($stream,['batch_code','row_number','product_sku','points','code','claim_url'],',','"','');
        foreach ($codes as $index=>$code) {
            $displayCode=implode('-',str_split($code,4));
            fputcsv($stream,[$batchCode,$index+1,$sku,$points,$displayCode,url('/claim.php#c='.$displayCode)],',','"','');
        }
        rewind($stream);
        $csv=stream_get_contents($stream);
        fclose($stream);
        if (!is_string($csv)) { throw new RuntimeException('The in-memory CSV could not be read.'); }
        return $csv;
    }
}
