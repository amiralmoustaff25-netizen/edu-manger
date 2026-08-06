<?php

namespace App\Services;

use App\Models\ClassroomFee;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SchoolYear;
use Carbon\Carbon;

class FeeService
{
    /**
     * Liste des mois de l'année civile en français.
     */
    private const MONTHS = [
        'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
    ];

    /**
     * Génère tous les frais attendus pour une inscription, avec leur statut de paiement.
     */
    public function getPendingFees(Registration $registration): array
    {
        $fees = [];
        $classroomFees = $this->getClassroomFees($registration);
        $months = $this->getMonthsForSchoolYear($registration->schoolYear);

        $discounts = $this->getActiveDiscounts($registration);

        foreach ($classroomFees as $classroomFee) {
            $feeType = $classroomFee['fee_type'];
            $code = $feeType['code'] ?? $this->normalizeCode($feeType['name'] ?? '');

            if (!$this->isFeeApplicable($registration, $code, $feeType['is_optional'] ?? false)) {
                continue;
            }

            $amount = (float) $classroomFee['amount'];

            if ($feeType['is_recurring'] ?? false) {
                foreach ($months as $monthKey => $monthLabel) {
                    $dueDate = $this->getDueDate($registration->schoolYear, $monthLabel, $monthKey);
                    $discountedAmount = $this->applyDiscounts($amount, $discounts, $dueDate);
                    $fees[] = $this->buildFeeItem($registration, $code, $feeType, $discountedAmount, $monthLabel, $monthKey);
                }
            } else {
                $fees[] = $this->buildFeeItem($registration, $code, $feeType, $amount, null, null);
            }
        }

        return $fees;
    }

    /**
     * Récupère les dérogations tarifaires (Discount) actives pour l'inscription.
     */
    private function getActiveDiscounts(Registration $registration)
    {
        return $registration->discounts()->get();
    }

    /**
     * Applique les dérogations actives à un montant donné pour une date d'échéance.
     */
    private function applyDiscounts(float $amount, $discounts, string $dueDate): float
    {
        foreach ($discounts as $discount) {
            if ($discount->valid_from && $dueDate < $discount->valid_from->toDateString()) {
                continue;
            }
            if ($discount->valid_until && $dueDate > $discount->valid_until->toDateString()) {
                continue;
            }

            if ($discount->type === 'percentage') {
                $amount -= $amount * ((float) $discount->value / 100);
            } else {
                $amount -= (float) $discount->value;
            }
        }

        return max(0, round($amount, 2));
    }

    /**
     * Calcule la situation financière complète d'une inscription.
     */
    public function getFinancialSituation(Registration $registration): array
    {
        $fees = $this->getPendingFees($registration);
        $expected = collect($fees)->sum('amount');
        // Les paiements annulés ne comptent plus dans le solde (traçabilité conservée dans audit_logs).
        $paid = $registration->payments()
            ->whereIn('status', ['complet', 'partiel'])
            ->notCancelled()
            ->sum('amount');
        $remaining = max(0, $expected - (float) $paid);
        $overdue = collect($fees)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', now()->toDateString())
            ->sum('remaining_amount');

        $nextDue = collect($fees)
            ->where('status', '!=', 'paid')
            ->sortBy('due_date')
            ->first();

        return [
            'expected' => (float) $expected,
            'paid' => (float) $paid,
            'remaining' => (float) $remaining,
            'overdue' => (float) $overdue,
            'monthly_fee' => (float) $registration->monthly_fee,
            'next_due' => $nextDue ? [
                'description' => $nextDue['description'],
                'amount' => $nextDue['remaining_amount'],
                'due_date' => $nextDue['due_date'],
            ] : null,
        ];
    }

