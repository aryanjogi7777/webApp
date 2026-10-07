@extends('layouts.app')

@section('title', 'Defaulting accounts')
@section('breadcrumb', 'Defaulting accounts')

@section('content')
    <div class="page-heading">
        <div>
            <div class="eyebrow">ACCOUNT MANAGEMENT <span class="eyebrow-line"></span></div>
            <h1>Defaulting accounts</h1>
            <p class="page-intro">A clear view of your accounts, balances and recovery progress.</p>
        </div>
        <a href="{{ route('accounts.create') }}" class="button button-primary"><span class="button-plus">+</span> Add account</a>
    </div>

    <section class="summary-grid" aria-label="Account summary">
        <article class="summary-card">
            <div class="summary-top"><span class="summary-icon icon-blue">▤</span><span class="summary-label">TOTAL ACCOUNTS</span></div>
            <div class="summary-value">{{ number_format($summary['total']) }}</div>
            <div class="summary-foot">Records in your register</div>
        </article>
        <article class="summary-card">
            <div class="summary-top"><span class="summary-icon icon-purple">₹</span><span class="summary-label">CLOSING BALANCE</span></div>
            <div class="summary-value">₹{{ number_format($summary['closing_total'], 0) }}</div>
            <div class="summary-foot">Total outstanding at close</div>
        </article>
        <article class="summary-card">
            <div class="summary-top"><span class="summary-icon icon-green">↗</span><span class="summary-label">AMOUNT PAID</span></div>
            <div class="summary-value">₹{{ number_format($summary['paid_total'], 0) }}</div>
            <div class="summary-foot">Payments recorded</div>
        </article>
        <article class="summary-card summary-card-accent">
            <div class="summary-top"><span class="summary-icon icon-amber">◷</span><span class="summary-label">EST. PENDING</span></div>
            <div class="summary-value">₹{{ number_format($summary['pending_total'], 0) }}</div>
            <div class="summary-foot">Balance after recorded payments</div>
        </article>
    </section>

    <section class="register-card">
        <div class="register-heading">
            <div>
                <h2>Account register</h2>
                <p>Search, review and manage your account data.</p>
            </div>
            <a href="{{ route('accounts.export', request()->only('search', 'category')) }}" class="button button-light">Export this list <span aria-hidden="true">→</span></a>
        </div>

        <form action="{{ route('accounts.index') }}" method="GET" class="filter-bar">
            <label class="search-field">
                <span class="search-icon" aria-hidden="true">⌕</span>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, account ID, phone...">
            </label>
            <label class="filter-select">
                <span class="sr-only">Filter by category</span>
                <select name="category">
                    <option value="">All categories</option>
                    <option value="DS" @selected(request('category') === 'DS')>DS</option>
                    <option value="NDS" @selected(request('category') === 'NDS')>NDS</option>
                    <option value="AGRI" @selected(request('category') === 'AGRI')>AGRI</option>
                    <option value="LT" @selected(request('category') === 'LT')>LT</option>
                </select>
            </label>
            <button class="button button-dark" type="submit">Apply filters</button>
            @if (request()->hasAny(['search', 'category']))
                <a class="clear-filter" href="{{ route('accounts.index') }}">Clear</a>
            @endif
        </form>

        <div class="table-wrap">
            <table class="account-table">
                <thead>
                    <tr>
                        <th class="number-col">#</th>
                        <th>ACCOUNT</th>
                        <th>ADDRESS</th>
                        <th class="amount-col">CLOSING BALANCE</th>
                        <th>CATEGORY</th>
                        <th>PROGRESS OF THE JE</th>
                        <th class="amount-col">PAY</th>
                        <th class="amount-col">PENDING</th>
                        <th class="actions-col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td class="row-number">{{ $accounts->firstItem() + $loop->index }}</td>
                            <td class="account-cell">
                                <strong>{{ $account->name }}</strong>
                                <span>{{ $account->account_id }}</span>
                                @if ($account->phone_number)
                                    <small>Phone · {{ $account->phone_number }}</small>
                                @endif
                                @if ($account->old_account_id)
                                    <small>Old ID · {{ $account->old_account_id }}</small>
                                @endif
                            </td>
                            <td class="address-cell" data-label="ADDRESS">{{ $account->address ?: '—' }}</td>
                            <td class="amount-cell" data-label="CLOSING BALANCE">₹{{ number_format((float) $account->closing_balance, 2) }}</td>
                            <td data-label="CATEGORY">
                                @if ($account->category)
                                    <span class="category-badge badge-{{ strtolower($account->category) }}">{{ $account->category }}</span>
                                @else
                                    <span class="muted-dash">—</span>
                                @endif
                            </td>
                            <td class="progress-cell" data-label="PROGRESS">{{ $account->progress ?: '—' }}</td>
                            <td class="amount-cell" data-label="PAY">{{ $account->paid_amount === null ? '—' : '₹'.number_format((float) $account->paid_amount, 2) }}</td>
                            <td class="amount-cell pending-cell" data-label="PENDING">₹{{ number_format($account->pending_amount, 2) }}</td>
                            <td class="actions-cell" data-label="ACTIONS">
                                <a class="icon-button" href="{{ route('accounts.edit', $account) }}" aria-label="Edit {{ $account->name }}" title="Edit record">✎</a>
                                <form action="{{ route('accounts.destroy', $account) }}" method="POST" data-confirm="Delete this account record? This cannot be undone.">
                                    @csrf
                                    @method('DELETE')
                                    <button class="icon-button delete-button" type="submit" aria-label="Delete {{ $account->name }}" title="Delete record">×</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <span class="empty-icon">▤</span>
                                    <strong>{{ request()->hasAny(['search', 'category']) ? 'No matching accounts' : 'Your register is ready' }}</strong>
                                    <span>{{ request()->hasAny(['search', 'category']) ? 'Try changing your search or filters.' : 'Add an account or import your spreadsheet to get started.' }}</span>
                                    @unless (request()->hasAny(['search', 'category']))
                                        <a href="{{ route('accounts.create') }}" class="button button-primary">Add your first account</a>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span>Showing <strong>{{ $accounts->firstItem() ?? 0 }}–{{ $accounts->lastItem() ?? 0 }}</strong> of <strong>{{ $accounts->total() }}</strong> accounts</span>
            {{ $accounts->links() }}
        </div>
    </section>
    <p class="data-note"><span>ⓘ</span> Pending is estimated as closing balance minus recorded payments, never below zero.</p>
@endsection
