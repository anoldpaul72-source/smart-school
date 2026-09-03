<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentPayment;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = StudentPayment::with('student');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                  ->orWhere('reg_number', 'like', "%{$search}%");
            })->orWhere('receipt_no', 'like', "%{$search}%");
        }

        $payments = $query->orderBy('payment_date', 'desc')->paginate(20);
        return view('payments.index', compact('payments'));
    }

    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $query = Student::query();

        if (!$user->isAdmin() && $user->school_name) {
            $query->where('school_name', $user->school_name);
        }

        $students = $query->orderBy('student_name')->get();
        return view('payments.create', compact('students'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required',
            'amount_paid' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'receipt_no' => 'required|string',
            'academic_year' => 'required|numeric',
        ]);

        StudentPayment::create([
            'student_id' => $request->student_id,
            'amount_paid' => (float) $request->amount_paid,
            'payment_date' => $request->payment_date,
            'receipt_no' => $request->receipt_no,
            'academic_year' => $request->academic_year,
            'recorded_by' => Auth::id(),
        ]);

        return redirect()->route('payments.index')->with('success', 'Malipo yameingizwa kikamilifu!');
    }
}
