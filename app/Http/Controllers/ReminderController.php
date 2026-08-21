<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\Reminder;
use App\Services\ReminderService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Autorisation déléguée au middleware de route (role:super-admin|manager-comptable,
// routes/web.php) : les anciens appels $this->authorize('manager-comptable') utilisaient
// un nom de RÔLE comme ability, ce qui n'est pas une permission valide et bloquait
// systématiquement le rôle manager-comptable (le seul rôle métier visé) avec un 403.
class ReminderController extends Controller
{
    protected $reminderService;

    public function __construct(ReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    /**
     * Afficher la liste des rappels
     */
    public function index(): View
    {
        $reminders = Reminder::with(['registration.user', 'registration.classroom'])
            ->latest()
            ->paginate(15);

        return view('accounting.reminders.index', compact('reminders'));
    }

    /**
     * Afficher le formulaire de création de rappel
     */
    public function create(): View
    {
        $registrations = Registration::where('status', 'active')
            ->with(['user', 'classroom'])
            ->get();

        return view('accounting.reminders.create', compact('registrations'));
    }

    /**
     * Créer un rappel manuel
     */
    public function store(Request $Request): RedirectResponse
    {
        $validated = $Request->validate([
            'registration_id' => 'required|exists:registrations,id',
            'message' => 'required|string|max:500',
            'scheduled_at' => 'required|date|after:now',
        ]);

        $registration = Registration::findOrFail($validated['registration_id']);

        $this->reminderService->createCustomReminder(
            $registration,
            $validated['message'],
            Carbon::parse($validated['scheduled_at'])
        );

        return redirect()->route('reminders.index')
            ->with('success', 'Rappel créé avec succès.');
    }

    /**
     * Supprimer un rappel
     */
    public function destroy(Reminder $reminder): RedirectResponse
    {
        if ($reminder->status === 'sent') {
            return back()->with('error', 'Impossible de supprimer un rappel déjà envoyé.');
        }

        $reminder->delete();

        return back()->with('success', 'Rappel supprimé.');
    }

    /**
     * Générer automatiquement les rappels de retard
     */
    public function generateOverdue(): RedirectResponse
    {
        $this->reminderService->generateOverdueReminders();

        return back()->with('success', 'Rappels de retard générés.');
    }

    /**
     * Générer automatiquement les rappels d'échéances
     */
    public function generateUpcoming(): RedirectResponse
    {
        $this->reminderService->generateUpcomingReminders();

        return back()->with('success', 'Rappels d\'échéances générés.');
    }
}
