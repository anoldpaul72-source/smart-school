@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Ingiza Alama Za Somo Kwa Darasa</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Weka Scores Za Wanafunzi Kulingana Na Somo Na Darasa</p>
    </div>
    <a href="{{ route('marks.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Rudi Kwenye Orodha</a>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <form method="GET" action="{{ route('marks.create') }}" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
        <div>
            <label class="form-label">Darasa</label>
            <input type="text" name="class_name" class="form-control" placeholder="Mfano: Form 1" value="{{ $selectedClass }}" style="min-width:200px;">
        </div>

        <div>
            <label class="form-label">Somo</label>
            <select name="subject_id" class="form-control" style="min-width:220px;">
                <option value="">-- Chagua Somo --</option>
                @foreach($subjects as $sub)
                <option value="{{ $sub->id }}" {{ $selectedSubject == $sub->id ? 'selected' : '' }}>{{ $sub->subject_name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-users"></i> Onyesha Wanafunzi</button>
    </form>
</div>

@if($selectedClass && $selectedSubject)
<div class="card">
    <form action="{{ route('marks.store') }}" method="POST">
        @csrf
        <input type="hidden" name="subject_id" value="{{ $selectedSubject }}">

        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
            <div class="form-group">
                <label class="form-label">Muhula (Term)</label>
                <select name="term" class="form-control" required>
                    <option value="Term 1">Term 1</option>
                    <option value="Term 2">Term 2</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Mwaka</label>
                <input type="number" name="year" class="form-control" value="{{ date('Y') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Tarehe Ya Mtihani</label>
                <input type="date" name="exam_date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Reg Number</th>
                        <th>Jina La Mwanafunzi</th>
                        <th>Score / Alama (0 - 100)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $st)
                    <tr>
                        <td><strong>{{ $st->reg_number }}</strong></td>
                        <td>{{ $st->student_name }}</td>
                        <td>
                            <input type="number" name="scores[{{ $st->id }}]" class="form-control" style="max-width:140px;" placeholder="0 - 100" min="0" max="100" step="0.5">
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="text-align:center; color:var(--text-muted);">Hakuna wanafunzi waliopatikana kwa darasa hili.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->isNotEmpty())
        <button type="submit" class="btn btn-primary" style="margin-top:1.25rem; width:100%; justify-content:center;">
            <i class="fa-solid fa-floppy-disk"></i> Hifadhi Alama Za Mtihani
        </button>
        @endif
    </form>
</div>
@endif
@endsection
