<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicHeadProfile;
use App\Models\CashierProfile;
use App\Models\ProctorProfile;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->with([
                'studentProfile.section.proctor',
                'proctorProfile',
                'cashierProfile',
                'academicHeadProfile',
                'advisorySections.program',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('role')) {
            $query->where('role', $request->string('role')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('studentProfile', function ($sq) use ($search): void {
                        $sq->where('student_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('proctorProfile', function ($sq) use ($search): void {
                        $sq->where('employee_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('cashierProfile', function ($sq) use ($search): void {
                        $sq->where('employee_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('academicHeadProfile', function ($sq) use ($search): void {
                        $sq->where('employee_id', 'like', "%{$search}%");
                    })
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        return view('admin.users.index', [
            'users' => $query->paginate(15)->withQueryString(),
            'sections' => Section::with('program')->orderBy('year_level')->orderBy('section_code')->get(),
        ]);
    }

    public function createProctor(): View
    {
        return view('admin.users.create-proctor', [
            'sections' => Section::with('program')->orderBy('year_level')->orderBy('section_code')->get(),
        ]);
    }

    public function storeProctor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'employee_id' => ['required', 'string', 'max:50', 'unique:proctor_profiles,employee_id'],
            'department' => ['required', 'in:IT,Tourism and Hospitality,General Education,Business and Management,Arts and Sciences,Senior High'],
            'advisory_section_id' => ['nullable', 'exists:sections,id'],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => 'proctor',
                'status' => 'active',
            ]);

            ProctorProfile::create([
                'user_id' => $user->id,
                'employee_id' => $validated['employee_id'],
                'department' => $validated['department'],
            ]);

            if (! empty($validated['advisory_section_id'])) {
                Section::whereKey($validated['advisory_section_id'])->update(['proctor_id' => $user->id]);
            }
        });

        return redirect()->route('admin.users.index')->with('status', 'Proctor account created successfully.');
    }

    public function createCashier(): View
    {
        return view('admin.users.create-cashier');
    }

    public function storeCashier(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'employee_id' => ['required', 'string', 'max:50', 'unique:cashier_profiles,employee_id'],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => 'cashier',
                'status' => 'active',
            ]);

            CashierProfile::create([
                'user_id' => $user->id,
                'employee_id' => $validated['employee_id'],
            ]);
        });

        return redirect()->route('admin.users.index')->with('status', 'Cashier account created successfully.');
    }

    public function createAcademicHead(): View
    {
        return view('admin.users.create-academic-head');
    }

    public function storeAcademicHead(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'employee_id' => ['required', 'string', 'max:50', 'unique:academic_head_profiles,employee_id'],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => 'academic_head',
                'status' => 'active',
            ]);

            AcademicHeadProfile::create([
                'user_id' => $user->id,
                'employee_id' => $validated['employee_id'],
            ]);
        });

        return redirect()->route('admin.users.index')->with('status', 'Academic head account created successfully.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,active,deactivated,archived'],
        ]);

        if ($user->id === $request->user()->id && $validated['status'] !== 'active') {
            return back()->withErrors(['status' => 'You cannot deactivate or archive your own admin account.']);
        }

        $user->update(['status' => $validated['status']]);

        return back()->with('status', 'User status updated.');
    }

    public function sendReset(User $user): RedirectResponse
    {
        $result = Password::sendResetLink(['email' => $user->email]);

        if ($result !== Password::RESET_LINK_SENT) {
            return back()->withErrors(['email' => 'Unable to send reset link right now.']);
        }

        return back()->with('status', 'Password reset link sent to user email.');
    }

    public function updateAdvisorySection(Request $request, User $user): RedirectResponse
    {
        if (! $user->isProctor()) {
            return back()->withErrors(['advisory_section_id' => 'Only proctor accounts can be assigned advisory sections.']);
        }

        $validated = $request->validate([
            'advisory_section_id' => ['nullable', 'exists:sections,id'],
        ]);

        DB::transaction(function () use ($user, $validated): void {
            Section::where('proctor_id', $user->id)->update(['proctor_id' => null]);

            if (! empty($validated['advisory_section_id'])) {
                Section::whereKey($validated['advisory_section_id'])->update(['proctor_id' => $user->id]);
            }
        });

        return back()->with('status', 'Proctor advisory class updated.');
    }
}
