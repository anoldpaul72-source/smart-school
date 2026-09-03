@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Dashibodi Ya Mwalimu</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Usimamizi Wa Masomo, Mahudhurio Na Alama Za Wanafunzi</p>
    </div>
</div>

<div class="grid">
    <div class="card" style="margin-bottom:0;">
        <h2 class="card-title" style="margin-bottom:1rem;"><i class="fa-solid fa-pen-to-square" style="color:var(--primary);"></i> Ingiza Alama Za Masomo</h2>
        <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.25rem;">Weka alama za mitihani ya wanafunzi mmoja mmoja au upload faili la CSV.</p>
        <div style="display:flex; gap:0.75rem;">
            <a href="{{ route('marks.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Weka Alama</a>
            <a href="{{ route('marks.upload.form') }}" class="btn btn-secondary"><i class="fa-solid fa-file-csv"></i> Upload CSV</a>
        </div>
    </div>

    <div class="card" style="margin-bottom:0;">
        <h2 class="card-title" style="margin-bottom:1rem;"><i class="fa-solid fa-clipboard-user" style="color:var(--secondary);"></i> Mahudhurio Ya Kila Siku</h2>
        <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.25rem;">Chukua au kagua mahudhurio ya darasa lako la leo.</p>
        <div style="display:flex; gap:0.75rem;">
            <a href="{{ route('attendance.create') }}" class="btn btn-primary"><i class="fa-solid fa-check-double"></i> Chukua Mahudhurio</a>
            <a href="{{ route('attendance.index') }}" class="btn btn-secondary"><i class="fa-solid fa-list-check"></i> Kagua Daftari</a>
        </div>
    </div>
</div>

<div class="card" style="margin-top:1.5rem;">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-user-graduate"></i> Wanafunzi Wa Shule Yako ({{ Auth::user()->school_name ?? 'Shule' }})</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Namba Ya Usajili</th>
                    <th>Jina La Mwanafunzi</th>
                    <th>Jinsia</th>
                    <th>Darasa</th>
                    <th>Kitendo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $st)
                <tr>
                    <td><strong>{{ $st->reg_number }}</strong></td>
                    <td>{{ $st->student_name }}</td>
                    <td>{{ $st->sex }}</td>
                    <td>{{ $st->class_name }}</td>
                    <td>
                        <a href="{{ route('reports.student', $st->id) }}" class="btn btn-secondary" style="padding:0.35rem 0.75rem; font-size:0.8rem;">
                            <i class="fa-solid fa-id-card"></i> Ripoti Card
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:var(--text-muted);">Hakuna wanafunzi waliosajiliwa.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
