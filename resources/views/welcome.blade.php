<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ContractorSpecialties — You know your trade. We'll help with the business part.</title>
    <meta name="description" content="Free, easy-to-use tools for real contractors. Create estimates, get paid, stay organized, and look professional without the headaches.">
    <style>
        :root {
            --ink:#12233a;
            --muted:#5f6b78;
            --orange:#d95b16;
            --orange-dark:#b9440b;
            --paper:#f7f3ed;
            --line:#dde2e7;
            --white:#fff;
            --shadow:0 18px 50px rgba(18,35,58,.12);
        }
        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{margin:0;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--ink);background:#fff}
        a{text-decoration:none;color:inherit}
        button,input{font:inherit}
        .container{width:min(1180px,calc(100% - 40px));margin:0 auto}
        .site-header{position:sticky;top:0;z-index:50;background:rgba(255,255,255,.95);backdrop-filter:blur(12px);border-bottom:1px solid rgba(18,35,58,.08)}
        .nav{min-height:96px;display:flex;align-items:center;justify-content:space-between;gap:18px}
        .brand{display:flex;align-items:center;min-width:0}
        .brand img{display:block;width:min(390px,36vw);height:auto;max-height:78px;object-fit:contain;object-position:left center}
        .nav-links{display:flex;align-items:center;gap:20px;font-weight:700;font-size:.88rem;white-space:nowrap}
        .nav-links a:hover{color:var(--orange)}
        .nav-actions{display:flex;align-items:center;gap:10px;white-space:nowrap}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:48px;padding:0 22px;border-radius:7px;border:1px solid transparent;font-weight:800;transition:.2s ease;cursor:pointer}
        .btn-outline{border-color:#8793a0;background:#fff}
        .btn-outline:hover{border-color:var(--ink)}
        .btn-primary{background:var(--orange);color:#fff;box-shadow:0 10px 24px rgba(217,91,22,.22)}
        .btn-primary:hover{background:var(--orange-dark);transform:translateY(-1px)}
        .hero{position:relative;overflow:hidden;min-height:710px;background:#d8c8b1}
        .hero-grid{position:relative;min-height:710px}
        .hero-copy{position:relative;z-index:2;min-height:710px;width:min(760px,58vw);padding:64px 42px 58px max(20px,calc((100vw - 1180px)/2));display:flex;flex-direction:column;justify-content:center}
        .eyebrow{font-size:.78rem;letter-spacing:.22em;text-transform:uppercase;font-weight:800;color:var(--muted);margin-bottom:18px}
        h1{font-size:clamp(3.7rem,6.1vw,6.4rem);line-height:.87;letter-spacing:-.055em;margin:0;max-width:760px;text-transform:uppercase;font-weight:950}
        h1 .accent{color:var(--orange);display:block;margin-top:8px}
        .lede{font-size:1.38rem;line-height:1.45;max-width:680px;margin:24px 0 26px;color:#27384b}
        .benefits{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin:14px 0 28px;max-width:720px}
        .benefit{padding-right:10px;border-right:1px solid var(--line)}
        .benefit:last-child{border-right:0}
        .benefit .icon{font-size:1.8rem;margin-bottom:8px}
        .benefit strong{display:block;text-transform:uppercase;font-size:.78rem;line-height:1.15}
        .hero-cta{display:flex;align-items:center;gap:18px;flex-wrap:wrap}
        .hero-cta .btn{font-size:1.2rem;min-height:64px;padding:0 30px}
        .subnote{font-size:.95rem;color:var(--muted)}
        .hero-visual{position:absolute;inset:0;z-index:0;overflow:hidden;background:#d8c8b1}
        .hero-visual img{width:100%;height:100%;object-fit:cover;object-position:center right;display:block}
        .hero-visual:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(255,255,255,.97) 0%,rgba(255,255,255,.90) 28%,rgba(255,255,255,.48) 48%,rgba(255,255,255,.08) 67%,rgba(255,255,255,0) 82%)}
        .value-strip{border-top:1px solid var(--line);border-bottom:1px solid var(--line);background:#fff}
        .value-grid{display:grid;grid-template-columns:repeat(4,1fr)}
        .value{display:flex;gap:14px;padding:24px 20px;border-right:1px solid var(--line)}
        .value:last-child{border-right:0}
        .value-icon{font-size:1.9rem}
        .value strong{display:block;text-transform:uppercase;font-size:.83rem;margin-bottom:3px}
        .value span{font-size:.88rem;color:var(--muted)}
        .section{padding:88px 0}
        .section.alt{background:var(--paper)}
        .section h2{font-size:clamp(2.2rem,4vw,4rem);letter-spacing:-.045em;line-height:.95;margin:0 0 18px;text-transform:uppercase}
        .section p.intro{font-size:1.2rem;color:var(--muted);max-width:720px;margin:0 0 36px}
        .cards{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
        .card{padding:28px;border:1px solid var(--line);border-radius:14px;background:#fff;box-shadow:0 8px 30px rgba(18,35,58,.04)}
        .card .kicker{color:var(--orange);font-weight:900;text-transform:uppercase;font-size:.75rem;letter-spacing:.12em}
        .card h3{font-size:1.35rem;margin:8px 0 10px}
        .card p{color:var(--muted);line-height:1.55;margin:0}
        .steps{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;counter-reset:step}
        .step{counter-increment:step;padding:30px;border-top:4px solid var(--ink);background:#fff}
        .step:before{content:"0" counter(step);font-weight:900;color:var(--orange);font-size:1.1rem}
        .step h3{font-size:1.55rem;margin:10px 0 8px}
        .step p{color:var(--muted);margin:0;line-height:1.55}
        .cta-band{background:var(--ink);color:#fff;padding:72px 0}
        .cta-band .inner{display:flex;justify-content:space-between;gap:30px;align-items:center}
        .cta-band h2{margin:0;font-size:clamp(2rem,4vw,4rem);line-height:.95;text-transform:uppercase;max-width:760px}
        .cta-band p{color:#cfd7df;font-size:1.05rem;max-width:640px}
        footer{padding:34px 0;color:var(--muted);font-size:.9rem}
        .footer-flex{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}
        @media (max-width:1050px){
            .nav-links{display:none}
            .hero-copy{width:min(720px,72vw);padding:64px 30px}
            .hero-visual{min-height:710px}
            .hero-visual:after{background:linear-gradient(90deg,rgba(255,255,255,.97) 0%,rgba(255,255,255,.88) 40%,rgba(255,255,255,.34) 66%,rgba(255,255,255,0) 86%)}
            .benefits{grid-template-columns:repeat(3,1fr)}
            .value-grid{grid-template-columns:repeat(2,1fr)}
            .value:nth-child(2){border-right:0}
            .cards,.steps{grid-template-columns:1fr}
        }
        @media (max-width:700px){
            .container{width:min(100% - 28px,1180px)}
            .nav{min-height:82px}
            .brand img{width:min(306px,68vw);max-height:65px}
            .nav-actions .btn-outline{display:none}
            .nav-actions .btn-primary{padding:0 14px;min-height:42px;font-size:.86rem}
            .hero{min-height:auto}
            .hero-grid{min-height:auto;padding-top:360px;background:rgba(255,255,255,.92)}
            .hero-copy{width:100%;min-height:auto;padding:42px 22px}
            .hero-visual{height:360px;min-height:0}
            .hero-visual:after{background:linear-gradient(180deg,rgba(255,255,255,0) 58%,rgba(255,255,255,.92) 100%)}
            h1{font-size:clamp(3rem,15vw,4.7rem)}
            .lede{font-size:1.1rem}
            .benefits{grid-template-columns:repeat(2,1fr)}
            .benefit{border-right:0;border-bottom:1px solid var(--line);padding-bottom:12px}
            .hero-visual{min-height:420px}
            .value-grid{grid-template-columns:1fr}.value{border-right:0;border-bottom:1px solid var(--line)}
            .value:last-child{border-bottom:0}
            .cta-band .inner{flex-direction:column;align-items:flex-start}
        }
    </style>
</head>
<body>
<header class="site-header">
    <div class="container nav">
        <a href="#top" class="brand" aria-label="ContractorSpecialties home">
            <img src="{{ asset('images/contractorspecialties-logo.webp') }}" alt="Contractor Specialties">
        </a>
        <nav class="nav-links" aria-label="Primary navigation">
            <a href="#features">Features</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#pricing">Pricing</a>
            <a href="#contractors">Real Contractors</a>
        </nav>
        <div class="nav-actions">
            <a class="btn btn-outline" href="{{ Route::has('login') ? route('login') : '#get-started' }}">Log In</a>
            <a class="btn btn-primary" href="{{ Route::has('register') ? route('register') : '#get-started' }}">Get Started Free →</a>
        </div>
    </div>
</header>

<main id="top">
    <section class="hero">
        <div class="hero-grid">
            <div class="hero-copy">
                <div class="eyebrow">Estimates. Payments. Customers. Growth.</div>
                <h1>You know your trade.<span class="accent">We'll help with the business part.</span></h1>
                <p class="lede">Free, easy-to-use tools built for real contractors. Create estimates, get paid, stay organized, and look professional — without the headaches.</p>

                <div class="benefits" aria-label="Key features">
                    <div class="benefit"><div class="icon">🧾</div><strong>Estimates & invoices</strong></div>
                    <div class="benefit"><div class="icon">💳</div><strong>Get paid online</strong></div>
                    <div class="benefit"><div class="icon">👥</div><strong>Customer management</strong></div>
                    <div class="benefit"><div class="icon">💬</div><strong>SMS messaging</strong></div>
                    <div class="benefit"><div class="icon">★</div><strong>Professional presence</strong></div>
                </div>

                <div class="hero-cta" id="get-started">
                    <a class="btn btn-primary" href="{{ Route::has('register') ? route('register') : '#features' }}">Get Started Free →</a>
                    <span class="subnote">No subscription. No credit card required.</span>
                </div>
            </div>
            <div class="hero-visual">
                <img src="{{ asset('images/end-of-the-day.webp') }}" alt="Contractor sitting on a framed structure with his dog at the end of a workday">
            </div>
        </div>
    </section>

    <section class="value-strip" aria-label="Why ContractorSpecialties">
        <div class="container value-grid">
            <div class="value"><div class="value-icon">👥</div><div><strong>Built for real contractors</strong><span>No corporate fluff. Just useful tools.</span></div></div>
            <div class="value"><div class="value-icon">🛡️</div><div><strong>Free core tools</strong><span>No subscription. Ever.</span></div></div>
            <div class="value"><div class="value-icon">💳</div><div><strong>Get paid faster</strong><span>Integrated online payments.</span></div></div>
            <div class="value"><div class="value-icon">📈</div><div><strong>Grow your business</strong><span>Look professional. Win more work.</span></div></div>
        </div>
    </section>

    <section class="section" id="features">
        <div class="container">
            <div class="eyebrow">Built around the work you actually do</div>
            <h2>Run the business without becoming a software guy.</h2>
            <p class="intro">ContractorSpecialties handles the business chores around the job so you can spend more time doing work customers will actually pay for.</p>
            <div class="cards">
                <article class="card"><div class="kicker">Price the job</div><h3>Professional estimates</h3><p>Build clear estimates, send them by email or text, revise them without losing history, and give the customer one place to approve the work.</p></article>
                <article class="card"><div class="kicker">Get the money</div><h3>Payments that make sense</h3><p>Collect deposits and balances online, record cash or checks, and always know what was paid and what is still owed.</p></article>
                <article class="card"><div class="kicker">While you're here…</div><h3>Quick Bill</h3><p>Customer asks for one more little job? Price it, collect it, then do it. No tiny accounts-receivable problem required.</p></article>
            </div>
        </div>
    </section>

    <section class="section alt" id="how-it-works">
        <div class="container">
            <div class="eyebrow">Simple on purpose</div>
            <h2>You do the work. We keep the business moving.</h2>
            <div class="steps">
                <div class="step"><h3>Send the price</h3><p>Create the estimate, send it to the customer, and handle revisions without digging through old text messages.</p></div>
                <div class="step"><h3>Win the job</h3><p>Your customer reviews the real scope and price, accepts it, and pays the deposit when one is required.</p></div>
                <div class="step"><h3>Finish and get paid</h3><p>Keep the job moving, collect the final balance, and leave behind a clean history you can actually find later.</p></div>
            </div>
        </div>
    </section>

    <section class="section" id="pricing">
        <div class="container">
            <div class="eyebrow">The two-dollar business model</div>
            <h2>Free software. We make money when you make money.</h2>
            <p class="intro">No monthly software subscription for the core tools. When an integrated online payment helps you get paid, ContractorSpecialties earns $2. Cash and checks can still be recorded manually.</p>
        </div>
    </section>

    <section class="cta-band" id="contractors">
        <div class="container inner">
            <div>
                <h2>Same hard work. Just less bullshit.</h2>
                <p>Start with an estimate, a customer, or the next little “while you're here” job. The software should make the business easier, not become another job.</p>
            </div>
            <a class="btn btn-primary" href="{{ Route::has('register') ? route('register') : '#top' }}">Get Started Free →</a>
        </div>
    </section>
</main>

<footer>
    <div class="container footer-flex">
        <div>© {{ date('Y') }} ContractorSpecialties</div>
        <div>You know your trade. We'll help with the business part.</div>
    </div>
</footer>
</body>
</html>