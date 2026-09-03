@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Ripoti Kamili Ya Ufaulu Kwa Viongozi</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Matrix Ya Matokeo Ya Masomo Yote Na Wastani Wa Wanafunzi</p>
    </div>
    <button onclick="window.print()" class="btn btn-secondary"><i class="fa-solid fa-print"></i> Print Ripoti</button>
</div>

<div class="card">
    <form method="GET" action="{{ route('reports.leaders') }}" style="display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap;">
        <input type="text" name="class_name" class="form-control" placeholder="Filter Darasa (Mfano Form 1)" value="{{ $selectedClass }}" style="max-width:220px;">
        <select name="term" class="form-control" style="max-width:180px;">
            <option value="Term 1" {{ $selectedTerm == 'Term 1' ? 'selected' : '' }}>Term 1</option>
            <option value="Term 2" {{ $selectedTerm == 'Term 2' ? 'selected' : '' }}>Term 2</option>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Onyesha Matokeo</button>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Namba Usajili</th>
                    <th>Jina La Mwanafunzi</th>
                    <th>Darasa</th>
                    @foreach($subjects as $sub)
                    <th>{{ substr($sub->subject_name, 0, 5) }}</th>
                    @endforeach
                    <th>Jumla</th>
                    <th>Wastani</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $idx => $st)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td><strong>{{ $st->reg_number }}</strong></td>
                    <td>{{ $st->student_name }}</td>
                    <td>{{ $st->class_name }}</td>
                    @foreach($subjects as $sub)
                    @php
                        $mk = isset($st->marks_by_subject[$sub->id]) ? $st->marks_by_subject[$sub->id]->score : '-';
                    @endphp
                    <td>{{ $mk }}</td>
                    @endforeach
                    <td><strong style="color:var(--secondary);">{{ $st->total_score }}</strong></td>
                    <td><strong style="color:var(--success);">{{ $st->avg_score }}%</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ 6 + count($subjects) }}" style="text-align:center; color:var(--text-muted);">Hakuna matokeo kwa vigezo vilivyochaguliwa.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
