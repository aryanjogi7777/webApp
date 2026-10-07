@extends('layouts.app')

@section('title', 'Export accounts')
@section('breadcrumb', 'Export data')

@section('content')
    <div class="page-heading">
        <div>
            <div class="eyebrow">DATA TOOLS <span class="eyebrow-line"></span></div>
            <h1>Export account data</h1>
            <p class="page-intro">Download your account register in the format you need.</p>
        </div>
        <a href="{{ route('accounts.index') }}" class="button button-light"><span class="back-arrow">←</span> Back to account list</a>
    </div>

    <section class="module-card export-page-card">
        <div class="module-card-heading">
            <span class="module-icon module-icon-blue">↓</span>
            <div>
                <h2>Prepare your export</h2>
                <p>Optionally filter records, then choose a spreadsheet format.</p>
            </div>
        </div>

        <form class="export-page-form" action="{{ route('accounts.export.download') }}" method="GET">
            <div class="form-grid export-filters">
                <label class="field">
                    <span>Search accounts <small>(optional)</small></span>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Name, account ID, phone or address">
                </label>
                <label class="field">
                    <span>Category <small>(optional)</small></span>
                    <select name="category">
                        <option value="">All categories</option>
                        @foreach (['DS', 'NDS', 'AGRI', 'LT'] as $category)
                            <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>File format</span>
                    <select name="format" required>
                        <option value="xlsx">Excel workbook (.xlsx)</option>
                        <option value="csv">CSV spreadsheet (.csv)</option>
                    </select>
                </label>
            </div>
            <div class="export-file-note"><span aria-hidden="true">▤</span><span>Export includes account IDs, contact information, balances, category and JE progress.</span></div>
            <button class="button button-primary" type="submit">Download export <span aria-hidden="true">↓</span></button>
        </form>
    </section>

    <div class="module-tip"><span aria-hidden="true">ⓘ</span> Your export will use the selected filters. Leave them blank to include every account.</div>
@endsection
