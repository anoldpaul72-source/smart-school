<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->get('date', date('Y-m-d'));
        $selectedClass = $request->get('class_name');

        $query = Attendance::with('student')->where('attendance_date', $selectedDate);
        if ($selectedClass) {
            $query->where('class_name', $selectedClass);
        }

        $records = $query->get();
        return view('attendance.index', compact('records', 'selectedDate', 'selectedClass'));
    }

    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $selectedClass = $request->get('class_name');
        $attendanceDate = $request->get('date', date('Y-m-d'));

        $students = collect();
        if ($selectedClass) {
            $studentsQuery = Student::where('class_name', $selectedClass);
            if (!$user->isAdmin() && $user->school_name) {
                $studentsQuery->where('school_name', $user->school_name);
            }
            $students = $studentsQuery->orderBy('student_name')->get();
        }

        return view('attendance.create', compact('students', 'selectedClass', 'attendanceDate'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_name' => 'required',
            'attendance_date' => 'required|date',
            'status' => 'required|array',
        ]);

        $recorded = 0;
        foreach ($request->status as $studentId => $statusVal) {
            Attendance::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'attendance_date' => $request->attendance_date,
                ],
                [
                    'class_name' => $request->class_name,
                    'status' => $statusVal,
                    'marked_by' => Auth::id(),
                ]
            );
            $recorded++;
        }

        return redirect()->route('attendance.index', ['date' => $request->attendance_date, 'class_name' => $request->class_name])
            ->with('success', "Mahudhurio ya wanafunzi {$recorded} yamehifadhiwa!");
    }
}
