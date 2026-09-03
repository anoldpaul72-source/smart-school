<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mark;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;

class MarkController extends Controller
{
    public function index(Request $request)
    {
        $subjects = Subject::orderBy('subject_name')->get();
        $query = Mark::with(['student', 'subject']);

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('term')) {
            $query->where('term', $request->term);
        }

        $marks = $query->orderBy('created_at', 'desc')->paginate(25);
        return view('marks.index', compact('marks', 'subjects'));
    }

    public function create(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $schoolName = $user->school_name;

        $subjects = Subject::orderBy('subject_name')->get();
        $selectedClass = $request->get('class_name');
        $selectedSubject = $request->get('subject_id');

        $students = collect();
        if ($selectedClass) {
            $studentsQuery = Student::where('class_name', $selectedClass);
            if ($schoolName && !$user->isAdmin()) {
                $studentsQuery->where('school_name', $schoolName);
            }
            $students = $studentsQuery->orderBy('student_name')->get();
        }

        return view('marks.create', compact('subjects', 'students', 'selectedClass', 'selectedSubject'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_id' => 'required',
            'term' => 'required',
            'year' => 'required',
            'exam_date' => 'required|date',
            'scores' => 'required|array',
        ]);

        $recordedCount = 0;
        foreach ($request->scores as $studentId => $score) {
            if ($score !== null && $score !== '') {
                Mark::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $request->subject_id,
                        'term' => $request->term,
                        'year' => $request->year,
                    ],
                    [
                        'score' => (float) $score,
                        'exam_date' => $request->exam_date,
                        'recorded_by' => Auth::id(),
                    ]
                );
                $recordedCount++;
            }
        }

        return redirect()->route('marks.index')->with('success', "Alama za wanafunzi {$recordedCount} zimehifadhiwa!");
    }

    public function edit(string $id)
    {
        $mark = Mark::with(['student', 'subject'])->findOrFail($id);
        return view('marks.edit', compact('mark'));
    }

    public function update(Request $request, string $id)
    {
        $mark = Mark::findOrFail($id);
        $request->validate([
            'score' => 'required|numeric|min:0|max:100',
            'term' => 'required|string',
            'year' => 'required|numeric',
        ]);

        $mark->update([
            'score' => (float) $request->score,
            'term' => $request->term,
            'year' => $request->year,
        ]);

        return redirect()->route('marks.index')->with('success', 'Alama zimehaririwa kikamilifu!');
    }

    public function showUploadForm()
    {
        $subjects = Subject::orderBy('subject_name')->get();
        return view('marks.upload', compact('subjects'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'subject_id' => 'required',
            'term' => 'required',
            'year' => 'required',
            'exam_date' => 'required|date',
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle);

        $inserted = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 2) {
                $regNumber = trim($row[0]);
                $score = trim($row[1]);

                $student = Student::where('reg_number', $regNumber)->first();
                if ($student && is_numeric($score)) {
                    Mark::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'subject_id' => $request->subject_id,
                            'term' => $request->term,
                            'year' => $request->year,
                        ],
                        [
                            'score' => (float) $score,
                            'exam_date' => $request->exam_date,
                            'recorded_by' => Auth::id(),
                        ]
                    );
                    $inserted++;
                }
            }
        }
        fclose($handle);

        return redirect()->route('marks.index')->with('success', "Alama za wanafunzi {$inserted} zimeingizwa kikamilifu!");
    }
}
