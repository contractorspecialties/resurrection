{{-- ContractorSpecialties responsive application navigation --}}
<header class="cs-app-header" data-cs-nav>
    <div class="cs-app-header__inner">
        <a class="cs-brand" href="{{ route('dashboard') }}">ContractorSpecialties</a>

        <nav class="cs-desktop-nav" aria-label="Primary navigation">
            <a class="cs-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>

            <div class="cs-menu">
                <button class="cs-menu__trigger" type="button" aria-expanded="false">Work <span>⌄</span></button>
                <div class="cs-mega cs-mega--work">
                    <div>
                        <div class="cs-mega__eyebrow">Customers & jobs</div>
                        <a href="{{ route('customers.index') }}"><strong>Customers</strong><small>People, contact details and job history.</small></a>
                        <a href="{{ route('estimates.index') }}"><strong>Estimates</strong><small>Price work, revise it and get approval.</small></a>
                    </div>
                    <div>
                        <div class="cs-mega__eyebrow">Get paid</div>
                        <a href="{{ route('quick-bills.index') }}"><strong>Quick Bills</strong><small>Handle the little “while you're here” jobs.</small></a>
                        <a href="{{ route('recurring-services.index') }}"><strong>Recurring Services</strong><small>Repeat work without rebuilding the bill every time.</small></a>
                    </div>
                </div>
            </div>

            <div class="cs-menu">
                <button class="cs-menu__trigger" type="button" aria-expanded="false">Tools <span>⌄</span></button>
                <div class="cs-mega cs-mega--tools">
                    <div>
                        <div class="cs-mega__eyebrow">Business tools</div>
                        <a href="{{ route('price-book.index') }}"><strong>Price Book</strong><small>Reusable services, descriptions and prices.</small></a>
                        <a href="{{ route('messages.index') }}"><strong>Messages</strong><small>Customer email and SMS history.</small></a>
                        <a href="{{ route('payments.index') }}"><strong>Payments</strong><small>Stripe connection and payment activity.</small></a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="cs-header-actions">
            <div class="cs-quick">
                <button class="cs-btn cs-btn--primary cs-quick__trigger" type="button" aria-expanded="false">+ New</button>
                <div class="cs-quick__menu">
                    <a href="{{ route('estimates.create') }}">New estimate</a>
                    <a href="{{ route('quick-bills.create') }}">New Quick Bill</a>
                    <a href="{{ route('customers.create') }}">New customer</a>
                    <a href="{{ route('recurring-services.create') }}">New recurring service</a>
                </div>
            </div>

            <form class="cs-desktop-logout" method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="cs-btn cs-btn--quiet" type="submit">Log out</button>
            </form>

            <button class="cs-mobile-toggle" type="button" aria-label="Open navigation" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <div class="cs-mobile-panel" aria-hidden="true">
        <div class="cs-mobile-panel__inner">
            <a class="cs-mobile-dashboard" href="{{ route('dashboard') }}">Dashboard</a>

            <div class="cs-mobile-section">
                <div class="cs-mobile-section__title">Work</div>
                <a href="{{ route('customers.index') }}">Customers</a>
                <a href="{{ route('estimates.index') }}">Estimates</a>
                <a href="{{ route('quick-bills.index') }}">Quick Bills</a>
                <a href="{{ route('recurring-services.index') }}">Recurring Services</a>
            </div>

            <div class="cs-mobile-section">
                <div class="cs-mobile-section__title">Tools</div>
                <a href="{{ route('price-book.index') }}">Price Book</a>
                <a href="{{ route('messages.index') }}">Messages</a>
                <a href="{{ route('payments.index') }}">Payments</a>
            </div>

            <div class="cs-mobile-section">
                <div class="cs-mobile-section__title">Quick actions</div>
                <a href="{{ route('estimates.create') }}">+ New estimate</a>
                <a href="{{ route('quick-bills.create') }}">+ New Quick Bill</a>
                <a href="{{ route('customers.create') }}">+ New customer</a>
                <a href="{{ route('recurring-services.create') }}">+ New recurring service</a>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="cs-mobile-logout" type="submit">Log out</button>
            </form>
        </div>
    </div>
