@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Usimamizi Wa Matokeo Na Alama</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Orodha Ya Alama Za Mitihani Za Wanafunzi Zilizohifadhiwa</p>
    </div>
    <div style="display:flex; gap:0.75rem;">
        <a href="{{ route('marks.upload.form') }}" class="btn btn-secondary"><i class="fa-solid fa-file-csv"></i> Upload CSV</a>
        <a href="{{ route('marks.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Ingiza Alama</a>
    </div>
</div>

<div class="card">
    <form method="GET" action="{{ route('marks.index') }}" style="display:flex; gap:1rem; margin-bottom:1.25rem; flex-wrap:wrap;">
        <select name="subject_id" class="form-control" style="max-width:220px;">
            <option value="">-- Somo Zote --</option>
            @foreach($subjects as $sub)
            <option value="{{ $sub->id }}" {{ request('subject_id') == $sub->id ? 'selected' : '' }}>{{ $sub->subject_name }}</option>
            @endforeach
        </select>
        <select name="term" class="form-control" style="max-width:180px;">
            <option value="">-- Muhula Wote --</option>
            <option value="Term 1" {{ request('term') == 'Term 1' ? 'selected' : '' }}>Term 1</option>
            <option value="Term 2" {{ request('term') == 'Term 2' ? 'selected' : '' }}>Term 2</option>
        </select>
        <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mwanafunzi</th>
                    <th>Somo</th>
                    <th>Alama (Score)</th>
                    <th>Grade</th>
                    <th>Maoni (Remarks)</th>
                    <th>Muhula</th>
                    <th>Mwaka</th>
                    <th>Kitendo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($marks as $m)
                <tr>
                    <td><strong>{{ $m->student->student_name ?? 'N/A' }}</strong> <br><small style="color:var(--text-muted)">{{ $m->student->reg_number ?? '' }}</small></td>
                    <td>{{ $m->subject->subject_name ?? 'N/A' }}</td>
                    <td><span style="font-weight:800; font-size:1.05rem; color:var(--secondary);">{{ $m->score }}</span></td>
                    <td><span class="role-badge" style="background:rgba(16, 185, 129, 0.2); color:#6ee7b7; font-size:0.85rem;">{{ $m->grade }}</span></td>
                    <td>{{ $m->remarks }}</td>
                    <td>{{ $m->term }}</td>
                    <td>{{ $m->year }}</td>
                    <td>
                        <a href="{{ route('marks.edit', $m->id) }}" class="btn btn-secondary" style="padding:0.35rem 0.6rem; font-size:0.8rem;">
                            <i class="fa-solid fa-pen-to-square"></i> Hariri
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center; color:var(--text-muted);">Hakuna alama zilizopatikana.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1.25rem;">
        {{ $marks->appends(request()->query())->links() }}
    </div>
</div>
@endsection
