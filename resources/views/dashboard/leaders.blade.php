@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Dashibodi Ya Viongozi Na Ufaulu</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Taarifa Za Matokeo Na Ufaulu Wa Wanafunzi</p>
    </div>
    <a href="{{ route('reports.leaders') }}" class="btn btn-primary">
        <i class="fa-solid fa-chart-column"></i> Tazama Ripoti Kamili Ya Viongozi
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-graduation-cap"></i> Muhtasari Wa Matokeo</h2>
    </div>

    <form method="GET" action="{{ route('dashboard') }}" style="display:flex; gap:1rem; margin-bottom:1.25rem;">
        <select name="term" class="form-control" style="max-width:200px;">
            <option value="Term 1" {{ $selectedTerm == 'Term 1' ? 'selected' : '' }}>Muhula Wa Kwanza (Term 1)</option>
            <option value="Term 2" {{ $selectedTerm == 'Term 2' ? 'selected' : '' }}>Muhula Wa Pili (Term 2)</option>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Reg Number</th>
                    <th>Jina La Mwanafunzi</th>
                    <th>Darasa</th>
                    <th>Jumla Ya Alama</th>
                    <th>Wastani</th>
                    <th>Kitendo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $st)
                @php
                    $m = $st->marks->where('term', $selectedTerm);
                    $tot = $m->sum('score');
                    $avg = $m->count() > 0 ? round($tot / $m->count(), 2) : 0;
                @endphp
                <tr>
                    <td><strong>{{ $st->reg_number }}</strong></td>
                    <td>{{ $st->student_name }}</td>
                    <td>{{ $st->class_name }}</td>
                    <td><span style="font-weight:700; color:var(--secondary);">{{ $tot }}</span></td>
                    <td><span style="font-weight:700; color:var(--success);">{{ $avg }}%</span></td>
                    <td>
                        <a href="{{ route('reports.student', $st->id) }}" class="btn btn-secondary" style="padding:0.35rem 0.75rem; font-size:0.8rem;">
                            <i class="fa-solid fa-eye"></i> Ripoti
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; color:var(--text-muted);">Hakuna taarifa za wanafunzi kwa sasa.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
