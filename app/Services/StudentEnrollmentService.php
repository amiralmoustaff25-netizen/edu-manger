<?php

namespace App\Services;

use App\Models\ParentModel;
use App\Models\Registration;
use App\Models\SchoolYear;
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

    public function getParentCredentials(): ?array
    {
        return $this->parentCredentials;
    }

    public function enroll(array $data, ?UploadedFile $photo = null, ?int $createdBy = null): User
    {
        $this->parentCredentials = null;

        return DB::transaction(function () use ($data, $photo, $createdBy) {
            $activeYear = SchoolYear::where('is_active', true)->firstOrFail();
            $student = User::create([
                'name' => trim($data['nom'].' '.$data['prenom']),
                'prenom' => $data['prenom'],
                'email' => $data['email'] ?? null,
                'password' => Hash::make('password'),
                'matricule' => User::generateMatricule('eleve'),
                'role' => 'eleve',
                'cycle' => $data['cycle'],
                'telephone' => $data['telephone'] ?? null,
                'date_naissance' => $data['date_naissance'],
                'lieu_naissance' => $data['lieu_naissance'],
                'sexe' => $data['sexe'],
                'nationalite' => $data['nationalite'] ?? null,
                'adresse' => $data['adresse'] ?? null,
                'created_by' => $createdBy,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'password_must_change' => true,
            ]);

            $student->syncRoles(['eleve']);

            if ($photo) {
                $path = $this->storeStudentPhoto($photo, $student->matricule);
                $student->update(['profile_photo_path' => $path]);
            }

            Registration::create([
                'user_id' => $student->id,
                'classroom_id' => $data['classroom_id'],
                'registration_fee_paid' => $data['registration_fee_paid'] ?? 0,
                'monthly_fee' => $data['monthly_fee'] ?? 0,
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
            'role' => 'parent',
            'telephone' => $data['parent_telephone'] ?? null,
            'adresse' => $data['parent_adresse'] ?? null,
            'is_active' => true,
            'password_must_change' => true,
        ]);

        $parentUser->syncRoles(['parent']);

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

    private function normalizeOptions(array $options): array
    {
        return collect($options)
            ->only(['cantine', 'transport', 'internat'])
            ->map(fn ($value) => (bool) $value)
            ->toArray();
    }
}
