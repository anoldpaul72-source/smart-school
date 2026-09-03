<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\School;
use App\Models\Subject;
use App\Models\Timetable;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // Users Management
    public function usersIndex(Request $request)
    {
        $query = User::query();
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        $users = $query->orderBy('username')->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function userCreate()
    {
        $schools = School::orderBy('school_name')->get();
        return view('admin.users.create', compact('schools'));
    }

    public function userStore(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username',
            'email' => 'nullable|email',
            'password' => 'required|min:6',
            'role' => 'required|string',
            'school_name' => 'nullable|string',
        ]);

        User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'school_name' => $request->school_name,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Mtumiaji mpya amesajiliwa!');
    }

    public function userEdit(string $id)
    {
        $user = User::findOrFail($id);
        $schools = School::orderBy('school_name')->get();
        return view('admin.users.edit', compact('user', 'schools'));
    }

    public function userUpdate(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'username' => 'required|string',
            'role' => 'required|string',
            'school_name' => 'nullable|string',
        ]);

        $data = [
            'username' => $request->username,
            'role' => $request->role,
            'school_name' => $request->school_name,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Mtumiaji amehifadhiwa!');
    }

    public function userDestroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Mtumiaji amefutwa.');
    }

    // Schools Management
    public function schoolsIndex()
    {
        $schools = School::orderBy('school_name')->get();
        return view('admin.schools.index', compact('schools'));
    }

    public function schoolStore(Request $request)
    {
        $request->validate(['school_name' => 'required|string']);
        School::create(['school_name' => $request->school_name]);
        return back()->with('success', 'Shule imeongezwa kikamilifu!');
    }

    // Subjects Management
    public function subjectsIndex()
    {
        $subjects = Subject::orderBy('subject_name')->get();
        return view('admin.subjects.index', compact('subjects'));
    }

    public function subjectStore(Request $request)
    {
        $request->validate(['subject_name' => 'required|string']);
        Subject::create(['subject_name' => $request->subject_name]);
        return back()->with('success', 'Somo limeongezwa kikamilifu!');
    }

    // Timetables Management
    public function timetableIndex(Request $request)
    {
        $selectedClass = $request->get('class_name');
        $selectedSchool = $request->get('school_name');

        $query = Timetable::with(['subject', 'teacher']);
        if ($selectedClass) $query->where('class_name', $selectedClass);
        if ($selectedSchool) $query->where('school_name', $selectedSchool);

        $timetables = $query->get();
        $schools = School::orderBy('school_name')->get();
        $teachers = User::where('role', 'Teacher')->orWhere('role', 'Mwalimu')->get();
        $subjects = Subject::orderBy('subject_name')->get();

        return view('admin.timetable.index', compact('timetables', 'schools', 'teachers', 'subjects', 'selectedClass', 'selectedSchool'));
    }

    public function timetableStore(Request $request)
    {
        $request->validate([
            'school_name' => 'required',
            'class_name' => 'required',
            'day_of_week' => 'required',
            'period_number' => 'required',
            'time_slot' => 'required',
            'subject_id' => 'required',
            'teacher_id' => 'required',
        ]);

        Timetable::create($request->all());
        return back()->with('success', 'Ratiba imehifadhiwa kikamilifu!');
    }
}
