@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Ripoti Ya Matokeo Ya Mwanafunzi</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Official Academic Performance Report Card</p>
    </div>
    <button onclick="window.print()" class="btn btn-secondary"><i class="fa-solid fa-print"></i> Chapisha / Print PDF</button>
</div>

<div class="card" style="background:#fff; color:#0f172a; padding:2rem; border-radius:18px;">
    <!-- Header -->
    <div style="text-align:center; border-bottom:2px solid #e2e8f0; padding-bottom:1.25rem; margin-bottom:1.5rem;">
        <div style="font-size:1.5rem; font-weight:800; text-transform:uppercase; color:#1e1b4b;">{{ $student->school_name ?? 'SCHOOL RESULTS PORTAL' }}</div>
        <div style="font-size:1.1rem; font-weight:700; color:#475569; margin-top:0.3rem;">TAARIFA YA MAENDELEO YA MWANAFUNZI - {{ strtoupper($selectedTerm) }} ({{ $selectedYear }})</div>
    </div>

    <!-- Student Bio -->
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.25rem; margin-bottom:1.5rem; background:#f8fafc; padding:1.25rem; border-radius:12px; border:1px solid #e2e8f0;">
        <div>
            <div><strong>Jina La Mwanafunzi:</strong> {{ $student->student_name }}</div>
            <div style="margin-top:0.4rem;"><strong>Namba Ya Usajili:</strong> {{ $student->reg_number }}</div>
        </div>
        <div>
            <div><strong>Darasa:</strong> {{ $student->class_name }} | <strong>Jinsia:</strong> {{ $student->sex }}</div>
            <div style="margin-top:0.4rem;"><strong>Siku Zilizohudhuriwa:</strong> {{ $attendanceCount }} Siku</div>
        </div>
    </div>

    <!-- Marks Table -->
    <table class="table" style="color:#0f172a; margin-bottom:1.5rem;">
        <thead>
            <tr style="background:#f1f5f9;">
                <th style="color:#334155; border-bottom:2px solid #cbd5e1;">SOMO</th>
                <th style="color:#334155; border-bottom:2px solid #cbd5e1;">SCORE / ALAMA</th>
                <th style="color:#334155; border-bottom:2px solid #cbd5e1;">GRADE</th>
                <th style="color:#334155; border-bottom:2px solid #cbd5e1;">MAONI (REMARKS)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($marks as $m)
            <tr>
                <td style="color:#0f172a; border-bottom:1px solid #e2e8f0;"><strong>{{ $m->subject->subject_name ?? 'N/A' }}</strong></td>
                <td style="color:#0f172a; border-bottom:1px solid #e2e8f0; font-weight:700;">{{ $m->score }}</td>
                <td style="color:#0f172a; border-bottom:1px solid #e2e8f0;"><span style="font-weight:700; background:#e0e7ff; color:#3730a3; padding:0.2rem 0.6rem; border-radius:6px;">{{ $m->grade }}</span></td>
                <td style="color:#0f172a; border-bottom:1px solid #e2e8f0;">{{ $m->remarks }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align:center; color:#64748b; padding:1.5rem;">Hakuna alama zilizorekodiwa kwa muhula huu.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Summary Stats -->
    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1rem; text-align:center; background:#eef2ff; padding:1.25rem; border-radius:12px; font-weight:700;">
        <div>Jumla Ya Alama: <span style="color:#4338ca; font-size:1.2rem;">{{ $totalScore }}</span></div>
        <div>Wastani (Average): <span style="color:#059669; font-size:1.2rem;">{{ $averageScore }}%</span></div>
        <div>Ada Iliyolipwa: <span style="color:#0284c7; font-size:1.2rem;">TSH {{ number_format($totalPayments) }}</span></div>
    </div>
</div>
@endsection
