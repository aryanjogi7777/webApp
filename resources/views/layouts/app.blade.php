<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Account Ledger') · Ledgerly</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-mark">L</span>
                <span><strong>Ledgerly</strong><small>RECOVERY DESK</small></span>
            </a>
            <div class="sidebar-modules">
                <div class="sidebar-group">
                    <div class="side-caption">WORKSPACE</div>
                    <nav class="sidebar-nav" aria-label="Workspace modules">
                        <a class="side-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                            <span class="side-icon">⌂</span> Dashboard
                        </a>
                        <a class="side-link {{ request()->routeIs('accounts.create', 'accounts.edit') ? 'active' : '' }}" href="{{ route('accounts.create') }}">
                            <span class="side-icon">＋</span> Create account
                        </a>
                        <a class="side-link {{ request()->routeIs('accounts.index') ? 'active' : '' }}" href="{{ route('accounts.index') }}">
                            <span class="side-icon">▤</span> Account list
                        </a>
                    </nav>
                </div>
                <div class="sidebar-group">
                    <div class="side-caption">DATA TOOLS</div>
                    <nav class="sidebar-nav" aria-label="Import and export modules">
                        <a class="side-link {{ request()->routeIs('accounts.import*') ? 'active' : '' }}" href="{{ route('accounts.import') }}">
                                <span class="side-icon">↑</span> Import sheet
                        </a>
                        <a class="side-link {{ request()->routeIs('accounts.export*') ? 'active' : '' }}" href="{{ route('accounts.export') }}">
                            <span class="side-icon">⇩</span> Export data
                        </a>
                    </nav>
                </div>
            </div>
            <div class="sidebar-note">
                <span class="status-dot"></span>
                <span><strong>All changes saved</strong><small>Your records are up to date</small></span>
            </div>
            <div class="sidebar-foot">DEFAULTING ACCOUNTS<br><span>Management workspace</span></div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="breadcrumbs">Workspace <span>/</span> <strong>@yield('breadcrumb', 'Accounts')</strong></div>
                <div class="profile-chip"><span class="profile-avatar">AD</span><span>Administrator</span></div>
            </header>
            <section class="page-content">
                @if (session('success'))
                    <div class="flash flash-success" role="status">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="flash flash-error" role="alert">
                        <strong>We couldn't complete that action.</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </section>
        </main>
    </div>
</body>
</html>
