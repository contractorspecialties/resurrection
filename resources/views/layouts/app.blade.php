<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ContractorSpecialties')</title>

    <style>
        :root{
            --ink:#12233a;
            --muted:#687482;
            --orange:#d95b16;
            --orange-dark:#b9440b;
            --paper:#f6f3ee;
            --line:#dde3e8;
            --white:#fff;
            --success:#176c45;

            --nav-bg:#fff;
            --nav-text:#24364a;
            --nav-muted:#687482;
            --nav-soft:#f3f5f7;
            --nav-shadow:0 18px 50px rgba(18,35,58,.14);
        }

        *{box-sizing:border-box}

        body{
            margin:0;
            font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            background:var(--paper);
            color:var(--ink);
        }

        a{text-decoration:none;color:inherit}
        button,input,textarea,select{font:inherit}

        .shell{
            width:min(1180px,calc(100% - 32px));
            margin:0 auto;
        }

        /*
        |--------------------------------------------------------------------------
        | App header / navigation
        |--------------------------------------------------------------------------
        */

        .topbar{
            background:rgba(255,255,255,.98);
            border-bottom:1px solid var(--line);
            position:sticky;
            top:0;
            z-index:1000;
            backdrop-filter:blur(12px);
        }

        .topbar-inner{
            min-height:82px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:24px;
        }

        .brand{
            flex:0 0 auto;
        }

        .brand img{
            display:block;
            width:min(300px,40vw);
            height:auto;
            max-height:58px;
            object-fit:contain;
            object-position:left center;
        }

        .desktop-nav{
            display:flex;
            align-items:center;
            gap:6px;
            margin-left:auto;
        }

        .nav-link,
        .menu-trigger,
        .logout-btn{
            border:0;
            background:transparent;
            padding:10px 12px;
            border-radius:9px;
            font-weight:800;
            color:#31445a;
            cursor:pointer;
        }

        .nav-link:hover,
        .menu-trigger:hover,
        .logout-btn:hover,
        .nav-link.active{
            background:var(--nav-soft);
        }

        .menu{
            position:relative;
        }

        .menu-trigger{
            display:flex;
            align-items:center;
            gap:6px;
        }

        .menu-trigger .chevron{
            transition:transform .18s ease;
        }

        .menu.open .menu-trigger .chevron{
            transform:rotate(180deg);
        }

        .mega-menu{
            position:absolute;
            top:calc(100% + 14px);
            left:0;
            display:none;
            min-width:560px;
            gap:14px;
            padding:14px;
            background:var(--nav-bg);
            border:1px solid var(--line);
            border-radius:16px;
            box-shadow:var(--nav-shadow);
        }

        .menu.open .mega-menu{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
        }

        .mega-menu.tools{
            min-width:330px;
        }

        .menu.open .mega-menu.tools{
            grid-template-columns:1fr;
        }

        .mega-heading{
            margin:6px 8px 8px;
            color:var(--nav-muted);
            font-size:.72rem;
            font-weight:900;
            letter-spacing:.12em;
            text-transform:uppercase;
        }

        .mega-menu a{
            display:block;
            padding:12px;
            border-radius:11px;
        }

        .mega-menu a:hover{
            background:var(--nav-soft);
        }

        .mega-menu strong,
        .mega-menu small{
            display:block;
        }

        .mega-menu strong{
            margin-bottom:3px;
            color:var(--ink);
            font-size:.95rem;
        }

        .mega-menu small{
            color:var(--nav-muted);
            font-size:.82rem;
            line-height:1.35;
        }

        .header-actions{
            display:flex;
            align-items:center;
            gap:8px;
        }

        .quick-menu{
            position:relative;
        }

        .quick-trigger{
            border:0;
            background:var(--orange);
            color:#fff;
            padding:10px 14px;
            border-radius:9px;
            font-weight:900;
            cursor:pointer;
        }

        .quick-trigger:hover{
            background:var(--orange-dark);
        }

        .quick-panel{
            position:absolute;
            top:calc(100% + 12px);
            right:0;
            display:none;
            width:220px;
            padding:8px;
            background:#fff;
            border:1px solid var(--line);
            border-radius:14px;
            box-shadow:var(--nav-shadow);
        }

        .quick-menu.open .quick-panel{
            display:block;
        }

        .quick-panel a{
            display:block;
            padding:11px 12px;
            border-radius:9px;
            font-weight:800;
            color:#31445a;
        }

        .quick-panel a:hover{
            background:var(--nav-soft);
        }

        .desktop-logout{
            margin:0;
        }

        .mobile-toggle,
        .mobile-panel{
            display:none;
        }

        /*
        |--------------------------------------------------------------------------
        | Existing app UI
        |--------------------------------------------------------------------------
        */

        main{padding:44px 0 72px}

        .page-head{
            display:flex;
            justify-content:space-between;
            gap:24px;
            align-items:flex-end;
            margin-bottom:28px;
        }

        .page-head h1{
            font-size:clamp(2rem,5vw,4rem);
            line-height:.95;
            margin:0;
            text-transform:uppercase;
            letter-spacing:-.045em;
        }

        .page-head p{
            margin:10px 0 0;
            color:var(--muted);
            font-size:1.08rem;
        }

        .card{
            background:#fff;
            border:1px solid var(--line);
            border-radius:16px;
            padding:26px;
            box-shadow:0 8px 30px rgba(18,35,58,.04);
        }

        .grid{display:grid;gap:20px}
        .grid-2{grid-template-columns:repeat(2,1fr)}
        .grid-3{grid-template-columns:repeat(3,1fr)}

        .btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:46px;
            padding:0 18px;
            border-radius:8px;
            border:1px solid transparent;
            font-weight:850;
            cursor:pointer;
        }

        .btn-primary{background:var(--orange);color:#fff}
        .btn-secondary{background:#fff;border-color:#9aa5b1;color:var(--ink)}
        .btn-danger{background:#fff;border-color:#d8b2ad;color:#8b2d24}

        .field{margin-bottom:18px}

        label{
            display:block;
            font-weight:800;
            margin-bottom:7px;
        }

        input,textarea,select{
            width:100%;
            border:1px solid #bdc7d0;
            border-radius:8px;
            background:#fff;
            padding:12px 13px;
            color:var(--ink);
        }

        textarea{
            min-height:120px;
            resize:vertical;
        }

        .help{
            font-size:.9rem;
            color:var(--muted);
            margin-top:6px;
        }

        .errors{
            background:#fff3f1;
            border:1px solid #edc5bf;
            color:#7a2b21;
            padding:14px 16px;
            border-radius:10px;
            margin-bottom:20px;
        }

        .status{
            background:#eef8f2;
            border:1px solid #b9dfc8;
            color:var(--success);
            padding:14px 16px;
            border-radius:10px;
            margin-bottom:20px;
            font-weight:750;
        }

        .muted{color:var(--muted)}

        .kicker{
            text-transform:uppercase;
            letter-spacing:.13em;
            font-weight:900;
            font-size:.76rem;
            color:var(--orange);
        }

        .big-number{
            font-size:2.7rem;
            font-weight:950;
            line-height:1;
        }

        .list-row{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:18px;
            padding:14px 0;
            border-bottom:1px solid var(--line);
        }

        .list-row:last-child{border-bottom:0}

        .empty{
            padding:30px;
            text-align:center;
            color:var(--muted);
        }

        .badge{
            display:inline-flex;
            align-items:center;
            padding:6px 9px;
            border-radius:999px;
            background:#eef1f4;
            font-size:.76rem;
            text-transform:uppercase;
            letter-spacing:.08em;
            font-weight:900;
        }

        .money{
            font-variant-numeric:tabular-nums;
            font-weight:900;
        }

        /*
        |--------------------------------------------------------------------------
        | Mobile navigation
        |--------------------------------------------------------------------------
        */

        @media(max-width:980px){
            .topbar-inner{
                min-height:70px;
            }

            .desktop-nav,
            .desktop-logout,
            .quick-menu{
                display:none;
            }

            .brand img{
                width:min(250px,58vw);
                max-height:48px;
            }

            .mobile-toggle{
                width:44px;
                height:44px;
                display:inline-flex;
                flex-direction:column;
                align-items:center;
                justify-content:center;
                gap:5px;
                padding:0;
                border:1px solid var(--line);
                border-radius:10px;
                background:#fff;
                cursor:pointer;
            }

            .mobile-toggle span{
                width:20px;
                height:2px;
                border-radius:999px;
                background:var(--ink);
                transition:transform .18s ease,opacity .18s ease;
            }

            .topbar.mobile-open .mobile-toggle span:nth-child(1){
                transform:translateY(7px) rotate(45deg);
            }

            .topbar.mobile-open .mobile-toggle span:nth-child(2){
                opacity:0;
            }

            .topbar.mobile-open .mobile-toggle span:nth-child(3){
                transform:translateY(-7px) rotate(-45deg);
            }

            .topbar.mobile-open .mobile-panel{
                display:block;
            }

            .mobile-panel{
                border-top:1px solid var(--line);
                background:#fff;
            }

            .mobile-panel-inner{
                width:min(720px,calc(100% - 24px));
                max-height:calc(100vh - 71px);
                margin:0 auto;
                padding:14px 0 28px;
                overflow-y:auto;
            }

            .mobile-dashboard,
            .mobile-section a,
            .mobile-logout{
                width:100%;
                display:block;
                padding:13px 12px;
                border:0;
                border-radius:10px;
                background:transparent;
                color:var(--ink);
                text-align:left;
                font:inherit;
                font-weight:850;
                cursor:pointer;
            }

            .mobile-dashboard{
                margin-bottom:8px;
                background:var(--nav-soft);
            }

            .mobile-section{
                padding:10px 0;
                border-top:1px solid var(--line);
            }

            .mobile-section-title{
                padding:5px 12px 4px;
                color:var(--muted);
                font-size:.72rem;
                font-weight:950;
                letter-spacing:.12em;
                text-transform:uppercase;
            }

            .mobile-section a:hover,
            .mobile-logout:hover{
                background:var(--nav-soft);
            }

            .mobile-logout{
                margin-top:8px;
                border-top:1px solid var(--line);
                color:var(--muted);
            }
        }

        @media(max-width:850px){
            .grid-2,.grid-3{grid-template-columns:1fr}
            .page-head{align-items:flex-start;flex-direction:column}
        }

        @media(max-width:620px){
            main{padding-top:28px}
        }
    </style>
</head>
<body>

<header class="topbar" data-app-nav>
    <div class="shell topbar-inner">
        <a class="brand" href="{{ auth()->check() && auth()->user()->onboarding_completed_at ? route('dashboard') : route('home') }}">
            <img src="{{ asset('images/contractorspecialties-logo.webp') }}" alt="ContractorSpecialties">
        </a>

        @auth
            @if(auth()->user()->onboarding_completed_at)
                <nav class="desktop-nav" aria-label="Primary navigation">
                    <a
                        class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}"
                    >
                        Dashboard
                    </a>

                    <div class="menu">
                        <button class="menu-trigger" type="button" aria-expanded="false">
                            Work <span class="chevron">⌄</span>
                        </button>

                        <div class="mega-menu">
                            <div>
                                <div class="mega-heading">Customers & jobs</div>

                                <a href="{{ route('customers.index') }}">
                                    <strong>Customers</strong>
                                    <small>People, contact details and job history.</small>
                                </a>

                                <a href="{{ route('estimates.index') }}">
                                    <strong>Estimates</strong>
                                    <small>Price work, revise it and get approval.</small>
                                </a>
                            </div>

                            <div>
                                <div class="mega-heading">Get paid</div>

                                <a href="{{ route('quick-bills.index') }}">
                                    <strong>Quick Bills</strong>
                                    <small>Handle the little “while you're here” jobs.</small>
                                </a>

                                <a href="{{ route('recurring-services.index') }}">
                                    <strong>Recurring Services</strong>
                                    <small>Repeat work without rebuilding the bill every time.</small>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="menu">
                        <button class="menu-trigger" type="button" aria-expanded="false">
                            Tools <span class="chevron">⌄</span>
                        </button>

                        <div class="mega-menu tools">
                            <div>
                                <div class="mega-heading">Business tools</div>

                                <a href="{{ route('price-book.index') }}">
                                    <strong>Price Book</strong>
                                    <small>Reusable services, descriptions and prices.</small>
                                </a>

                                <a href="{{ route('messages.index') }}">
                                    <strong>Messages</strong>
                                    <small>Customer email and SMS history.</small>
                                </a>

                                <a href="{{ route('payments.index') }}">
                                    <strong>Payments</strong>
                                    <small>Stripe connection and payment activity.</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </nav>

                <div class="header-actions">
                    <div class="quick-menu">
                        <button class="quick-trigger" type="button" aria-expanded="false">+ New</button>

                        <div class="quick-panel">
                            <a href="{{ route('estimates.create') }}">New Estimate</a>
                            <a href="{{ route('quick-bills.create') }}">New Quick Bill</a>
                            <a href="{{ route('customers.create') }}">New Customer</a>
                            <a href="{{ route('recurring-services.create') }}">New Recurring Service</a>
                        </div>
                    </div>

                    <form class="desktop-logout" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="logout-btn" type="submit">Log Out</button>
                    </form>

                    <button class="mobile-toggle" type="button" aria-label="Open navigation" aria-expanded="false">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            @else
                <div class="header-actions">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="logout-btn" type="submit">Log Out</button>
                    </form>
                </div>
            @endif
        @else
            <nav class="desktop-nav">
                <a class="nav-link" href="{{ route('login') }}">Log In</a>
                <a class="quick-trigger" href="{{ route('register') }}">Get Started Free</a>
            </nav>
        @endauth
    </div>

    @auth
        @if(auth()->user()->onboarding_completed_at)
            <div class="mobile-panel" aria-hidden="true">
                <div class="mobile-panel-inner">
                    <a class="mobile-dashboard" href="{{ route('dashboard') }}">Dashboard</a>

                    <div class="mobile-section">
                        <div class="mobile-section-title">Work</div>
                        <a href="{{ route('customers.index') }}">Customers</a>
                        <a href="{{ route('estimates.index') }}">Estimates</a>
                        <a href="{{ route('quick-bills.index') }}">Quick Bills</a>
                        <a href="{{ route('recurring-services.index') }}">Recurring Services</a>
                    </div>

                    <div class="mobile-section">
                        <div class="mobile-section-title">Tools</div>
                        <a href="{{ route('price-book.index') }}">Price Book</a>
                        <a href="{{ route('messages.index') }}">Messages</a>
                        <a href="{{ route('payments.index') }}">Payments</a>
                    </div>

                    <div class="mobile-section">
                        <div class="mobile-section-title">Quick actions</div>
                        <a href="{{ route('estimates.create') }}">+ New Estimate</a>
                        <a href="{{ route('quick-bills.create') }}">+ New Quick Bill</a>
                        <a href="{{ route('customers.create') }}">+ New Customer</a>
                        <a href="{{ route('recurring-services.create') }}">+ New Recurring Service</a>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="mobile-logout" type="submit">Log Out</button>
                    </form>
                </div>
            </div>
        @endif
    @endauth
</header>

<main>
    <div class="shell">
        @if(session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="errors">
                <strong>There’s something to fix:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
</main>

@stack('scripts')

<script>
document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('[data-app-nav]');

    if (!header) {
        return;
    }

    const menus = [...header.querySelectorAll('.menu')];
    const quickMenu = header.querySelector('.quick-menu');
    const mobileToggle = header.querySelector('.mobile-toggle');
    const mobilePanel = header.querySelector('.mobile-panel');

    const closeMenus = (except = null) => {
        menus.forEach((menu) => {
            if (menu === except) {
                return;
            }

            menu.classList.remove('open');
            menu.querySelector('.menu-trigger')?.setAttribute('aria-expanded', 'false');
        });

        if (quickMenu && quickMenu !== except) {
            quickMenu.classList.remove('open');
            quickMenu.querySelector('.quick-trigger')?.setAttribute('aria-expanded', 'false');
        }
    };

    menus.forEach((menu) => {
        const trigger = menu.querySelector('.menu-trigger');

        trigger?.addEventListener('click', (event) => {
            event.stopPropagation();

            const opening = !menu.classList.contains('open');

            closeMenus(menu);
            menu.classList.toggle('open', opening);
            trigger.setAttribute('aria-expanded', opening ? 'true' : 'false');
        });
    });

    if (quickMenu) {
        const trigger = quickMenu.querySelector('.quick-trigger');

        trigger?.addEventListener('click', (event) => {
            event.stopPropagation();

            const opening = !quickMenu.classList.contains('open');

            closeMenus(quickMenu);
            quickMenu.classList.toggle('open', opening);
            trigger.setAttribute('aria-expanded', opening ? 'true' : 'false');
        });
    }

    mobileToggle?.addEventListener('click', () => {
        const opening = !header.classList.contains('mobile-open');

        header.classList.toggle('mobile-open', opening);
        mobileToggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
        mobileToggle.setAttribute('aria-label', opening ? 'Close navigation' : 'Open navigation');
        mobilePanel?.setAttribute('aria-hidden', opening ? 'false' : 'true');
    });

    document.addEventListener('click', (event) => {
        if (!header.contains(event.target)) {
            closeMenus();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        closeMenus();

        header.classList.remove('mobile-open');
        mobileToggle?.setAttribute('aria-expanded', 'false');
        mobileToggle?.setAttribute('aria-label', 'Open navigation');
        mobilePanel?.setAttribute('aria-hidden', 'true');
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 980) {
            header.classList.remove('mobile-open');
            mobileToggle?.setAttribute('aria-expanded', 'false');
            mobilePanel?.setAttribute('aria-hidden', 'true');
        }
    });
});
</script>

</body>
</html>