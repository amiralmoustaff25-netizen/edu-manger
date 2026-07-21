<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function getByMatricule($matricule): JsonResponse
    {
        // Recherche flexible du matricule
        $registration = Registration::with(['user', 'classroom', 'schoolYear', 'payments'])
            ->where('matricule', 'like', "%{$matricule}%")
            ->where('status', 'active')
            ->first();

        if (!$registration) {
            // Si pas trouvé avec like, essayer recherche exacte
            $registration = Registration::with(['user', 'classroom', 'schoolYear', 'payments'])
                ->where('matricule', $matricule)
                ->first();
            
            if (!$registration) {
                return response()->json(['error' => 'Élève non trouvé. Vérifiez le matricule.'], 404);
            }
            
            if ($registration->status !== 'active') {
                return response()->json(['error' => 'Inscription inactive. Statut: ' . $registration->status], 404);
            }
        }

        return response()->json([
            'registration_id' => $registration->id,
            'matricule' => $registration->matricule,
            'user' => $registration->user,
            'classroom' => $registration->classroom,
            'school_year' => $registration->schoolYear,
            'monthly_fee' => $registration->monthly_fee,
            'payments' => $registration->payments,
        ]);
    }

    public function getStudentFees($registrationId): JsonResponse
    {
        $registration = Registration::with(['classroom', 'schoolYear', 'payments'])
            ->findOrFail($registrationId);

        $months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        $paidMonths = $registration->payments()->where('status', 'complet')->pluck('month')->toArray();
        
        // Générer les frais mensuels basés sur le mois actuel
        $currentMonthIndex = now()->month - 1; // 0-based index
        $fees = [];

        // Frais d'inscription (si pas payé)
        $hasPaidRegistration = $registration->payments()->where('payment_type', 'inscription')->exists();
        if (!$hasPaidRegistration) {
            $fees[] = [
                'id' => 'inscription',
                'description' => 'Frais d\'inscription',
                'type' => 'inscription',
                'amount' => 50000, // À configurer dynamiquement
                'status' => 'pending',
                'due_date' => now()->format('Y-m-d'),
                'priority' => 'high',
            ];
        }

        // Générer les mensualités à partir du mois actuel
        for ($i = $currentMonthIndex; $i < min($currentMonthIndex + 3, 12); $i++) {
            $monthName = $months[$i];
            $isPaid = in_array($monthName, $paidMonths);
            
            $fees[] = [
                'id' => 'mensualite-' . $i,
                'description' => 'Mensualité ' . $monthName,
                'type' => 'mensualité',
                'amount' => (float) $registration->monthly_fee,
                'status' => $isPaid ? 'paid' : 'pending',
                'due_date' => now()->month($i + 1)->endOfMonth()->format('Y-m-d'),
                'priority' => $isPaid ? 'none' : 'medium',
            ];
        }

        return response()->json([
            'fees' => $fees,
            'total_due' => collect($fees)->where('status', 'pending')->sum('amount'),
            'monthly_fee' => $registration->monthly_fee,
        ]);
    }
}
