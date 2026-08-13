<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        abort_if($request->user()->hasRole('eleve'), 403);

        $user = $request->user();
        $user->load('latestRegistration.classroom.schoolYear');

        return view('profile.edit', [
            'user' => $user,
            'registration' => $user->latestRegistration,
            'classroom' => $user->latestRegistration?->classroom,
            'schoolYear' => $user->latestRegistration?->schoolYear,
        ]);
    }

    public function show(): View
    {
        $user = Auth::user();
        $user->load('latestRegistration.classroom.schoolYear');

        return view('profile.show', [
            'user' => $user,
            'registration' => $user->latestRegistration,
            'classroom' => $user->latestRegistration?->classroom,
            'schoolYear' => $user->latestRegistration?->schoolYear,
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        abort_if($request->user()->hasRole('eleve'), 403);

        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Gérer l'upload de la photo de profil
        // SEC-03 : disque privé, comme pour les photos d'élèves gérées par
        // StudentController — jamais `public` pour une photo de personne
        // identifiée, servie uniquement via la route contrôlée students.photo.
        if ($request->hasFile('profile_photo')) {
            // Supprimer l'ancienne photo si elle existe
            if ($user->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($user->profile_photo_path);
            }

            // Stocker la nouvelle photo
            $path = $request->file('profile_photo')->store('profile-photos', 'local');
            $user->profile_photo_path = $path;
        }

        $user->save();

        return Redirect::route('profile.show')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        // Même restriction que edit()/update() : un élève ne gère pas son propre
        // compte, seul un administrateur peut le faire.
        abort_if($request->user()->hasRole('eleve'), 403);

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Même garde-fou que RoleAssignmentController::ensureLastSuperAdminNotRemoved :
        // un super-admin ne peut pas se supprimer lui-même s'il est le dernier actif,
        // ce qui laisserait l'établissement sans compte capable de tout administrer.
        if ($user->hasRole('super-admin')) {
            $remainingSuperAdmins = User::role('super-admin')->where('id', '!=', $user->id)->where('is_active', true)->count();

            abort_if($remainingSuperAdmins === 0, 422, 'Impossible de supprimer le dernier compte Super-Admin actif.');
        }

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
