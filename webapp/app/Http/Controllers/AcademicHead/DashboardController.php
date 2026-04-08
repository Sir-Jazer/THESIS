<?php

namespace App\Http\Controllers\AcademicHead;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('academic-head.dashboard');
    }
}
