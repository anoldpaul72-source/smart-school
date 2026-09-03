@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Dashibodi Ya Utawala (Admin Overview)</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Karibu Kwenye Mfumo Wa Usimamizi Wa Matokeo Ya Shule</p>
    </div>
</div>

<div class="grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #4f46e5, #6366f1);">
            <i class="fa-solid fa-user-graduate"></i>
        </div>
        <div>
            <div class="stat-val">{{ $totalStudents }}</div>
            <div class="stat-label">Wanafunzi Wote</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
            <i class="fa-solid fa-school"></i>
        </div>
        <div>
            <div class="stat-val">{{ $totalSchools }}</div>
            <div class="stat-label">Shule Zilizosajiliwa</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
            <i class="fa-solid fa-chalkboard-user"></i>
        </div>
        <div>
            <div class="stat-val">{{ $totalTeachers }}</div>
            <div class="stat-label">Walimu Waliosajiliwa</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <i class="fa-solid fa-book-open"></i>
        </div>
        <div>
            <div class="stat-val">{{ $totalSubjects }}</div>
            <div class="stat-label">Masomo Yaliyopo</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-bolt" style="color:var(--accent);"></i> Njia Za Haraka (Quick Actions)</h2>
    </div>
    <div style="display:flex; gap:1rem; flex-wrap:wrap;">
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-user-plus"></i> Sajili Mtumiaji Mpya
        </a>
        <a href="{{ route('students.create') }}" class="btn btn-secondary">
            <i class="fa-solid fa-user-graduate"></i> Sajili Mwanafunzi
        </a>
        <a href="{{ route('marks.create') }}" class="btn btn-secondary">
            <i class="fa-solid fa-pen"></i> Ingiza Alama/Matokeo
        </a>
        <a href="{{ route('attendance.create') }}" class="btn btn-secondary">
            <i class="fa-solid fa-clipboard-user"></i> Chukua Mahudhurio
        </a>
        <a href="{{ route('payments.create') }}" class="btn btn-secondary">
            <i class="fa-solid fa-money-bill-wave"></i> Rekodi Malipo Ada
        </a>
    </div>
</div>
@endsection
