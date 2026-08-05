<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Service générique de journalisation d'audit, utilisable par tous les modules
 * (notes, inscriptions, remises, utilisateurs...), au-delà du seul périmètre financier
 * déjà couvert par PaymentService::logAction().
 */
class AuditLogService
{
    public function log(
        string $action,
        string $modelType,
        ?int $modelId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $comment = null
    ): void {
        DB::table('audit_logs')->insert([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'comment' => $comment,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
