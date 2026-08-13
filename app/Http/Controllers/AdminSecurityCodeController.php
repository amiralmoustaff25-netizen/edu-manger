<?php

namespace App\Http\Controllers;

use App\Services\AdminSecurityCodeService;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminSecurityCodeController extends Controller
{
    public function edit(Request $request, AdminSecurityCodeService $codeService): View
    {
        Gate::authorize('gerer-code-securite-admin');

        return view('admin.security-code', [
            'hasCode' => $codeService->hasCode($request->user()),
            'updatedAt' => $request->user()->security_code_updated_at,
        ]);
    }

    public function update(Request $request, AdminSecurityCodeService $codeService, AuditLogService $auditLog): RedirectResponse
    {
        Gate::authorize('gerer-code-securite-admin');

        // "Modifiable uniquement par le Super Admin après vérification d'identité" :
        // le mot de passe de connexion actuel est revérifié en plus de l'authentification
        // de session normale, avant de toucher au code de sécurité lui-même.
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'security_code' => ['required', 'string', 'min:6', 'confirmed', 'different:current_password'],
        ]);

        $user = $request->user();
        $hadCodeBefore = $codeService->hasCode($user);

        $codeService->setCode($user, $validated['security_code']);

        $auditLog->log(
            action: $hadCodeBefore ? 'security_code_changed' : 'security_code_created',
            modelType: $user::class,
            modelId: $user->id,
            comment: 'Code de sécurité administrateur '.($hadCodeBefore ? 'modifié' : 'défini').' par son propriétaire, après revérification du mot de passe.'
        );

        return back()->with('success', $hadCodeBefore
            ? 'Code de sécurité modifié avec succès.'
            : 'Code de sécurité défini avec succès. Il sera désormais requis pour les actions critiques.');
    }
}
