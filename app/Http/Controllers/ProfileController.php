<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
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

        // Réinitialiser password_must_change si le mot de passe a été changé
        if ($user->isDirty('password')) {
            $user->password_must_change = false;
        }

        // Gérer l'upload de la photo de profil
        if ($request->hasFile('profile_photo')) {
            // Supprimer l'ancienne photo si elle existe
            if ($user->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
            }

            // Stocker la nouvelle photo
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
        }

        $user->save();

        return Redirect::route('profile.show')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
