@extends('layouts.app')

@section('title', 'Import accounts')
@section('breadcrumb', 'Import data')

@section('content')
    <div class="page-heading">
        <div>
            <div class="eyebrow">DATA TOOLS <span class="eyebrow-line"></span></div>
            <h1>Import account data</h1>
            <p class="page-intro">Bring your spreadsheet into the account register.</p>
        </div>
        <a href="{{ route('accounts.index') }}" class="button button-light"><span class="back-arrow">←</span> Back to account list</a>
    </div>

    <section class="module-card import-page-card">
        <div class="module-card-heading">
            <span class="module-icon module-icon-green">↑</span>
            <div>
                <h2>Upload a spreadsheet</h2>
                <p>Choose an Excel or CSV file to import account records.</p>
            </div>
        </div>

        <form class="import-page-form" action="{{ route('accounts.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label class="field">
                <span>Spreadsheet file <b>*</b></span>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required>
                <small class="module-field-hint">Accepted: .xlsx, .xls, .csv · Max 10 MB · Up to 5,000 rows</small>
                <small class="module-field-hint">If a local upload returns 413, stop <code>php artisan serve</code>, run <code>composer run serve:large-import</code>, then reconnect LocalTunnel to port 8000.</small>
                @error('file') <small class="field-error">{{ $message }}</small> @enderror
            </label>
            <button class="button button-primary" type="submit">Import records <span aria-hidden="true">→</span></button>
        </form>
    </section>

    <section class="module-card import-guide">
        <div class="module-card-heading">
            <span class="module-icon module-icon-blue">i</span>
            <div>
                <h2>Before you import</h2>
                <p>Your uploaded workbook follows the expected structure.</p>
            </div>
        </div>
        <div class="import-guide-grid">
            <div class="import-guide-item"><span class="guide-number">01</span><div><strong>Required columns</strong><span>ACCT_ID, NAME and CLOSING_BALANCE_LPS</span></div></div>
            <div class="import-guide-item"><span class="guide-number">02</span><div><strong>Optional columns</strong><span>Old account ID, address, phone, category, progress, pay, status and payment date</span></div></div>
            <div class="import-guide-item"><span class="guide-number">03</span><div><strong>Existing accounts</strong><span>Matching ACCT_ID records are updated. New IDs create new records.</span></div></div>
            <div class="import-guide-item"><span class="guide-number">04</span><div><strong>Automatic cleanup</strong><span>Phone is extracted from “PH No” in the address; swapped balance/category cells are corrected.</span></div></div>
        </div>
    </section>
@endsection
