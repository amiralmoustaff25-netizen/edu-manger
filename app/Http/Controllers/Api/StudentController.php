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
        $registration = Registration::with(['classroom', 'schoolYear'])
            ->findOrFail($registrationId);

        // Générer les frais de l'élève basés sur la configuration
        // Pour l'instant, on simule avec des données de base
        $fees = [
            [
                'id' => 1,
                'description' => 'Frais d\'inscription',
                'type' => 'inscription',
                'amount' => 50000,
                'status' => 'pending',
                'due_date' => now()->addDays(30)->format('Y-m-d'),
            ],
            [
                'id' => 2,
                'description' => 'Mensualité Septembre',
                'type' => 'mensualité',
                'amount' => $registration->monthly_fee,
                'status' => 'pending',
                'due_date' => now()->addDays(15)->format('Y-m-d'),
            ],
            [
                'id' => 3,
                'description' => 'Mensualité Octobre',
                'type' => 'mensualité',
                'amount' => $registration->monthly_fee,
                'status' => 'pending',
                'due_date' => now()->addDays(45)->format('Y-m-d'),
            ],
        ];

        // Vérifier les paiements existants pour marquer les frais comme payés
        $paidMonths = $registration->payments()->pluck('month')->toArray();
        
        foreach ($fees as &$fee) {
            if (in_array($fee['description'], $paidMonths)) {
                $fee['status'] = 'paid';
            }
        }

        return response()->json($fees);
    }
}
