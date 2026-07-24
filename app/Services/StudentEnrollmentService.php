<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class StudentEnrollmentService
{
    public function enroll(array $data, ?UploadedFile $photo = null, ?int $createdBy = null): User
    {
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

    private function normalizeOptions(array $options): array
    {
        return collect($options)
            ->only(['cantine', 'transport', 'internat'])
            ->map(fn ($value) => (bool) $value)
            ->toArray();
    }
}
