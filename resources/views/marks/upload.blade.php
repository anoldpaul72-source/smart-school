@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Upload Alama Za Mtihani (CSV)</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Upload Score Sheet Za Wanafunzi Kutoka Kwenye CSV</p>
    </div>
    <a href="{{ route('marks.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Rudi Kwenye Orodha</a>
</div>

<div class="card" style="max-width:600px;">
    <form action="{{ route('marks.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label class="form-label">Somo</label>
            <select name="subject_id" class="form-control" required>
                <option value="">-- Chagua Somo --</option>
                @foreach($subjects as $sub)
                <option value="{{ $sub->id }}">{{ $sub->subject_name }}</option>
                @endforeach
            </select>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
            <div class="form-group">
                <label class="form-label">Muhula</label>
                <select name="term" class="form-control" required>
                    <option value="Term 1">Term 1</option>
                    <option value="Term 2">Term 2</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Mwaka</label>
                <input type="number" name="year" class="form-control" value="{{ date('Y') }}" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Tarehe Ya Mtihani</label>
            <input type="date" name="exam_date" class="form-control" value="{{ date('Y-m-d') }}" required>
        </div>

        <div class="form-group">
            <label class="form-label">Faili La CSV (.csv)</label>
            <input type="file" name="file" class="form-control" accept=".csv, .txt" required>
        </div>

        <div style="margin: 1rem 0; padding:1rem; background:rgba(255,255,255,0.04); border-radius:10px; font-size:0.85rem; color:var(--text-muted);">
            <strong><i class="fa-solid fa-circle-info"></i> Format Ya CSV:</strong>
            <p style="margin-top:0.3rem;">Column 1: Namba Ya Usajili Ya Mwanafunzi (Reg Number)<br>Column 2: Alama / Score (0 - 100)</p>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
            <i class="fa-solid fa-cloud-arrow-up"></i> Upload Alama Sasa
        </button>
    </form>
</div>
@endsection
