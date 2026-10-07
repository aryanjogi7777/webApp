@extends('layouts.app')

@section('title', 'Quick account lookup')
@section('breadcrumb', 'Quick lookup')

@section('content')
    <section class="lookup-hero">
        <div class="lookup-eyebrow"><span class="lookup-eyebrow-dot"></span> SATPAL JE · ACCOUNT DESK</div>
        <h1>Find an account.<br><span>Get the full picture.</span></h1>
        <p class="lookup-intro">Search by account number, account holder name or mobile number.</p>

        <form class="lookup-form" method="GET" action="{{ route('home') }}">
            <label class="lookup-input-wrap">
                <span class="lookup-search-icon" aria-hidden="true">⌕</span>
                <span class="sr-only">Account number, name or mobile number</span>
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Enter account no., name or phone..."
                    minlength="2"
                    maxlength="120"
                    autocomplete="off"
                    required
                >
                @if ($search !== '')
                    <a class="lookup-clear" href="{{ route('home') }}" aria-label="Clear search">×</a>
                @endif
            </label>
            <button class="button button-primary lookup-submit" type="submit">Find account <span aria-hidden="true">→</span></button>
        </form>
        <div class="lookup-hint"><span aria-hidden="true">↳</span> Tip: you can type the phone number written in the address too.</div>
    </section>

    @if ($accounts)
        <section class="lookup-results" aria-live="polite">
            <div class="lookup-results-heading">
                <div>
                    <div class="eyebrow">SEARCH RESULTS <span class="eyebrow-line"></span></div>
                    <h2>{{ number_format($accounts->total()) }} {{ \Illuminate\Support\Str::plural('account', $accounts->total()) }} found</h2>
                    <p>Showing matches for <strong>“{{ $search }}”</strong></p>
                </div>
                <a href="{{ route('accounts.index', ['search' => $search]) }}" class="button button-light">Open in register <span aria-hidden="true">↗</span></a>
            </div>

            @forelse ($accounts as $account)
                <article class="lookup-card">
                    <div class="lookup-card-top">
                        <div class="lookup-person">
                            <span class="lookup-person-icon" aria-hidden="true">{{ mb_strtoupper(mb_substr($account->name, 0, 1)) }}</span>
                            <div>
                                <h3>{{ $account->name }}</h3>
                                <div class="lookup-ids">
                                    <span>ACCT ID <strong>{{ $account->account_id }}</strong></span>
                                    @if ($account->old_account_id)
                                        <span>OLD ID <strong>{{ $account->old_account_id }}</strong></span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <a class="button button-light lookup-edit" href="{{ route('accounts.edit', $account) }}" aria-label="Edit {{ $account->name }}">Edit record <span aria-hidden="true">↗</span></a>
                    </div>

                    <div class="lookup-data-grid">
                        <div class="lookup-data-item">
                            <span>PHONE NUMBER</span>
                            @if ($account->phone_number)
                                <a href="tel:{{ $account->phone_number }}">{{ $account->phone_number }}</a>
                            @else
                                <strong class="lookup-missing">Not available</strong>
                            @endif
                        </div>
                        <div class="lookup-data-item">
                            <span>CLOSING BALANCE (LPS)</span>
                            <strong>₹{{ number_format((float) $account->closing_balance, 2) }}</strong>
                        </div>
                        <div class="lookup-data-item">
                            <span>AMOUNT PAID</span>
                            <strong>{{ $account->paid_amount === null ? '—' : '₹'.number_format((float) $account->paid_amount, 2) }}</strong>
                        </div>
                        <div class="lookup-data-item lookup-pending">
                            <span>EST. PENDING</span>
                            <strong>₹{{ number_format($account->pending_amount, 2) }}</strong>
                        </div>
                        <div class="lookup-data-item">
                            <span>CATEGORY</span>
                            <strong>{{ $account->category ?: '—' }}</strong>
                        </div>
                        <div class="lookup-data-item lookup-address">
                            <span>ADDRESS</span>
                            <strong>{{ $account->address ?: 'Not available' }}</strong>
                        </div>
                        <div class="lookup-data-item lookup-progress">
                            <span>PROGRESS OF THE JE</span>
                            <strong>{{ $account->progress ?: 'No progress recorded' }}</strong>
                        </div>
                    </div>
                </article>
            @empty
                <div class="lookup-empty">
                    <span class="empty-icon">⌕</span>
                    <strong>No account matched that search</strong>
                    <span>Check the spelling or try a different account number or phone number.</span>
                </div>
            @endforelse

            @if ($accounts->hasPages())
                <div class="table-footer lookup-pagination">
                    <span>Showing <strong>{{ $accounts->firstItem() }}–{{ $accounts->lastItem() }}</strong> of <strong>{{ $accounts->total() }}</strong> matches</span>
                    {{ $accounts->links() }}
                </div>
            @endif
        </section>
    @else
        <section class="lookup-empty-start">
            <span class="lookup-empty-icon">⌕</span>
            <div><strong>Your account details, in seconds.</strong><span>Enter any account number, name or phone above to see the complete record.</span></div>
            <a href="{{ route('accounts.index') }}">Browse all accounts <span aria-hidden="true">→</span></a>
        </section>
    @endif
@endsection
