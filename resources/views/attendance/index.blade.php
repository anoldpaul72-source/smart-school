@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Daftari La Mahudhurio Ya Wanafunzi</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Kagua Mahudhurio Ya Kila Siku Kulingana Na Tarehe Na Darasa</p>
    </div>
    <a href="{{ route('attendance.create') }}" class="btn btn-primary"><i class="fa-solid fa-clipboard-user"></i> Chukua Mahudhurio Mpya</a>
</div>

<div class="card">
    <form method="GET" action="{{ route('attendance.index') }}" style="display:flex; gap:1rem; margin-bottom:1.25rem; flex-wrap:wrap;">
        <div>
            <label class="form-label">Tarehe</label>
            <input type="date" name="date" class="form-control" value="{{ $selectedDate }}">
        </div>
        <div>
            <label class="form-label">Darasa</label>
            <input type="text" name="class_name" class="form-control" placeholder="Mfano: Form 1" value="{{ $selectedClass }}">
        </div>
        <div style="align-self:flex-end;">
            <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tarehe</th>
                    <th>Mwanafunzi</th>
                    <th>Darasa</th>
                    <th>Hali Ya Mahudhurio</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                <tr>
                    <td>{{ $rec->attendance_date }}</td>
                    <td><strong>{{ $rec->student->student_name ?? 'N/A' }}</strong> <br><small style="color:var(--text-muted)">{{ $rec->student->reg_number ?? '' }}</small></td>
                    <td>{{ $rec->class_name }}</td>
                    <td>
                        @if(strtolower($rec->status) === 'present' || strtolower($rec->status) === 'yupo')
                        <span class="role-badge" style="background:rgba(16, 185, 129, 0.2); color:#6ee7b7;">YUPO (Present)</span>
                        @else
                        <span class="role-badge" style="background:rgba(239, 68, 68, 0.2); color:#fca5a5;">HAJAONEKANA (Absent)</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center; color:var(--text-muted);">Hakuna kumbukumbu za mahudhurio kwa tarehe na darasa hili.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
