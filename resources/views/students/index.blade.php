@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Orodha Ya Wanafunzi</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Usimamizi Wa Wanafunzi Waliosajiliwa Kwenye Mfumo</p>
    </div>
    <div style="display:flex; gap:0.75rem;">
        <a href="{{ route('students.template') }}" class="btn btn-secondary"><i class="fa-solid fa-download"></i> Pakua Template CSV</a>
        <a href="{{ route('students.upload.form') }}" class="btn btn-secondary"><i class="fa-solid fa-file-csv"></i> Upload CSV</a>
        <a href="{{ route('students.create') }}" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Sajili Mwanafunzi</a>
    </div>
</div>

<div class="card">
    <form method="GET" action="{{ route('students.index') }}" style="display:flex; gap:1rem; margin-bottom:1.25rem; flex-wrap:wrap;">
        <input type="text" name="search" class="form-control" placeholder="Tafuta jina au namba ya usajili..." value="{{ request('search') }}" style="max-width:300px;">
        <input type="text" name="class" class="form-control" placeholder="Filter darasa..." value="{{ request('class') }}" style="max-width:200px;">
        <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-magnifying-glass"></i> Tafuta</button>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Reg Number</th>
                    <th>Jina La Mwanafunzi</th>
                    <th>Jinsia</th>
                    <th>Darasa</th>
                    <th>Shule</th>
                    <th>Kitendo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $st)
                <tr>
                    <td><strong>{{ $st->reg_number }}</strong></td>
                    <td>{{ $st->student_name }}</td>
                    <td>{{ $st->sex }}</td>
                    <td>{{ $st->class_name }}</td>
                    <td>{{ $st->school_name }}</td>
                    <td style="display:flex; gap:0.4rem;">
                        <a href="{{ route('reports.student', $st->id) }}" class="btn btn-secondary" style="padding:0.35rem 0.6rem; font-size:0.8rem;" title="Ripoti">
                            <i class="fa-solid fa-file-invoice"></i>
                        </a>
                        <a href="{{ route('students.edit', $st->id) }}" class="btn btn-secondary" style="padding:0.35rem 0.6rem; font-size:0.8rem;" title="Hariri">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <form action="{{ route('students.destroy', $st->id) }}" method="POST" onsubmit="return confirm('Una uhakika unataka kumfuta mwanafunzi huyu?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-logout" style="padding:0.35rem 0.6rem; font-size:0.8rem;" title="Futa">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; color:var(--text-muted);">Hakuna wanafunzi waliopatikana.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1.25rem;">
        {{ $students->appends(request()->query())->links() }}
    </div>
</div>
@endsection
