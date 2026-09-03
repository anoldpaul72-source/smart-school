@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Usimamizi Wa Malipo Ya Ada</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Orodha Ya Risiti Na Rekodi Za Ada Zilizolipwa</p>
    </div>
    <a href="{{ route('payments.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Ingiza Malipo Mpya</a>
</div>

<div class="card">
    <form method="GET" action="{{ route('payments.index') }}" style="display:flex; gap:1rem; margin-bottom:1.25rem; flex-wrap:wrap;">
        <input type="text" name="search" class="form-control" placeholder="Tafuta jina la mwanafunzi au namba ya risiti..." value="{{ request('search') }}" style="max-width:320px;">
        <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-magnifying-glass"></i> Tafuta</button>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Namba Ya Risiti</th>
                    <th>Mwanafunzi</th>
                    <th>Kiasi Kilicholipwa (TSH)</th>
                    <th>Tarehe Ya Malipo</th>
                    <th>Mwaka Wa Masomo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $p)
                <tr>
                    <td><strong><i class="fa-solid fa-receipt"></i> {{ $p->receipt_no }}</strong></td>
                    <td>{{ $p->student->student_name ?? 'N/A' }} <br><small style="color:var(--text-muted)">{{ $p->student->reg_number ?? '' }}</small></td>
                    <td><span style="font-weight:800; color:var(--success); font-size:1.05rem;">TSH {{ number_format($p->amount_paid) }}</span></td>
                    <td>{{ $p->payment_date }}</td>
                    <td>{{ $p->academic_year }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:var(--text-muted);">Hakuna malipo yaliyorekodiwa.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1.25rem;">
        {{ $payments->appends(request()->query())->links() }}
    </div>
</div>
@endsection
