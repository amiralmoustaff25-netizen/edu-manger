<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use App\Services\FeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function getByMatricule($matricule): JsonResponse
    {
        // 1. Recherche par le matricule de l'élève (User) — rôle coloîne OU rôle Spatie
        $student = User::query()
            ->where(function ($query) {
                $query->where('role', 'eleve')
                    ->orWhereHas('roles', fn ($q) => $q->where('name', 'eleve'));
            })
            ->where(function ($query) use ($matricule) {
                $query->where('matricule', $matricule)
                    ->orWhere('matricule', 'like', "%{$matricule}%");
            })
            ->first();

        // 2. Fallback par matricule d'inscription (Registration) ou par matricule utilisateur
        if (!$student) {
            $registration = Registration::with(['user', 'classroom', 'schoolYear', 'payments'])
                ->where(function ($query) use ($matricule) {
                    $query->where('matricule', $matricule)
                        ->orWhere('matricule', 'like', "%{$matricule}%")
                        ->orWhereHas('user', function ($q) use ($matricule) {
                            $q->where('matricule', $matricule)
                                ->orWhere('matricule', 'like', "%{$matricule}%");
                        });
                })
                ->first();

            if ($registration) {
                $student = $registration->user;
            }
        }

        if (!$student) {
            return response()->json(['error' => 'Élève non trouvé. Vérifiez le matricule.'], 404);
        }

        // 3. Rechercher l'inscription en cours (pending ou active) de l'année active
        $activeYear = SchoolYear::where('is_active', true)->first();

        $registration = Registration::with(['user', 'classroom', 'schoolYear', 'payments'])
            ->where('user_id', $student->id)
            ->when($activeYear, fn ($q) => $q->where('school_year_id', $activeYear->id))
            ->whereIn('status', ['pending', 'active'])
            ->latest()
            ->first();

        if (!$registration) {
            return response()->json(['error' => 'Aucune inscription en cours pour cet élève.'], 404);
        }

        $feeService = new FeeService();

        return response()->json([
            'registration_id' => $registration->id,
            'matricule' => $registration->matricule,
            'user' => $registration->user,
            'classroom' => $registration->classroom,
            'school_year' => $registration->schoolYear,
            'monthly_fee' => $registration->monthly_fee,
            'options' => $registration->options ?? [],
            'parents' => $registration->user->parents ?? [],
            'payments' => $registration->payments,
            'situation' => $feeService->getFinancialSituation($registration),
        ]);
    }

    public function getStudentFees($registrationId, FeeService $feeService): JsonResponse
    {
        $registration = Registration::with(['classroom', 'schoolYear', 'payments'])
            ->findOrFail($registrationId);

        $fees = $feeService->getPendingFees($registration);
        $situation = $feeService->getFinancialSituation($registration);

        return response()->json([
            'fees' => $fees,
            'total_due' => $situation['remaining'],
            'monthly_fee' => $registration->monthly_fee,
        ]);
    }
}
