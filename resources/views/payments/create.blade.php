@extends('layouts.app')

@section('content')
<div class="card-header" style="border:none; margin-bottom:1.5rem; padding:0;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight:800;">Rekodi Malipo Ya Ada Mpya</h1>
        <p style="color: var(--text-muted); font-size:0.92rem; margin-top:0.2rem;">Weka Risiti Ya Ada Ya Mwanafunzi</p>
    </div>
    <a href="{{ route('payments.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Rudi Kwenye Orodha</a>
</div>

<div class="card" style="max-width:650px;">
    <form action="{{ route('payments.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label class="form-label">Mwanafunzi</label>
            <select name="student_id" class="form-control" required>
                <option value="">-- Chagua Mwanafunzi --</option>
                @foreach($students as $st)
                <option value="{{ $st->id }}">{{ $st->student_name }} ({{ $st->reg_number }} - {{ $st->class_name }})</option>
                @endforeach
            </select>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
            <div class="form-group">
                <label class="form-label">Kiasi Kilicholipwa (TSH)</label>
                <input type="number" name="amount_paid" class="form-control" placeholder="Mfano: 150000" min="0" step="500" required>
            </div>

            <div class="form-group">
                <label class="form-label">Namba Ya Risiti (Receipt No)</label>
                <input type="text" name="receipt_no" class="form-control" placeholder="Mfano: RCT-90821" required>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
            <div class="form-group">
                <label class="form-label">Tarehe Ya Malipo</label>
                <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Mwaka Wa Masomo</label>
                <input type="number" name="academic_year" class="form-control" value="{{ date('Y') }}" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:1rem;">
            <i class="fa-solid fa-floppy-disk"></i> Hifadhi Malipo
        </button>
    </form>
</div>
@endsection
