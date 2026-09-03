@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Usajili Wa Mwanafunzi Mpya</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Weka Taarifa Za Mwanafunzi Kwenye Mfumo</p>
    </div>
    <a href="{{ route('students.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Rudi Kwenye Orodha</a>
</div>

<div class="card" style="max-width:700px;">
    <form action="{{ route('students.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label class="form-label">Namba Ya Usajili (Registration Number)</label>
            <input type="text" name="reg_number" class="form-control" placeholder="Mfano: S1001/0001" value="{{ old('reg_number') }}" required>
        </div>

        <div class="form-group">
            <label class="form-label">Jina Buche/Kamili La Mwanafunzi</label>
            <input type="text" name="student_name" class="form-control" placeholder="Weka majina matatu ya mwanafunzi" value="{{ old('student_name') }}" required>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
            <div class="form-group">
                <label class="form-label">Jinsia (Sex)</label>
                <select name="sex" class="form-control" required>
                    <option value="M">Mume (M)</option>
                    <option value="F">Mke (F)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Darasa (Class)</label>
                <input type="text" name="class_name" class="form-control" placeholder="Mfano: Form 1, Form 2" value="{{ old('class_name') }}" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Jina La Shule</label>
            @if(Auth::user()->isAdmin())
            <select name="school_name" class="form-control" required>
                @foreach($schools as $sch)
                <option value="{{ $sch->school_name }}">{{ $sch->school_name }}</option>
                @endforeach
            </select>
            @else
            <input type="text" name="school_name" class="form-control" value="{{ Auth::user()->school_name }}" readonly>
            @endif
        </div>

        <div class="form-group">
            <label class="form-label">Mzazi / Mlezi (Parent Linkage - Optional)</label>
            <select name="parent_id" class="form-control">
                <option value="">-- Chagua Mzazi --</option>
                @foreach($parents as $p)
                <option value="{{ $p->id }}">{{ $p->username }} ({{ $p->email }})</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:1rem;">
            <i class="fa-solid fa-floppy-disk"></i> Hifadhi Mwanafunzi
        </button>
    </form>
</div>
@endsection
