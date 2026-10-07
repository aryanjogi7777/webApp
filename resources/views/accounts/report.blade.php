@extends('layouts.app')

@section('title', 'PDF payment report')
@section('breadcrumb', 'PDF reports')

@section('content')
    <div class="page-heading">
        <div>
            <div class="eyebrow">REPORTS <span class="eyebrow-line"></span></div>
            <h1>Filtered PDF report</h1>
            <p class="page-intro">Filter accounts by payment date, paid amount and recovery details, then download a PDF.</p>
        </div>
        <a href="{{ route('accounts.index') }}" class="button button-light"><span class="back-arrow">←</span> Back to account list</a>
    </div>

    <section class="module-card report-page-card">
        <div class="module-card-heading">
            <span class="module-icon module-icon-blue">▧</span>
            <div>
                <h2>Choose report filters</h2>
                <p>Leave a filter blank to include every value for that field.</p>
            </div>
        </div>

        <form class="report-page-form" action="{{ route('accounts.reports') }}" method="GET">
            <div class="form-grid report-filters">
                <label class="field field-full">
                    <span>Search <small>(optional)</small></span>
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, account ID, phone or address">
                </label>
                <label class="field">
                    <span>Payment date from</span>
                    <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                </label>
                <label class="field">
                    <span>Payment date to</span>
                    <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                </label>
                <label class="field">
                    <span>Minimum amount paid (₹)</span>
                    <input type="number" name="min_paid" value="{{ $filters['min_paid'] ?? '' }}" min="0" step="0.01" placeholder="No minimum">
                </label>
                <label class="field">
                    <span>Maximum amount paid (₹)</span>
                    <input type="number" name="max_paid" value="{{ $filters['max_paid'] ?? '' }}" min="0" step="0.01" placeholder="No maximum">
                </label>
                <label class="field">
                    <span>Recovery status</span>
                    <select name="status">
                        <option value="">All statuses</option>
                        @foreach (\App\Models\DefaultingAccount::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Category</span>
                    <select name="category">
                        <option value="">All categories</option>
                        @foreach (['DS', 'NDS', 'AGRI', 'LT'] as $category)
                            <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="report-actions">
                <button class="button button-dark" type="submit">Apply filters</button>
                <a class="button button-light" href="{{ route('accounts.reports') }}">Clear filters</a>
            </div>
        </form>
    </section>

    <section class="module-card report-summary">
        <div class="report-summary-heading">
            <div>
                <h2>{{ number_format($summary->total) }} {{ \Illuminate\Support\Str::plural('account', $summary->total) }} {{ $summary->total === 1 ? 'matches' : 'match' }}</h2>
                <p>Payment dates are attached to the amount currently recorded. Older imported payments without a date are not included in date-filtered results.</p>
            </div>
            <a class="button button-primary" href="{{ route('accounts.reports.download', $filters) }}">Download filtered PDF <span aria-hidden="true">↓</span></a>
        </div>
        <div class="report-totals">
            <div><span>CLOSING BALANCE</span><strong>₹{{ number_format((float) $summary->closing_total, 2) }}</strong></div>
            <div><span>AMOUNT PAID</span><strong>₹{{ number_format((float) $summary->paid_total, 2) }}</strong></div>
            <div><span>EST. PENDING</span><strong>₹{{ number_format((float) $summary->pending_total, 2) }}</strong></div>
        </div>
        @if ($preview->isNotEmpty())
            <div class="report-preview-list">
                <h3>Matching records preview</h3>
                @foreach ($preview as $account)
                    <div class="report-preview-row">
                        <span><strong>{{ $account->name }}</strong><small>{{ $account->account_id }}</small></span>
                        <span>₹{{ number_format((float) ($account->paid_amount ?? 0), 2) }} paid</span>
                        <span>{{ $account->payment_date?->format('d M Y') ?: 'No payment date' }}</span>
                        <span class="status-badge status-{{ $account->status }}">{{ $account->status_label }}</span>
                    </div>
                @endforeach
                @if ($summary->total > $preview->count())
                    <p class="report-preview-more">PDF includes all {{ number_format($summary->total) }} matching accounts.</p>
                @endif
            </div>
        @else
            <div class="report-empty">No accounts match these filters. Adjust the filters to prepare a report.</div>
        @endif
        <p class="report-payment-note">Payment date is a single date associated with the current paid amount—not a line-by-line installment history.</p>
    </section>
@endsection
