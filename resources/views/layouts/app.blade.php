<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'SOIMS')</title>
        <link rel="stylesheet" href="{{ asset('css/soims.css') }}">
    </head>
    <body>
        @auth
            <div class="soims-shell soims-shell--app">
                <input class="soims-sidebar-toggle" id="soims-sidebar-toggle" type="checkbox" aria-label="Toggle navigation">
                <aside class="soims-sidebar">
                    <a class="soims-brand" href="{{ route('dashboard') }}">
                        <span class="soims-brand__mark">SO</span>
                        <span>
                            <span class="soims-brand__name">SOIMS</span>
                            <span class="soims-brand__detail">Supply Office Inventory</span>
                        </span>
                    </a>
                    <nav class="soims-sidebar__nav" aria-label="Primary navigation">
                        <p class="soims-sidebar__group">Workspace</p>
                        <a class="soims-sidebar__link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                        @if (auth()->user()->hasPermission('manage_inventory') || auth()->user()->hasPermission('submit_supply_requests') || auth()->user()->hasPermission('approve_supply_requests'))
                            <a class="soims-sidebar__link {{ request()->routeIs('stock-availability.*') ? 'is-active' : '' }}" href="{{ route('stock-availability.index') }}">Stock availability</a>
                        @endif
                        <a class="soims-sidebar__link {{ request()->routeIs('notifications.*') ? 'is-active' : '' }}" href="{{ route('notifications.index') }}">Notifications</a>
                        @if (auth()->user()->hasPermission('view_reports'))
                            <a class="soims-sidebar__link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}" href="{{ route('reports.index') }}">Reports</a>
                        @endif

                        @if (auth()->user()->hasPermission('submit_supply_requests') && ! auth()->user()->hasRole('supply_administrator'))
                            <p class="soims-sidebar__group">Requests</p>
                            <a class="soims-sidebar__link {{ request()->routeIs('requests.*') ? 'is-active' : '' }}" href="{{ route('requests.index') }}">My requests</a>
                            <a class="soims-sidebar__link" href="{{ route('requests.create') }}">New request</a>
                        @endif
                        @if (auth()->user()->hasPermission('approve_supply_requests'))
                            <p class="soims-sidebar__group">Approvals</p>
                            <a class="soims-sidebar__link {{ request()->routeIs('approvals.*') ? 'is-active' : '' }}" href="{{ route('approvals.index') }}">Review requests</a>
                        @endif

                        @if (auth()->user()->hasPermission('manage_catalog') || auth()->user()->hasPermission('manage_inventory') || auth()->user()->hasPermission('issue_supplies'))
                            <p class="soims-sidebar__group">Supply</p>
                            @if (auth()->user()->hasPermission('manage_catalog'))
                                <a class="soims-sidebar__link {{ request()->routeIs('admin.catalog.*') ? 'is-active' : '' }}" href="{{ route('admin.catalog.index') }}">Catalog</a>
                            @endif
                            @if (auth()->user()->hasPermission('manage_inventory'))
                                <a class="soims-sidebar__link {{ request()->routeIs('admin.inventory.*') ? 'is-active' : '' }}" href="{{ route('admin.inventory.index') }}">Inventory</a>
                                <a class="soims-sidebar__link {{ request()->routeIs('admin.inventory.receive*') ? 'is-active' : '' }}" href="{{ route('admin.inventory.receive') }}">Add Items</a>
                                <a class="soims-sidebar__link {{ request()->routeIs('physical-inventories.*') ? 'is-active' : '' }}" href="{{ route('physical-inventories.index') }}">Physical inventory</a>
                            @endif
                            @if (auth()->user()->hasPermission('issue_supplies'))
                                <a class="soims-sidebar__link {{ request()->routeIs('ris.*') ? 'is-active' : '' }}" href="{{ route('ris.index') }}">Prepare / release RIS</a>
                            @endif
                        @endif

                        @if (auth()->user()->hasPermission('manage_accountable_items'))
                            <p class="soims-sidebar__group">Accountability</p>
                            <a class="soims-sidebar__link {{ request()->routeIs('accountable-items.*') ? 'is-active' : '' }}" href="{{ route('accountable-items.index') }}">Accountable assets</a>
                            <a class="soims-sidebar__link {{ request()->routeIs('property-returns.*') ? 'is-active' : '' }}" href="{{ route('property-returns.index') }}">Property returns</a>
                        @endif
                        @if (auth()->user()->hasPermission('manage_users'))
                            <p class="soims-sidebar__group">Administration</p>
                            <a class="soims-sidebar__link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}">Manage users</a>
                        @endif
                        @if (auth()->user()->hasPermission('view_audit_logs'))
                            <p class="soims-sidebar__group">Governance</p>
                            <a class="soims-sidebar__link {{ request()->routeIs('audit-logs.*') ? 'is-active' : '' }}" href="{{ route('audit-logs.index') }}">Audit logs</a>
                        @endif
                    </nav>
                    <div class="soims-sidebar__account">
                        <span class="soims-sidebar__user">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="soims-button soims-button--secondary" type="submit">Sign out</button>
                        </form>
                    </div>
                </aside>
                <label class="soims-sidebar-backdrop" for="soims-sidebar-toggle" aria-label="Close navigation"></label>
                <div class="soims-workspace">
                    <header class="soims-topbar">
                        <label class="soims-mobile-menu" for="soims-sidebar-toggle">Menu</label>
                        <span class="soims-topbar__context">Supply Office</span>
                        <div class="soims-topbar__actions">@yield('header-actions')</div>
                    </header>
                    <main class="soims-main @yield('main-class')">
                        @yield('content')
                    </main>
                    <footer class="soims-footer">
                        <div class="soims-footer__inner">West Visayas State University - Himamaylan City Campus</div>
                    </footer>
                </div>
            </div>
        @else
            <div class="soims-shell">
                <header class="soims-header">
                    <div class="soims-header__inner">
                        <a class="soims-brand" href="{{ url('/') }}">
                            <span class="soims-brand__mark">SO</span>
                            <span><span class="soims-brand__name">SOIMS</span><span class="soims-brand__detail">Supply Office Inventory Management System</span></span>
                        </a>
                        @yield('header-actions')
                    </div>
                </header>
                <main class="soims-main @yield('main-class')">@yield('content')</main>
                <footer class="soims-footer"><div class="soims-footer__inner">West Visayas State University - Himamaylan City Campus</div></footer>
            </div>
        @endauth
    </body>
</html>
