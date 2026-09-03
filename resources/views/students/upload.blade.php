@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Upload Wanafunzi Kwa CSV</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Ingiza Orodha Kubwa Ya Wanafunzi Kwa Mara Moja Kutoka Kwenye Faili La Excel/CSV</p>
    </div>
    <a href="{{ route('students.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Rudi Kwenye Orodha</a>
</div>

<div class="card" style="max-width:600px;">
    <form action="{{ route('students.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label class="form-label">Chagua Faili La CSV (.csv)</label>
            <input type="file" name="file" class="form-control" accept=".csv, .txt" required>
        </div>

        <div class="form-group">
            <label class="form-label">Shule</label>
            <input type="text" name="school_name" class="form-control" value="{{ Auth::user()->school_name ?? 'School' }}" required>
        </div>

        <div style="margin: 1rem 0; padding:1rem; background:rgba(255,255,255,0.04); border-radius:10px; font-size:0.85rem; color:var(--text-muted);">
            <strong><i class="fa-solid fa-circle-info"></i> Maelekezo Ya CSV:</strong>
            <ul style="margin-left:1.2rem; margin-top:0.4rem;">
                <li>Faili lazima liwe na safu wima: Namba Ya Usajili, Jina La Mwanafunzi, Jinsia (M/F), Darasa.</li>
                <li>Unaweza kupakua <a href="{{ route('students.template') }}" style="color:var(--secondary);">Template Hapa</a>.</li>
            </ul>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
            <i class="fa-solid fa-cloud-arrow-up"></i> Upload Sasa
        </button>
    </form>
</div>
@endsection
