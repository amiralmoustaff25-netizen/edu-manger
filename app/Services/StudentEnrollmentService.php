<?php

namespace App\Services;

use App\Models\ClassroomFee;
use App\Models\ParentModel;
use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\StudentClassHistory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;

class StudentEnrollmentService
{
    public ?array $parentCredentials = null;

    public ?string $studentTemporaryPassword = null;

    public function getParentCredentials(): ?array
    {
        return $this->parentCredentials;
    }

    public function enroll(array $data, ?UploadedFile $photo = null, ?int $createdBy = null, bool $canEditFees = false): User
    {
        $this->parentCredentials = null;
        $this->studentTemporaryPassword = Str::password(12);

        return DB::transaction(function () use ($data, $photo, $createdBy, $canEditFees) {
            $activeYear = SchoolYear::where('is_active', true)->firstOrFail();
            $student = User::create([
                'name' => trim($data['nom'].' '.$data['prenom']),
                'prenom' => $data['prenom'],
                'email' => $data['email'] ?? null,
                'password' => Hash::make($this->studentTemporaryPassword),
                'matricule' => User::generateMatricule('eleve'),
                'cycle' => $data['cycle'],
                'telephone' => $data['telephone'] ?? null,
                'date_naissance' => $data['date_naissance'],
                'lieu_naissance' => $data['lieu_naissance'],
                'sexe' => $data['sexe'],
                'nationalite' => $data['nationalite'] ?? null,
                'adresse' => $data['adresse'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'medical_notes' => $data['medical_notes'] ?? null,
                'allergies' => $data['allergies'] ?? null,
                'created_by' => $createdBy,
                'is_active' => (bool) ($data['is_active'] ?? false),
                'password_must_change' => true,
            ]);

            $student->syncRoles(['eleve']);
            $student->syncPrimaryRoleColumn();

            if ($photo) {
                $path = $this->storeStudentPhoto($photo, $student->matricule);
                $student->update(['profile_photo_path' => $path]);
            }

            [$registrationFee, $monthlyFee] = $this->resolveFeeAmounts($data, $activeYear, $canEditFees);

            Registration::create([
                'user_id' => $student->id,
                'classroom_id' => $data['classroom_id'],
                'registration_fee_paid' => $registrationFee,
                'monthly_fee' => $monthlyFee,
                'options' => $this->normalizeOptions($data['options'] ?? []),
                'registration_date' => now()->toDateString(),
                'academic_year' => $activeYear->year_string,
                'school_year_id' => $activeYear->id,
                'matricule' => $this->generateRegistrationMatricule(),
                'status' => 'pending',
            ]);

            $parents = collect($data['parents'] ?? [])
                ->filter(fn ($parent) => ! empty($parent['parent_id']))
                ->mapWithKeys(fn ($parent) => [
                    $parent['parent_id'] => [
                        'lien_parente' => $parent['lien_parente'] ?? null,
                        'est_responsable_financier' => (bool) ($parent['est_responsable_financier'] ?? false),
                        'est_contact_urgence' => (bool) ($parent['est_contact_urgence'] ?? false),
                    ],
                ])
                ->all();

            if ($parents !== []) {
                $student->parents()->sync($parents);
            }

            if (! empty($data['parent_nom']) && ! empty($data['parent_prenom'])) {
                $this->createParentForStudent($student, $data);
            }

            return $student;
        });
    }

    /**
     * Réinscrit un élève déjà existant pour l'année scolaire active, en réutilisant
     * automatiquement son identité, son matricule et son historique (aucune recréation
     * de compte utilisateur). Seuls la classe, les frais et les options sont à confirmer
     * pour la nouvelle année.
     */
    public function reenroll(User $student, array $data, bool $canEditFees): Registration
    {
        return DB::transaction(function () use ($student, $data, $canEditFees) {
            $activeYear = SchoolYear::where('is_active', true)->firstOrFail();

            if (Registration::where('user_id', $student->id)->where('school_year_id', $activeYear->id)->exists()) {
                throw new \RuntimeException('Cet élève est déjà inscrit pour l\'année scolaire active.');
            }

            // Historise la classe/année que l'élève quitte avant de créer la nouvelle
            // inscription — sans ceci, StudentClassHistory ne serait jamais alimenté
            // et l'onglet "Historique de classe" de la fiche élève resterait vide.
            $previousRegistration = Registration::where('user_id', $student->id)->latest('registration_date')->first();
            if ($previousRegistration) {
                StudentClassHistory::create([
                    'user_id' => $student->id,
                    'classroom_id' => $previousRegistration->classroom_id,
                    'school_year_id' => $previousRegistration->school_year_id,
                    'annee_scolaire' => $previousRegistration->academic_year,
                ]);
            }

            [$registrationFee, $monthlyFee] = $this->resolveFeeAmounts($data, $activeYear, $canEditFees);

            $registration = Registration::create([
                'user_id' => $student->id,
                'classroom_id' => $data['classroom_id'],
                'registration_fee_paid' => $registrationFee,
                'monthly_fee' => $monthlyFee,
                'options' => $this->normalizeOptions($data['options'] ?? []),
                'registration_date' => now()->toDateString(),
                'academic_year' => $activeYear->year_string,
                'school_year_id' => $activeYear->id,
                'matricule' => $this->generateRegistrationMatricule(),
                'status' => 'pending',
            ]);

            $student->update(['is_active' => (bool) ($data['is_active'] ?? false)]);

            return $registration;
        });
    }

    private function storeStudentPhoto(UploadedFile $photo, string $matricule): string
    {
        $filename = $matricule.'_'.time().'.jpg';
        $path = 'photos/eleves/'.$filename;

        try {
            if (extension_loaded('imagick')) {
                Image::configure(['driver' => 'imagick']);
            }

            $image = Image::make($photo)->fit(150, 150)->encode('jpg', 90);
            Storage::disk('public')->put($path, $image);
        } catch (\Throwable $e) {
            $ext = $photo->getClientOriginalExtension() ?: 'jpg';
            $filename = $matricule.'_'.time().'.'.$ext;
            $path = 'photos/eleves/'.$filename;
            Storage::disk('public')->putFileAs('photos/eleves', $photo, $filename);
        }

        return $path;
    }

    private function generateRegistrationMatricule(): string
    {
        $sequence = Registration::count() + 1;

        do {
            $matricule = 'EDU-'.date('y').'-'.str_pad($sequence, 6, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Registration::where('matricule', $matricule)->exists());

        return $matricule;
    }

    private function createParentForStudent(User $student, array $data): void
    {
        $parentPassword = Str::random(12);

        $parentUser = User::create([
            'name' => trim($data['parent_nom'] . ' ' . $data['parent_prenom']),
            'prenom' => $data['parent_prenom'],
            'email' => $data['parent_email'],
            'password' => Hash::make($parentPassword),
            'matricule' => User::generateMatricule('parent'),
            'telephone' => $data['parent_telephone'] ?? null,
            'adresse' => $data['parent_adresse'] ?? null,
            'is_active' => true,
            'password_must_change' => true,
        ]);

        $parentUser->syncRoles(['parent']);
        $parentUser->syncPrimaryRoleColumn();

        $parent = ParentModel::create([
            'matricule_parent' => $this->generateParentMatricule(),
            'nom' => $data['parent_nom'],
            'prenom' => $data['parent_prenom'],
            'email' => $data['parent_email'],
            'telephone' => $data['parent_telephone'] ?? null,
            'adresse' => $data['parent_adresse'] ?? null,
            'profession' => $data['parent_profession'] ?? null,
            'statut' => 'actif',
            'user_id' => $parentUser->id,
        ]);

        $parent->students()->attach($student->id, [
            'lien_parente' => $data['parent_lien_parente'] ?? 'Parent',
            'est_responsable_financier' => (bool) ($data['parent_est_responsable_financier'] ?? false),
            'est_contact_urgence' => (bool) ($data['parent_est_contact_urgence'] ?? false),
        ]);

        $this->parentCredentials = [
            'matricule' => $parentUser->matricule,
            'email' => $parentUser->email,
            'password' => $parentPassword,
        ];
    }

    private function generateParentMatricule(): string
    {
        $sequence = ParentModel::withTrashed()->where('matricule_parent', 'like', 'PAR-%')->count() + 1;

        do {
            $matricule = 'PAR-' . date('y') . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (ParentModel::withTrashed()->where('matricule_parent', $matricule)->exists());

        return $matricule;
    }

    /**
     * Détermine les montants d'inscription/mensualité à appliquer. Quand un tarif
     * existe dans la bibliothèque des frais (ClassroomFee) pour la classe et l'année
     * active, il prévaut toujours pour un utilisateur non privilégié : la valeur
     * saisie côté formulaire est ignorée et remplacée par le tarif officiel. Si AUCUN
     * tarif n'y est configuré, la valeur saisie sert de repli — y compris pour un
     * utilisateur non privilégié — pour ne pas bloquer une inscription tant que la
     * bibliothèque des frais n'a pas encore été renseignée pour cette classe. Seul un
     * Super Administrateur / Manager Comptable peut saisir un montant personnalisé
     * qui prévaut MÊME quand un tarif officiel existe (ex. dérogation).
     */
    private function resolveFeeAmounts(array $data, SchoolYear $activeYear, bool $canEditFees): array
    {
        $libraryFees = ClassroomFee::with('feeType')
            ->current()
            ->where('classroom_id', $data['classroom_id'])
            ->where('school_year_id', $activeYear->id)
            ->get()
            ->filter(fn (ClassroomFee $cf) => $cf->feeType)
            ->mapWithKeys(fn (ClassroomFee $cf) => [$cf->feeType->code => (float) $cf->amount]);

        $libraryRegistrationFee = $libraryFees->get('inscription');
        $libraryMonthlyFee = $libraryFees->get('mensualite');

        if (! $canEditFees) {
            return [
                $libraryRegistrationFee ?? (float) ($data['registration_fee_paid'] ?? 0),
                $libraryMonthlyFee ?? (float) ($data['monthly_fee'] ?? 0),
            ];
        }

        return [
            (float) ($data['registration_fee_paid'] ?? $libraryRegistrationFee ?? 0),
            (float) ($data['monthly_fee'] ?? $libraryMonthlyFee ?? 0),
        ];
    }

    private function normalizeOptions(array $options): array
    {
        return collect($options)
            ->only(['cantine', 'transport', 'internat'])
            ->map(fn ($value) => (bool) $value)
            ->toArray();
    }
}
