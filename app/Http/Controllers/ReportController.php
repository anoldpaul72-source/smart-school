<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Mark;
use App\Models\Subject;
use App\Models\Attendance;
use App\Models\StudentPayment;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function showStudentReport(Request $request, string $id)
    {
        $student = Student::with(['parent'])->findOrFail($id);
        $selectedTerm = $request->get('term', 'Term 1');
        $selectedYear = $request->get('year', date('Y'));

        $marks = Mark::with('subject')
            ->where('student_id', $student->id)
            ->where('term', $selectedTerm)
            ->where('year', (int)$selectedYear)
            ->get();

        $totalScore = $marks->sum('score');
        $averageScore = $marks->count() > 0 ? round($totalScore / $marks->count(), 2) : 0;

        $attendanceCount = Attendance::where('student_id', $student->id)->where('status', 'Present')->count();
        $totalPayments = StudentPayment::where('student_id', $student->id)->sum('amount_paid');

        return view('reports.student_card', compact(
            'student', 'marks', 'selectedTerm', 'selectedYear', 'totalScore', 'averageScore', 'attendanceCount', 'totalPayments'
        ));
    }

    public function showLeadersReport(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $schoolName = $user->school_name;

        $selectedClass = $request->get('class_name');
        $selectedTerm = $request->get('term', 'Term 1');
        $selectedYear = $request->get('year', date('Y'));

        $subjects = Subject::orderBy('subject_name')->get();
        $query = Student::query();

        if ($schoolName && !$user->isAdmin()) {
            $query->where('school_name', $schoolName);
        }

        if ($selectedClass) {
            $query->where('class_name', $selectedClass);
        }

        $students = $query->orderBy('student_name')->get();

        foreach ($students as $student) {
            $studentMarks = Mark::where('student_id', $student->id)
                ->where('term', $selectedTerm)
                ->where('year', (int)$selectedYear)
                ->get();

            $student->marks_by_subject = $studentMarks->keyBy('subject_id');
            $student->total_score = $studentMarks->sum('score');
            $student->avg_score = $studentMarks->count() > 0 ? round($student->total_score / $studentMarks->count(), 2) : 0;
        }

        return view('reports.leaders', compact('students', 'subjects', 'selectedClass', 'selectedTerm', 'selectedYear'));
    }
}
