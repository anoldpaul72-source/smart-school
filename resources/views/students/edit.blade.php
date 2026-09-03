@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Hariri Taarifa Za Mwanafunzi</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Badilisha Taarifa Za {{ $student->student_name }}</p>
    </div>
    <a href="{{ route('students.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Rudi Kwenye Orodha</a>
</div>

<div class="card" style="max-width:700px;">
    <form action="{{ route('students.update', $student->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label class="form-label">Namba Ya Usajili</label>
            <input type="text" name="reg_number" class="form-control" value="{{ old('reg_number', $student->reg_number) }}" required>
        </div>

        <div class="form-group">
            <label class="form-label">Jina La Mwanafunzi</label>
            <input type="text" name="student_name" class="form-control" value="{{ old('student_name', $student->student_name) }}" required>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
            <div class="form-group">
                <label class="form-label">Jinsia</label>
                <select name="sex" class="form-control" required>
                    <option value="M" {{ $student->sex == 'M' ? 'selected' : '' }}>Mume (M)</option>
                    <option value="F" {{ $student->sex == 'F' ? 'selected' : '' }}>Mke (F)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Darasa</label>
                <input type="text" name="class_name" class="form-control" value="{{ old('class_name', $student->class_name) }}" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Shule</label>
            <input type="text" name="school_name" class="form-control" value="{{ old('school_name', $student->school_name) }}" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:1rem;">
            <i class="fa-solid fa-floppy-disk"></i> Hifadhi Mabadiliko
        </button>
    </form>
</div>
@endsection
