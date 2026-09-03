@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Chukua Mahudhurio Ya Darasa</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Weka Hali Ya Mahudhurio Ya Wanafunzi Kwa Siku Ya Leo</p>
    </div>
    <a href="{{ route('attendance.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Rudi Daftari</a>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <form method="GET" action="{{ route('attendance.create') }}" style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap;">
        <div>
            <label class="form-label">Tarehe</label>
            <input type="date" name="date" class="form-control" value="{{ $attendanceDate }}">
        </div>

        <div>
            <label class="form-label">Darasa</label>
            <input type="text" name="class_name" class="form-control" placeholder="Mfano: Form 1" value="{{ $selectedClass }}">
        </div>

        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-users"></i> Onyesha Wanafunzi</button>
    </form>
</div>

@if($selectedClass)
<div class="card">
    <form action="{{ route('attendance.store') }}" method="POST">
        @csrf
        <input type="hidden" name="class_name" value="{{ $selectedClass }}">
        <input type="hidden" name="attendance_date" value="{{ $attendanceDate }}">

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Reg Number</th>
                        <th>Jina La Mwanafunzi</th>
                        <th>Hali Ya Mahudhurio (Status)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $st)
                    <tr>
                        <td><strong>{{ $st->reg_number }}</strong></td>
                        <td>{{ $st->student_name }}</td>
                        <td>
                            <select name="status[{{ $st->id }}]" class="form-control" style="max-width:180px;">
                                <option value="Present">YUPO (Present)</option>
                                <option value="Absent">HAJAONEKANA (Absent)</option>
                            </select>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="text-align:center; color:var(--text-muted);">Hakuna wanafunzi waliosajiliwa kwenye darasa hili.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->isNotEmpty())
        <button type="submit" class="btn btn-primary" style="margin-top:1.25rem; width:100%; justify-content:center;">
            <i class="fa-solid fa-floppy-disk"></i> Hifadhi Mahudhurio
        </button>
        @endif
    </form>
</div>
@endif
@endsection