</header>

<style>
:root{--cs-bg:#fffdf9;--cs-text:#252525;--cs-muted:#706d67;--cs-border:#e7e1d8;--cs-soft:#f5f1ea;--cs-accent:#1f6b5b;--cs-accent-hover:#185548;--cs-shadow:0 18px 50px rgba(28,25,20,.12)}
.cs-app-header,.cs-app-header *{box-sizing:border-box}
.cs-app-header{position:sticky;top:0;z-index:1000;background:rgba(255,253,249,.97);border-bottom:1px solid var(--cs-border);color:var(--cs-text);backdrop-filter:blur(14px)}
.cs-app-header__inner{width:min(1440px,calc(100% - 32px));min-height:68px;margin:0 auto;display:flex;align-items:center;gap:28px}
.cs-brand{flex:0 0 auto;color:var(--cs-text);text-decoration:none;font-weight:900;letter-spacing:-.035em;font-size:1.05rem;white-space:nowrap}
.cs-desktop-nav{display:flex;align-items:center;gap:4px}
.cs-nav-link,.cs-menu__trigger{border:0;background:transparent;color:var(--cs-text);text-decoration:none;font:inherit;font-weight:750;padding:11px 13px;border-radius:10px;cursor:pointer}
.cs-nav-link:hover,.cs-menu__trigger:hover,.cs-nav-link.is-active{background:var(--cs-soft)}
.cs-menu{position:relative}
.cs-menu__trigger{display:flex;align-items:center;gap:6px}
.cs-menu__trigger span{transition:transform .18s ease}
.cs-menu.is-open .cs-menu__trigger span{transform:rotate(180deg)}
.cs-mega{position:absolute;top:calc(100% + 14px);left:0;display:none;gap:12px;min-width:560px;padding:14px;background:var(--cs-bg);border:1px solid var(--cs-border);border-radius:16px;box-shadow:var(--cs-shadow)}
.cs-mega--tools{min-width:330px}
.cs-menu.is-open .cs-mega{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}
.cs-menu.is-open .cs-mega--tools{grid-template-columns:1fr}
.cs-mega__eyebrow{margin:6px 8px 8px;color:var(--cs-muted);font-size:.72rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase}
.cs-mega a{display:block;padding:12px;border-radius:12px;color:var(--cs-text);text-decoration:none}
.cs-mega a:hover{background:var(--cs-soft)}
.cs-mega strong,.cs-mega small{display:block}
.cs-mega strong{margin-bottom:3px;font-size:.95rem}
.cs-mega small{color:var(--cs-muted);font-size:.82rem;line-height:1.35}
.cs-header-actions{margin-left:auto;display:flex;align-items:center;gap:8px}
.cs-btn{border:0;border-radius:10px;padding:10px 14px;font:inherit;font-weight:850;cursor:pointer}
.cs-btn--primary{background:var(--cs-accent);color:#fff}.cs-btn--primary:hover{background:var(--cs-accent-hover)}
.cs-btn--quiet{background:transparent;color:var(--cs-muted)}.cs-btn--quiet:hover{background:var(--cs-soft);color:var(--cs-text)}
.cs-quick{position:relative}
.cs-quick__menu{position:absolute;top:calc(100% + 12px);right:0;display:none;width:220px;padding:8px;background:var(--cs-bg);border:1px solid var(--cs-border);border-radius:14px;box-shadow:var(--cs-shadow)}
.cs-quick.is-open .cs-quick__menu{display:block}
.cs-quick__menu a{display:block;padding:11px 12px;border-radius:9px;color:var(--cs-text);text-decoration:none;font-weight:750}
.cs-quick__menu a:hover{background:var(--cs-soft)}
.cs-mobile-toggle,.cs-mobile-panel{display:none}

@media(max-width:820px){
.cs-app-header__inner{width:min(100% - 24px,1440px);min-height:62px}
.cs-desktop-nav,.cs-desktop-logout,.cs-quick{display:none}
.cs-mobile-toggle{width:42px;height:42px;display:inline-flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;padding:0;border:1px solid var(--cs-border);border-radius:11px;background:var(--cs-bg);cursor:pointer}
.cs-mobile-toggle span{display:block;width:19px;height:2px;border-radius:999px;background:var(--cs-text);transition:transform .18s ease,opacity .18s ease}
.cs-app-header.is-mobile-open .cs-mobile-toggle span:nth-child(1){transform:translateY(7px) rotate(45deg)}
.cs-app-header.is-mobile-open .cs-mobile-toggle span:nth-child(2){opacity:0}
.cs-app-header.is-mobile-open .cs-mobile-toggle span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}
.cs-mobile-panel{border-top:1px solid var(--cs-border);background:var(--cs-bg)}
.cs-app-header.is-mobile-open .cs-mobile-panel{display:block}
.cs-mobile-panel__inner{width:min(100% - 24px,720px);max-height:calc(100vh - 63px);margin:0 auto;padding:14px 0 28px;overflow-y:auto}
.cs-mobile-dashboard,.cs-mobile-section a,.cs-mobile-logout{width:100%;display:block;padding:13px 12px;border:0;border-radius:10px;background:transparent;color:var(--cs-text);text-align:left;text-decoration:none;font:inherit;font-weight:800;cursor:pointer}
.cs-mobile-dashboard{margin-bottom:8px;background:var(--cs-soft)}
.cs-mobile-section{padding:10px 0;border-top:1px solid var(--cs-border)}
.cs-mobile-section__title{padding:5px 12px 4px;color:var(--cs-muted);font-size:.72rem;font-weight:950;letter-spacing:.12em;text-transform:uppercase}
.cs-mobile-section a:hover,.cs-mobile-logout:hover{background:var(--cs-soft)}
.cs-mobile-logout{margin-top:8px;border-top:1px solid var(--cs-border);color:var(--cs-muted)}
}
</style>

<script>
document.addEventListener('DOMContentLoaded',()=>{const h=document.querySelector('[data-cs-nav]');if(!h)return;const ms=[...h.querySelectorAll('.cs-menu')],q=h.querySelector('.cs-quick'),t=h.querySelector('.cs-mobile-toggle'),p=h.querySelector('.cs-mobile-panel');const close=(except=null)=>{ms.forEach(m=>{if(m===except)return;m.classList.remove('is-open');m.querySelector('.cs-menu__trigger')?.setAttribute('aria-expanded','false')});if(q&&q!==except){q.classList.remove('is-open');q.querySelector('.cs-quick__trigger')?.setAttribute('aria-expanded','false')}};ms.forEach(m=>{const b=m.querySelector('.cs-menu__trigger');b?.addEventListener('click',e=>{e.stopPropagation();const o=!m.classList.contains('is-open');close(m);m.classList.toggle('is-open',o);b.setAttribute('aria-expanded',o?'true':'false')})});if(q){const b=q.querySelector('.cs-quick__trigger');b?.addEventListener('click',e=>{e.stopPropagation();const o=!q.classList.contains('is-open');close(q);q.classList.toggle('is-open',o);b.setAttribute('aria-expanded',o?'true':'false')})}t?.addEventListener('click',()=>{const o=!h.classList.contains('is-mobile-open');h.classList.toggle('is-mobile-open',o);t.setAttribute('aria-expanded',o?'true':'false');t.setAttribute('aria-label',o?'Close navigation':'Open navigation');p?.setAttribute('aria-hidden',o?'false':'true')});document.addEventListener('click',e=>{if(!h.contains(e.target))close()});document.addEventListener('keydown',e=>{if(e.key!=='Escape')return;close();h.classList.remove('is-mobile-open');t?.setAttribute('aria-expanded','false');p?.setAttribute('aria-hidden','true')});window.addEventListener('resize',()=>{if(innerWidth>820){h.classList.remove('is-mobile-open');t?.setAttribute('aria-expanded','false');p?.setAttribute('aria-hidden','true')}})});
</script>
