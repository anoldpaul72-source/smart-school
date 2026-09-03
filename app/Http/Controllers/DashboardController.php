<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\School;
use App\Models\User;
use App\Models\Subject;
use App\Models\Mark;
use App\Models\Attendance;
use App\Models\StudentPayment;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            $totalStudents = Student::count();
            $totalSchools = School::count();
            $totalTeachers = User::where('role', 'Teacher')->orWhere('role', 'Mwalimu')->count();
            $totalSubjects = Subject::count();
            return view('dashboard.admin', compact('totalStudents', 'totalSchools', 'totalTeachers', 'totalSubjects'));
        }

        if ($user->isLeader()) {
            $schoolName = $user->school_name;
            $query = Student::query();
            if ($schoolName) {
                $query->where('school_name', $schoolName);
            }
            $studentsCount = $query->count();

            $selectedTerm = $request->get('term', 'Term 1');
            $selectedYear = $request->get('year', date('Y'));

            $subjects = Subject::orderBy('subject_name')->get();
            $students = $query->with('marks')->get();

            return view('dashboard.leaders', compact('studentsCount', 'subjects', 'students', 'selectedTerm', 'selectedYear'));
        }

        if ($user->isTeacher()) {
            $students = Student::where('school_name', $user->school_name)->get();
            $subjects = Subject::all();
            return view('dashboard.teacher', compact('students', 'subjects'));
        }

        // Parent / Student role
        $children = Student::where('parent_id', $user->id)->get();
        if ($children->isEmpty()) {
            // fallback search by name if parent_id was username string
            $children = Student::where('parent_id', $user->username)->get();
        }

        return view('dashboard.parent', compact('children'));
    }
}
