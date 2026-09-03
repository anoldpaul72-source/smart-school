@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Portal Ya Mzazi / Mwanafunzi</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Tazama Taarifa Za Matokeo, Mahudhurio Na Malipo Ya Ada Ya Watoto Wako</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-children"></i> Watoto Wako Waliosajiliwa</h2>
    </div>

    <div class="grid">
        @forelse($children as $child)
        <div class="stat-card" style="flex-direction:column; align-items:flex-start;">
            <div style="display:flex; justify-content:space-between; width:100%; align-items:center;">
                <div style="font-weight:700; font-size:1.1rem; color:#fff;">{{ $child->student_name }}</div>
                <span class="role-badge" style="background:rgba(6, 182, 212, 0.2); color:#6ee7b7;">{{ $child->class_name }}</span>
            </div>
            <div style="color:var(--text-muted); font-size:0.88rem; margin:0.4rem 0 1rem 0;">
                <div><i class="fa-solid fa-id-badge"></i> {{ $child->reg_number }}</div>
                <div><i class="fa-solid fa-school"></i> {{ $child->school_name }}</div>
            </div>
            <a href="{{ route('reports.student', $child->id) }}" class="btn btn-primary" style="width:100%; justify-content:center;">
                <i class="fa-solid fa-file-invoice"></i> Tazama Ripoti Ya Matokeo
            </a>
        </div>
        @empty
        <div style="padding:1.5rem; color:var(--text-muted); text-align:center; grid-column: 1/-1;">
            Hakuna taarifa za mtoto zilizounganishwa kwenye akaunti hii. Tafadhali wasiliana na Utawala Wa Shule.
        </div>
        @endforelse
    </div>
</div>
@endsection
