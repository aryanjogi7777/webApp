@extends('layouts.app')

@php($editing = $account->exists)

@section('title', $editing ? 'Edit account' : 'Add account')
@section('breadcrumb', $editing ? 'Edit account' : 'Add account')

@section('content')
    <div class="page-heading form-heading">
        <div>
            <div class="eyebrow">ACCOUNT REGISTER <span class="eyebrow-line"></span></div>
            <h1>{{ $editing ? 'Edit account' : 'Add an account' }}</h1>
            <p class="page-intro">{{ $editing ? 'Update the account details and recovery progress.' : 'Enter the account details below to add it to your register.' }}</p>
        </div>
        <a href="{{ route('accounts.index') }}" class="button button-light"><span class="back-arrow">←</span> Back to register</a>
    </div>

    <form class="record-form" action="{{ $editing ? route('accounts.update', $account) : route('accounts.store') }}" method="POST">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif
        <section class="form-section">
            <div class="form-section-heading">
                <span class="form-step">01</span>
                <div><h2>Account details</h2><p>Basic identification and contact information.</p></div>
            </div>
            <div class="form-grid">
                <label class="field">
                    <span>Account ID <b>*</b></span>
                    <input name="account_id" value="{{ old('account_id', $account->account_id) }}" required maxlength="64" placeholder="e.g. 8660011000">
                    @error('account_id') <small class="field-error">{{ $message }}</small> @enderror
                </label>
                <label class="field">
                    <span>Old account ID</span>
                    <input name="old_account_id" value="{{ old('old_account_id', $account->old_account_id) }}" maxlength="64" placeholder="Previous account reference">
                    @error('old_account_id') <small class="field-error">{{ $message }}</small> @enderror
                </label>
                <label class="field field-full">
                    <span>Account holder name <b>*</b></span>
                    <input name="name" value="{{ old('name', $account->name) }}" required maxlength="255" placeholder="Full name or business name">
                    @error('name') <small class="field-error">{{ $message }}</small> @enderror
                </label>
                <label class="field field-full">
                    <span>Address</span>
                    <textarea name="address" rows="3" maxlength="5000" placeholder="Address, town, district...">{{ old('address', $account->address) }}</textarea>
                    @error('address') <small class="field-error">{{ $message }}</small> @enderror
                </label>
                <label class="field">
                    <span>Phone number</span>
                    <input type="tel" name="phone_number" value="{{ old('phone_number', $account->phone_number) }}" maxlength="32" inputmode="tel" placeholder="Mobile number">
                    @error('phone_number') <small class="field-error">{{ $message }}</small> @enderror
                </label>
            </div>
        </section>

        <section class="form-section">
            <div class="form-section-heading">
                <span class="form-step">02</span>
                <div><h2>Balance &amp; recovery</h2><p>Financial information and current follow-up status.</p></div>
            </div>
            <div class="form-grid">
                <label class="field">
                    <span>Closing balance (LPS) <b>*</b></span>
                    <div class="input-prefix"><span>₹</span><input type="number" name="closing_balance" value="{{ old('closing_balance', $account->closing_balance) }}" required min="0" step="0.01" placeholder="0.00"></div>
                    @error('closing_balance') <small class="field-error">{{ $message }}</small> @enderror
                </label>
                <label class="field">
                    <span>Category</span>
                    <select name="category">
                        <option value="">Select category</option>
                        <option value="DS" @selected(old('category', $account->category) === 'DS')>DS</option>
                        <option value="NDS" @selected(old('category', $account->category) === 'NDS')>NDS</option>
                        <option value="AGRI" @selected(old('category', $account->category) === 'AGRI')>AGRI</option>
                        <option value="LT" @selected(old('category', $account->category) === 'LT')>LT</option>
                    </select>
                    @error('category') <small class="field-error">{{ $message }}</small> @enderror
                </label>
                <label class="field">
                    <span>Amount paid</span>
                    <div class="input-prefix"><span>₹</span><input type="number" name="paid_amount" value="{{ old('paid_amount', $account->paid_amount) }}" min="0" step="0.01" placeholder="0.00"></div>
                    @error('paid_amount') <small class="field-error">{{ $message }}</small> @enderror
                </label>
                <label class="field field-full">
                    <span>Progress of the JE</span>
                    <textarea name="progress" rows="3" maxlength="5000" placeholder="Add case status, next steps or follow-up notes...">{{ old('progress', $account->progress) }}</textarea>
                    @error('progress') <small class="field-error">{{ $message }}</small> @enderror
                </label>
            </div>
        </section>

        <div class="form-actions">
            <a href="{{ route('accounts.index') }}" class="button button-light">Cancel</a>
            <button type="submit" class="button button-primary">{{ $editing ? 'Save changes' : 'Add account' }}</button>
        </div>
    </form>
@endsection
