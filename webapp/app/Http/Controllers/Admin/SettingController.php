<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'setting' => AcademicSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:1st Semester,2nd Semester'],
            'exam_period' => ['required', 'in:Prelim,Midterm,Prefinals,Finals'],
        ]);

        $setting = AcademicSetting::current();

        if ($setting) {
            $setting->update($validated);
        } else {
            AcademicSetting::create($validated);
        }

        return back()->with('status', 'Academic timeline updated successfully.');
    }
}
