@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Usimamizi Wa Ratiba Za Masomo</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Panga Na Kagua Ratiba Za Kazi Za Walimu Na Madarasa</p>
    </div>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 class="card-title" style="margin-bottom:1rem;"><i class="fa-solid fa-plus"></i> Ongeza Kipindi Kwenye Ratiba</h2>
    <form action="{{ route('admin.timetable.store') }}" method="POST">
        @csrf
        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1rem;">
            <div class="form-group">
                <label class="form-label">Shule</label>
                <select name="school_name" class="form-control" required>
                    @foreach($schools as $sch)
                    <option value="{{ $sch->school_name }}">{{ $sch->school_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Darasa</label>
                <input type="text" name="class_name" class="form-control" placeholder="Form 1" required>
            </div>
            <div class="form-group">
                <label class="form-label">Siku Ya Wiki</label>
                <select name="day_of_week" class="form-control" required>
                    <option value="Jumatatu">Jumatatu</option>
                    <option value="Jumanne">Jumanne</option>
                    <option value="Jumatano">Jumatano</option>
                    <option value="Alhamisi">Alhamisi</option>
                    <option value="Ijumaa">Ijumaa</option>
                </select>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap:1rem;">
            <div class="form-group">
                <label class="form-label">Namba Ya Kipindi</label>
                <input type="number" name="period_number" class="form-control" placeholder="1" min="1" max="10" required>
            </div>
            <div class="form-group">
                <label class="form-label">Muda (Time Slot)</label>
                <input type="text" name="time_slot" class="form-control" placeholder="08:00 AM - 08:40 AM" required>
            </div>
            <div class="form-group">
                <label class="form-label">Somo</label>
                <select name="subject_id" class="form-control" required>
                    @foreach($subjects as $sub)
                    <option value="{{ $sub->id }}">{{ $sub->subject_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Mwalimu</label>
                <select name="teacher_id" class="form-control" required>
                    @foreach($teachers as $t)
                    <option value="{{ $t->id }}">{{ $t->username }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:0.5rem;">
            <i class="fa-solid fa-floppy-disk"></i> Hifadhi Kipindi
        </button>
    </form>
</div>

<div class="card">
    <h2 class="card-title" style="margin-bottom:1rem;"><i class="fa-solid fa-calendar-days"></i> Ratiba Ya Masomo</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Siku</th>
                    <th>Kipindi</th>
                    <th>Muda</th>
                    <th>Darasa</th>
                    <th>Somo</th>
                    <th>Mwalimu</th>
                </tr>
            </thead>
            <tbody>
                @forelse($timetables as $tt)
                <tr>
                    <td><strong>{{ $tt->day_of_week }}</strong></td>
                    <td>Kipindi Cha {{ $tt->period_number }}</td>
                    <td>{{ $tt->time_slot }}</td>
                    <td>{{ $tt->class_name }}</td>
                    <td>{{ $tt->subject->subject_name ?? 'N/A' }}</td>
                    <td>{{ $tt->teacher->username ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; color:var(--text-muted);">Hakuna vipindi kwenye ratiba.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
