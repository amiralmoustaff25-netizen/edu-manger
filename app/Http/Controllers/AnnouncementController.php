<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Classroom;
use App\Models\User;
use App\Services\AnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AnnouncementController extends Controller
{
    public function __construct(protected AnnouncementService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('voir-historique-notifications');

        $query = Announcement::with(['author', 'classroom'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('role')) {
            $query->whereJsonContains('target_roles', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $announcements = $query->paginate(15)->withQueryString();
        $roles = Role::all()->pluck('name');

        return view('announcements.index', compact('announcements', 'roles'));
    }

    public function create(): View
    {
        $this->authorize('creer-notification');

        $roles = Role::all()->pluck('name');
        $classrooms = Classroom::with('schoolYear')->orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);

        return view('announcements.create', compact('roles', 'classrooms', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('creer-notification');

        $this->resolvePublicationStatus($request);

        $validated = $this->validateAnnouncement($request);

        $announcement = Announcement::create($validated);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('announcements', 'public');
            $announcement->update(['attachment' => $path]);
        }

        if ($request->input('action') === 'preview') {
            return redirect()->route('announcements.preview', $announcement)
                ->withInput();
        }

        return $this->handlePublication($announcement, $request);
    }

    public function preview(Announcement $announcement): View
    {
        $this->authorize('creer-notification');

        $announcement->load(['author', 'classroom']);
        $recipientCount = $this->service->countRecipients($announcement);

        return view('announcements.preview', compact('announcement', 'recipientCount'));
    }

    public function show(Announcement $announcement): View
    {
        $this->authorize('voir-historique-notifications');

        $announcement->load(['author', 'classroom']);
        $this->service->refreshReadCount($announcement);

        return view('announcements.show', compact('announcement'));
    }

    public function edit(Announcement $announcement): View
    {
        $this->authorize('modifier-notification');

        if (! $announcement->isDraft() && ! $announcement->isScheduled()) {
            return redirect()->route('announcements.show', $announcement)
                ->with('error', 'Seuls les brouillons ou notifications programmées peuvent être modifiés.');
        }

        $roles = Role::all()->pluck('name');
        $classrooms = Classroom::with('schoolYear')->orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);

        return view('announcements.edit', compact('announcement', 'roles', 'classrooms', 'users'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('modifier-notification');

        if (! $announcement->isDraft() && ! $announcement->isScheduled()) {
            return redirect()->route('announcements.show', $announcement)
                ->with('error', 'Seuls les brouillons ou notifications programmées peuvent être modifiés.');
        }

        $this->resolvePublicationStatus($request);

        $validated = $this->validateAnnouncement($request, $announcement);

        $announcement->update($validated);

        if ($request->hasFile('attachment')) {
            if ($announcement->attachment) {
                Storage::disk('public')->delete($announcement->attachment);
            }
            $path = $request->file('attachment')->store('announcements', 'public');
            $announcement->update(['attachment' => $path]);
        }

        return $this->handlePublication($announcement, $request);
    }

    public function publish(Announcement $announcement): RedirectResponse
    {
        $this->authorize('publier-notification');

        $this->service->publish($announcement);

        return redirect()->route('announcements.index')
            ->with('success', 'Notification publiée avec succès.');
    }

    public function archive(Announcement $announcement): RedirectResponse
    {
        $this->authorize('archiver-notification');

        $announcement->markAsArchived();

        return redirect()->route('announcements.index')
            ->with('success', 'Notification archivée.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->authorize('archiver-notification');

        $announcement->delete();

        return redirect()->route('announcements.index')
            ->with('success', 'Notification supprimée.');
    }

    protected function resolvePublicationStatus(Request $request): void
    {
        $action = $request->input('action', 'publish');

        $status = match ($action) {
            'draft' => 'draft',
            'schedule' => 'scheduled',
            'preview' => 'draft',
            default => 'published',
        };

        $request->merge(['status' => $status]);
    }

    protected function validateAnnouncement(Request $request, ?Announcement $announcement = null): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:5000',
            'type' => 'required|in:information,important,urgent,reminder,announcement',
            'priority' => 'required|in:normal,important,urgent',
            'target_mode' => 'required|in:all,roles,classroom,users',
            'target_roles' => 'nullable|array',
            'target_roles.*' => 'string',
            'classroom_id' => 'nullable|required_if:target_mode,classroom|exists:classrooms,id',
            'target_user_ids' => 'nullable|array',
            'target_user_ids.*' => 'exists:users,id',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:published_at',
            'status' => 'required|in:draft,scheduled,published',
        ];

        $data = $request->validate($rules);

        if ($request->hasFile('attachment')) {
            $this->ensureMimeTypeIsAllowed(
                $request->file('attachment'),
                ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
            );
        }

        $data['created_by'] = auth()->id();
        $data['target_roles'] = $data['target_roles'] ?? [];
        $data['target_user_ids'] = $data['target_user_ids'] ?? [];

        if ($data['target_mode'] !== 'users') {
            $data['target_user_ids'] = [];
        }

        if ($data['target_mode'] !== 'classroom') {
            $data['classroom_id'] = null;
        }

        return $data;
    }

    protected function handlePublication(Announcement $announcement, Request $request): RedirectResponse
    {
        $status = $request->input('status');

        if ($status === 'draft') {
            return redirect()->route('announcements.index')
                ->with('success', 'Brouillon enregistré.');
        }

        $publishedAt = $request->input('published_at')
            ? \Carbon\Carbon::parse($request->input('published_at'))
            : now();

        if ($status === 'scheduled' && $publishedAt->isFuture()) {
            $this->service->schedule($announcement, $publishedAt);

            return redirect()->route('announcements.index')
                ->with('success', 'Notification programmée avec succès.');
        }

        $this->service->publish($announcement);

        return redirect()->route('announcements.index')
            ->with('success', 'Notification publiée avec succès.');
    }

    private function ensureMimeTypeIsAllowed($file, array $allowedTypes): void
    {
        $detectedMime = $file->getMimeType();

        if (! in_array($detectedMime, $allowedTypes, true)) {
            abort(422, 'Le type de fichier détecté ('.$detectedMime.') n\'est pas autorisé.');
        }
    }
}