    /**
     * Renvoie les montants actuels de la bibliothèque des frais pour une classe/année
     * donnée, indexés par code de type de frais (inscription, mensualite, cantine, ...).
     * Sert de source de vérité unique pour préremplir/verrouiller les champs de frais
     * lors de l'inscription, au lieu de laisser ces montants saisis librement.
     */
    public function getCurrentFeeAmounts(int $classroomId, int $schoolYearId): array
    {
        return ClassroomFee::with('feeType')
            ->current()
            ->where('classroom_id', $classroomId)
            ->where('school_year_id', $schoolYearId)
            ->get()
            ->filter(fn (ClassroomFee $cf) => $cf->feeType)
            ->mapWithKeys(fn (ClassroomFee $cf) => [$cf->feeType->code => (float) $cf->amount])
            ->all();
    }

    /**
     * Renvoie les frais configurés pour la classe/année, avec fallback sur l'inscription.
     */
    private function getClassroomFees(Registration $registration): array
    {
        $configured = ClassroomFee::with('feeType')
            ->current()
            ->where('classroom_id', $registration->classroom_id)
            ->where('school_year_id', $registration->school_year_id)
            ->get()
            ->map(fn (ClassroomFee $cf) => [
                'fee_type' => [
                    'id' => $cf->feeType->id,
                    'name' => $cf->feeType->name,
                    'code' => $cf->feeType->code ?? $this->normalizeCode($cf->feeType->name),
                    'is_recurring' => $cf->feeType->is_recurring,
                    'is_optional' => $cf->feeType->is_optional,
                ],
                'amount' => $cf->amount,
            ])
            ->toArray();

        $hasInscription = collect($configured)->contains(fn ($f) => ($f['fee_type']['code'] ?? '') === 'inscription');
        $hasMensualite = collect($configured)->contains(fn ($f) => ($f['fee_type']['code'] ?? '') === 'mensualite');

        if (!$hasInscription && $registration->registration_fee_paid > 0) {
            $configured[] = [
                'fee_type' => [
                    'id' => 'inscription',
                    'name' => "Frais d'inscription",
                    'code' => 'inscription',
                    'is_recurring' => false,
                    'is_optional' => false,
                ],
                'amount' => $registration->registration_fee_paid,
            ];
        }

        if (!$hasMensualite && $registration->monthly_fee > 0) {
            $configured[] = [
                'fee_type' => [
                    'id' => 'mensualite',
                    'name' => 'Scolarité mensuelle',
                    'code' => 'mensualite',
                    'is_recurring' => true,
                    'is_optional' => false,
                ],
                'amount' => $registration->monthly_fee,
            ];
        }

        return $configured;
    }

    /**
     * Construit un élément de frais avec son statut de paiement.
     */
    private function buildFeeItem(
        Registration $registration,
        string $code,
        array $feeType,
        float $amount,
        ?string $month,
        ?string $monthKey
    ): array {
        $paid = $this->getPaidAmountForFee($registration, $code, $month);
        $remaining = max(0, $amount - $paid);
        $status = $remaining <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'pending');
        $dueDate = $this->getDueDate($registration->schoolYear, $month, $monthKey);

        $priority = match (true) {
            $status === 'paid' => 'none',
            $dueDate < now()->toDateString() => 'high',
            $code === 'inscription' => 'high',
            default => 'medium',
        };

