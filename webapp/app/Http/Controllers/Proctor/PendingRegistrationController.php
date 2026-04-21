<?php

namespace App\Http\Controllers\Proctor;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PendingRegistrationController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $advisorySectionIds = $user->advisorySections()->pluck('id')->all();

        $pendingStudents = User::query()
            ->where('role', 'student')
            ->where('status', 'pending')
            ->with(['studentProfile.program', 'studentProfile.section'])
            ->whereHas('studentProfile', function ($query) use ($advisorySectionIds): void {
                $query->whereIn('section_id', $advisorySectionIds);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('proctor.pending-registrations.index', [
            'pendingStudents' => $pendingStudents,
            'hasSections' => count($advisorySectionIds) > 0,
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        $this->authorizeStudent($user);

        $user->update(['status' => 'active']);

        return back()->with('status', $user->full_name . ' has been approved.');
    }

    public function reject(User $user): RedirectResponse
    {
        $this->authorizeStudent($user);

        $user->update(['status' => 'archived']);

        return back()->with('status', $user->full_name . '\'s registration has been rejected.');
    }

    private function authorizeStudent(User $student): void
    {
        $proctor = auth()->user();

        if ($student->role !== 'student' || $student->status !== 'pending') {
            abort(422, 'Invalid registration action.');
        }

        $advisorySectionIds = $proctor->advisorySections()->pluck('id')->all();
        $studentSectionId = $student->studentProfile?->section_id;

        if (! $studentSectionId || ! in_array($studentSectionId, $advisorySectionIds, false)) {
            abort(403, 'This student is not in your advisory section.');
        }
    }
}
