<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $query = Student::query();

        if (!$user->isAdmin() && $user->school_name) {
            $query->where('school_name', $user->school_name);
        }

        if ($request->filled('class')) {
            $query->where('class_name', $request->class);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                  ->orWhere('reg_number', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('student_name')->paginate(20);
        return view('students.index', compact('students'));
    }

    public function create()
    {
        $schools = School::orderBy('school_name')->get();
        $parents = User::where('role', 'Parent')->orWhere('role', 'Mzazi')->orderBy('username')->get();
        return view('students.create', compact('schools', 'parents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'reg_number' => 'required|string',
            'student_name' => 'required|string',
            'sex' => 'required|in:M,F',
            'class_name' => 'required|string',
            'school_name' => 'required|string',
        ]);

        Student::create($request->all());

        return redirect()->route('students.index')->with('success', 'Mwanafunzi amesajiliwa kikamilifu!');
    }

    public function edit(string $id)
    {
        $student = Student::findOrFail($id);
        $schools = School::orderBy('school_name')->get();
        $parents = User::where('role', 'Parent')->orWhere('role', 'Mzazi')->orderBy('username')->get();
        return view('students.edit', compact('student', 'schools', 'parents'));
    }

    public function update(Request $request, string $id)
    {
        $student = Student::findOrFail($id);
        $request->validate([
            'reg_number' => 'required|string',
            'student_name' => 'required|string',
            'sex' => 'required|in:M,F',
            'class_name' => 'required|string',
            'school_name' => 'required|string',
        ]);

        $student->update($request->all());

        return redirect()->route('students.index')->with('success', 'Taarifa za mwanafunzi zimehaririwa!');
    }

    public function destroy(string $id)
    {
        $student = Student::findOrFail($id);
        $student->delete();
        return redirect()->route('students.index')->with('success', 'Mwanafunzi amefutwa.');
    }

    public function showUploadForm()
    {
        return view('students.upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'school_name' => 'required|string',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle);

        $inserted = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 3) {
                Student::create([
                    'reg_number' => trim($row[0]),
                    'student_name' => trim($row[1]),
                    'sex' => strtoupper(trim($row[2])),
                    'class_name' => isset($row[3]) ? trim($row[3]) : 'Form 1',
                    'school_name' => $request->school_name,
                ]);
                $inserted++;
            }
        }
        fclose($handle);

        return redirect()->route('students.index')->with('success', "Wanafunzi {$inserted} wameingizwa kikamilifu!");
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_template.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Namba Ya Usajili', 'Jina La Mwanafunzi', 'Jinsia (M/F)', 'Darasa']);
            fputcsv($file, ['S1001/0001', 'Amina Juma', 'F', 'Form 1']);
            fputcsv($file, ['S1001/0002', 'Baraka Ali', 'M', 'Form 1']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