        return [
            'id' => $month ? "{$code}-{$month}" : $code,
            'fee_type_id' => $feeType['id'] ?? null,
            'code' => $code,
            'description' => $month
                ? "{$feeType['name']} - {$month}"
                : $feeType['name'],
            'type' => $code,
            'amount' => $amount,
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'month' => $month,
            'due_date' => $dueDate,
            'status' => $status,
            'priority' => $priority,
        ];
    }

    /**
     * Calcule le montant déjà payé pour un frais donné.
     */
    private function getPaidAmountForFee(Registration $registration, string $code, ?string $month): float
    {
        $paid = 0.0;

        foreach ($registration->payments()->whereIn('status', ['complet', 'partiel'])->notCancelled()->get() as $payment) {
            if ($payment->fee_breakdown) {
                foreach ($payment->fee_breakdown as $item) {
                    $itemCode = $item['code'] ?? $this->normalizeCode($item['type'] ?? '');
                    $itemMonth = $item['month'] ?? null;

                    if ($itemCode === $code && $itemMonth === $month) {
                        $paid += (float) ($item['amount_paid'] ?? $item['amount'] ?? 0);
                    }
                }
                continue;
            }

            $paymentCode = $this->normalizeCode($payment->payment_type ?? '');
            $paymentMonth = $payment->month;

            if ($paymentMonth === 'Multiple' && $month) {
                // Anciens paiements multiples sans répartition : on ne peut pas rattacher précisément.
                continue;
            }

            if ($paymentCode === $code && $paymentMonth === $month) {
                $paid += (float) $payment->amount;
            }
        }

        return $paid;
    }

    /**
     * Détermine si un type de frais s'applique à l'élève en fonction de ses options.
     */
    private function isFeeApplicable(Registration $registration, string $code, bool $isOptional): bool
    {
        $options = $registration->options ?? [];

        if (in_array($code, ['inscription', 'mensualite'], true)) {
            return true;
        }

        if (!$isOptional) {
            return true;
        }

        return (bool) ($options[$code] ?? false);
    }

    /**
     * Calcule la date d'échéance d'un frais.
     */
    private function getDueDate(?SchoolYear $schoolYear, ?string $month, ?string $monthKey): string
    {
        $year = now()->year;

        if ($month && $monthKey && str_contains($monthKey, '-')) {
            $parts = explode('-', $monthKey);
            $yearPart = $parts[0] ?? $year;
            $monthNumber = $parts[1] ?? now()->month;
            return Carbon::createFromDate((int) $yearPart, (int) $monthNumber, 1)->endOfMonth()->toDateString();
        }

        if ($schoolYear && $schoolYear->start_date) {
            $base = Carbon::parse($schoolYear->start_date);
        } else {
            $base = now();
        }

        if ($month) {
            $monthIndex = array_search($month, self::MONTHS, true);
            if ($monthIndex !== false) {
                $target = $base->copy()->month($monthIndex + 1);
                if ($target->lessThan($base)) {
                    $target->addYear();
                }
                return $target->endOfMonth()->toDateString();
            }
        }

        return $base->endOfMonth()->toDateString();
    }

    /**
     * Renvoie les mois de l'année scolaire sous forme [clé => label].
     */
    public function getMonthsForSchoolYear(?SchoolYear $schoolYear): array
    {
        $months = [];

        if ($schoolYear && $schoolYear->start_date && $schoolYear->end_date) {
            $start = Carbon::parse($schoolYear->start_date);
            $end = Carbon::parse($schoolYear->end_date);
            $current = $start->copy()->startOfMonth();

            while ($current->lessThanOrEqualTo($end)) {
                $key = $current->format('Y-m');
                $label = self::MONTHS[$current->month - 1];
                $months[$key] = $label;
                $current->addMonth();
            }

            return $months;
        }

        // Fallback : année scolaire classique d'octobre à juillet si year_string présent
        if ($schoolYear && $schoolYear->year_string && preg_match('/(\d{4})-(\d{4})/', $schoolYear->year_string, $m)) {
            $startYear = (int) $m[1];
            $start = Carbon::create($startYear, 10, 1);
            for ($i = 0; $i < 10; $i++) {
                $current = $start->copy()->addMonths($i);
                $key = $current->format('Y-m');
                $label = self::MONTHS[$current->month - 1];
                $months[$key] = $label;
            }

            return $months;
        }

        // Dernier recours : 9 mois à partir du mois courant
        $start = now()->startOfMonth();
        for ($i = 0; $i < 9; $i++) {
            $current = $start->copy()->addMonths($i);
            $key = $current->format('Y-m');
            $label = self::MONTHS[$current->month - 1];
            $months[$key] = $label;
        }

        return $months;
    }

    /**
     * Normalise une chaîne en code utilisable (ex: "Frais d'inscription" => "frais_d_inscription").
     */
    public function normalizeCode(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        $value = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $value));
        return trim($value, '_');
    }
}
