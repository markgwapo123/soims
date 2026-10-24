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

    <style>
        .soims-loading-overlay[hidden] { display: none !important; }
        .soims-loading-overlay {
            position: fixed; inset: 0; z-index: 99999;
            display: flex; align-items: center; justify-content: center;
            background: rgba(12, 31, 50, .42); padding: 20px;
        }
        .soims-loading-dialog {
            width: min(100%, 360px); background: #fff; color: #18324c;
            border-radius: 16px; padding: 32px 26px; text-align: center;
            box-shadow: 0 20px 65px rgba(0, 0, 0, .2);
        }
        .soims-loading-spinner {
            width: 42px; height: 42px; margin: 0 auto 18px;
            border: 4px solid #dbeaf5; border-top-color: #2189c3;
            border-radius: 50%; animation: soims-loading-spin .75s linear infinite;
        }
        .soims-loading-title { font-weight: 700; font-size: 18px; margin: 0 0 8px; }
        .soims-loading-description { font-size: 14px; margin: 0; color: #64748b; }
        @keyframes soims-loading-spin { to { transform: rotate(360deg); } }
        @media (prefers-reduced-motion: reduce) { .soims-loading-spinner { animation-duration: 2s; } }
    </style>
    <div class="soims-loading-overlay" id="soims-global-loading" hidden role="status" aria-live="polite" aria-label="Processing request">
        <div class="soims-loading-dialog">
            <div class="soims-loading-spinner" aria-hidden="true"></div>
            <p class="soims-loading-title" id="soims-loading-label">Processing...</p>
            <p class="soims-loading-description">Please wait while we complete your request.</p>
        </div>
    </div>
    <script>
    (function () {
        const overlay = document.getElementById('soims-global-loading');
        const label = document.getElementById('soims-loading-label');
        const actions = [
            [/sign\s*out|log\s*out/i, 'Signing out...'],
            [/log\s*in|sign\s*in/i, 'Signing in...'],
            [/register|create account/i, 'Creating account...'],
            [/approv/i, 'Approving request...'],
            [/reject|decline/i, 'Rejecting request...'],
            [/release|issu(e|ing)|prepare ris/i, 'Processing issuance...'],
            [/return/i, 'Processing return...'],
            [/receiv|add items|add stock|stock in/i, 'Adding stock to inventory...'],
            [/request|submit/i, 'Submitting request...'],
            [/delet|remov/i, 'Deleting record...'],
            [/deactiv/i, 'Deactivating record...'],
            [/activat/i, 'Activating record...'],
            [/updat|edit|save/i, 'Saving changes...'],
            [/search|filter/i, 'Loading results...'],
        ];
        function processLabel(element, fallback) {
            const explicit = element?.dataset?.loadingLabel;
            if (explicit) return explicit;
            const text = [
                element?.textContent || '',
                element?.getAttribute?.('aria-label') || '',
                element?.getAttribute?.('name') || '',
                element?.getAttribute?.('value') || ''
            ].join(' ').trim();
            const form = element?.closest?.('form');
            const action = (form?.getAttribute('action') || '').toLowerCase();
            for (const [pattern, message] of actions) {
                if (pattern.test(text)) return message;
            }
            for (const [pattern, message] of actions) {
                if (pattern.test(action)) return message;
            }
            return fallback;
        }
        function show(message) {
            label.textContent = message;
            overlay.hidden = false;
        }
        function hide() { overlay.hidden = true; }
        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
            if (!form.checkValidity()) return;
            if (form.target && form.target !== '_self') return;
            const submitter = event.submitter || form.querySelector('[type="submit"]');
            setTimeout(function () {
                if (!event.defaultPrevented) show(processLabel(submitter || form, 'Processing request...'));
            }, 0);
        });
        document.addEventListener('click', function (event) {
            const link = event.target.closest?.('a[href]');
            if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            if (link.target && link.target !== '_self') return;
            if (link.hasAttribute('download') || link.dataset.noLoading !== undefined) return;
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
            const destination = new URL(link.href, window.location.href);
            if (destination.origin !== window.location.origin || destination.href === window.location.href) return;
            setTimeout(function () {
                if (!event.defaultPrevented) show(processLabel(link, 'Loading page...'));
            }, 0);
        });
        window.addEventListener('pageshow', hide);
        window.addEventListener('pagehide', hide);
        window.SOIMSLoading = { show, hide };
    })();
    </script>
    </body>
</html>
