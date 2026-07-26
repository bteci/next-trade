<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ app(\App\Services\SettingsService::class)->get('site_name', 'NextTrade') }} — Elevate Your Trading</title>
    @php
        $settings  = app(\App\Services\SettingsService::class);
        $siteName  = $settings->get('site_name', 'NextTrade');
        $faviconUrl = $settings->get('site_logo_url', '');
    @endphp
    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl ?: asset('favicon.ico') }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        cyan: { 400:'#22d3ee', 500:'#06b6d4', 600:'#0891b2' }
                    },
                    fontFamily: { sans: ['Inter','system-ui','sans-serif'] }
                }
            }
        }
    </script>
    {{-- Collapse plugin must load before Alpine core --}}
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * { scrollbar-width: thin; scrollbar-color: #06b6d4 transparent; }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: #06b6d4; border-radius: 2px; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', system-ui, sans-serif; }
        section[id] { scroll-margin-top: 72px; }

        .glassmorphism {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .nav-glassmorphism {
            background: transparent;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            border-bottom: 1px solid transparent;
            transition: background 0.35s ease, border-color 0.35s ease, backdrop-filter 0.35s ease;
        }
        .nav-glassmorphism.nav-scrolled {
            background: rgba(3,7,18,0.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        /* Always opaque on mobile so the mobile menu is readable */
        @media (max-width: 1023px) {
            .nav-glassmorphism {
                background: rgba(3,7,18,0.88);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border-bottom: 1px solid rgba(255,255,255,0.06);
            }
        }

        /* ── CTA buttons: sheen sweep on hover ── */
        .cta-btn {
            position: relative;
            overflow: hidden;
            background: white;
            color: #030712;
            transition: all 0.2s ease;
            box-shadow: 0 0 30px rgba(6,182,212,0.2);
        }
        .cta-btn:hover {
            background: #ecfeff;
            box-shadow: 0 0 50px rgba(6,182,212,0.35);
            transform: translateY(-1px);
        }
        .cta-btn::after, .nt-btn-primary::after {
            content: '';
            position: absolute;
            top: 0; left: -75%;
            width: 45%; height: 100%;
            background: linear-gradient(105deg, transparent, rgba(6,182,212,0.28), transparent);
            transform: skewX(-20deg);
            transition: left 0.55s ease;
            pointer-events: none;
        }
        .cta-btn:hover::after, .nt-btn-primary:hover::after { left: 130%; }

        .nav-link {
            color: rgba(255,255,255,0.65);
            transition: color 0.15s ease;
            font-size: 14px;
        }
        .nav-link:hover { color: white; }

        [x-cloak] { display: none !important; }

        /* ── Scroll reveal ── */
        .reveal {
            opacity: 0;
            transform: translateY(26px);
            transition: opacity 0.8s cubic-bezier(0.22,1,0.36,1), transform 0.8s cubic-bezier(0.22,1,0.36,1);
            transition-delay: var(--rd, 0ms);
        }
        .reveal.is-visible { opacity: 1; transform: none; }

        /* ── Full-screen Apple-style mobile menu ── */
        @keyframes mob-item-in {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .mob-full-link {
            display: block;
            text-align: center;
            font-size: clamp(2rem, 9vw, 2.75rem);
            font-weight: 700;
            letter-spacing: -0.025em;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            padding: 0.55rem 1.5rem;
            opacity: 0;
            animation: mob-item-in 0.5s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            animation-delay: calc(var(--d, 0) * 60ms + 70ms);
            transition: color 0.15s ease;
        }
        .mob-full-link:hover, .mob-full-link:active { color: #22d3ee; }
        .mob-full-link-sm {
            font-size: 1rem;
            font-weight: 500;
            letter-spacing: 0;
            color: rgba(255,255,255,0.38);
            padding: 0.4rem 1.5rem;
        }
        .mob-full-link-sm:hover, .mob-full-link-sm:active { color: rgba(255,255,255,0.72); }
        .mob-full-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            color: #030712;
            font-weight: 700;
            font-size: 1rem;
            padding: 13px 48px;
            border-radius: 999px;
            text-decoration: none;
            margin-top: 0.6rem;
            opacity: 0;
            animation: mob-item-in 0.5s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            animation-delay: calc(var(--d, 0) * 60ms + 70ms);
            transition: background 0.15s, box-shadow 0.15s;
        }
        .mob-full-cta:hover { background: #dcfeff; box-shadow: 0 0 40px rgba(6,182,212,0.25); }

        /* ── Markets ticker ── */
        @keyframes ticker-scroll {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .ticker-mask {
            -webkit-mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
            mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
        }
        .ticker-track { display: flex; width: max-content; animation: ticker-scroll 32s linear infinite; }
        .ticker-track:hover { animation-play-state: paused; }
        .ticker-item { display: inline-flex; align-items: center; gap: 8px; margin-right: 3rem; flex-shrink: 0; }

        /* ── Motion respect ── */
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            .reveal { opacity: 1; transform: none; }
        }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 overflow-x-hidden"
      x-data="{ mobileMenu: false }"
      x-effect="document.documentElement.style.overflow = mobileMenu ? 'hidden' : ''">

    {{-- ══════════════════ NAVBAR ══════════════════ --}}
    <nav class="nav-glassmorphism fixed top-0 inset-x-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Logo --}}
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 flex-shrink-0">
                    <x-site-logo :size="32" :showText="true" />
                </a>

                {{-- Desktop Nav --}}
                <div class="hidden lg:flex items-center gap-7">
                    <a href="#features" class="nav-link">Features</a>
                    <a href="#markets" class="nav-link">Markets</a>
                    <a href="#how-it-works" class="nav-link">How It Works</a>
                    <a href="#faq" class="nav-link">FAQ</a>
                </div>

                {{-- Desktop Auth --}}
                <div class="hidden lg:flex items-center gap-3">
                    @auth
                        <a href="{{ route('trade.index') }}"
                           class="px-4 py-1.5 rounded-xl text-sm font-medium text-white border border-white/10 hover:border-white/30 transition-colors">
                            Go to Platform
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="px-4 py-1.5 rounded-xl text-sm font-medium nav-link hover:text-white">
                            Login
                        </a>
                        <a href="{{ route('register') }}"
                           class="px-5 py-1.5 rounded-xl text-sm font-semibold border border-white/20 text-white hover:bg-white/5 transition-colors">
                            Sign up
                        </a>
                    @endauth
                </div>

                {{-- Mobile hamburger --}}
                <button @click="mobileMenu = !mobileMenu" class="lg:hidden p-2 rounded-lg text-gray-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="mobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    {{-- ── Full-screen mobile menu overlay (Apple style) ── --}}
    <div x-show="mobileMenu" x-cloak
         x-transition:enter="transition duration-300 ease-out"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="lg:hidden fixed inset-0 z-40 flex flex-col items-center justify-center"
         style="background:rgba(3,7,18,0.97);backdrop-filter:blur(28px);-webkit-backdrop-filter:blur(28px);">

        <div style="display:flex;flex-direction:column;align-items:center;width:100%;">
            {{-- Nav links — stagger via --d CSS variable --}}
            <a href="#features"     @click="mobileMenu=false" class="mob-full-link" style="--d:0">Features</a>
            <a href="#markets"      @click="mobileMenu=false" class="mob-full-link" style="--d:1">Markets</a>
            <a href="#how-it-works" @click="mobileMenu=false" class="mob-full-link" style="--d:2">How It Works</a>
            <a href="#faq"          @click="mobileMenu=false" class="mob-full-link" style="--d:3">FAQ</a>

            {{-- Divider --}}
            <div style="width:48px;height:1px;background:rgba(255,255,255,0.1);margin:1.8rem 0 1.2rem;opacity:0;animation:mob-item-in 0.5s cubic-bezier(0.22,1,0.36,1) forwards;animation-delay:320ms;"></div>

            @auth
                <a href="{{ route('trade.index') }}" class="mob-full-link mob-full-link-sm" style="--d:6">Go to Platform</a>
            @else
                <a href="{{ route('login') }}"    @click="mobileMenu=false" class="mob-full-link mob-full-link-sm" style="--d:5">Login</a>
                <a href="{{ route('register') }}" @click="mobileMenu=false" class="mob-full-cta"             style="--d:6">Sign up</a>
            @endauth
        </div>
    </div>

    {{-- ══════════════════ HERO ══════════════════ --}}
    <style>
        /* ══ Hero: text left · orb right ══ */
        .nt-hero {
            background: radial-gradient(ellipse at 70% 60%, rgba(14,18,40,0.65) 0%, #06080f 55%);
            position: relative;
            min-height: 100svh;
            padding-top: 64px;
            display: grid;
            grid-template-columns: 54% 46%;
            align-items: center;
            overflow: hidden;
        }

        /* Scattered star field — gently breathing */
        .nt-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(1.5px 1.5px at  8% 11%, rgba(255,255,255,0.55) 0%, transparent 100%),
                radial-gradient(1px   1px   at 23%  6%, rgba(255,255,255,0.38) 0%, transparent 100%),
                radial-gradient(2px   2px   at 41% 20%, rgba(255,255,255,0.22) 0%, transparent 100%),
                radial-gradient(1px   1px   at 58%  4%, rgba(255,255,255,0.42) 0%, transparent 100%),
                radial-gradient(1.5px 1.5px at 75% 14%, rgba(255,255,255,0.32) 0%, transparent 100%),
                radial-gradient(1px   1px   at 89%  8%, rgba(255,255,255,0.48) 0%, transparent 100%),
                radial-gradient(1px   1px   at 14% 37%, rgba(255,255,255,0.22) 0%, transparent 100%),
                radial-gradient(2px   2px   at 49% 31%, rgba(255,255,255,0.16) 0%, transparent 100%),
                radial-gradient(1px   1px   at 94% 24%, rgba(255,255,255,0.32) 0%, transparent 100%),
                radial-gradient(1px   1px   at  3% 54%, rgba(255,255,255,0.18) 0%, transparent 100%),
                radial-gradient(1px   1px   at 66% 47%, rgba(255,255,255,0.14) 0%, transparent 100%),
                radial-gradient(1px   1px   at 31% 61%, rgba(255,255,255,0.18) 0%, transparent 100%),
                radial-gradient(1.5px 1.5px at 83% 57%, rgba(255,255,255,0.2)  0%, transparent 100%),
                radial-gradient(1px   1px   at 11% 77%, rgba(255,255,255,0.14) 0%, transparent 100%),
                radial-gradient(1px   1px   at 96% 71%, rgba(255,255,255,0.16) 0%, transparent 100%);
            pointer-events: none;
            animation: star-breathe 7s ease-in-out infinite alternate;
        }
        @keyframes star-breathe {
            from { opacity: 0.65; }
            to   { opacity: 1; }
        }

        /* Shooting stars */
        .nt-shooting-star {
            position: absolute;
            width: 110px; height: 1.5px;
            background: linear-gradient(90deg, rgba(255,255,255,0.75), transparent);
            border-radius: 999px;
            opacity: 0;
            pointer-events: none;
            animation: nt-shoot 9s linear infinite;
            animation-delay: var(--sd, 0s);
        }
        @keyframes nt-shoot {
            0%   { transform: translate3d(0,0,0) rotate(-32deg); opacity: 0; }
            2%   { opacity: 0.85; }
            10%  { transform: translate3d(-300px,190px,0) rotate(-32deg); opacity: 0; }
            100% { transform: translate3d(-300px,190px,0) rotate(-32deg); opacity: 0; }
        }

        /* Left column: text */
        .nt-hero-content {
            text-align: left;
            padding: 0 1rem 0 1.5rem;
            position: relative;
            z-index: 10;
        }

        /* Staggered entrance for hero children */
        @keyframes hero-in {
            from { opacity: 0; transform: translateY(26px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .hero-in {
            opacity: 0;
            animation: hero-in 0.9s cubic-bezier(0.22,1,0.36,1) forwards;
            animation-delay: calc(var(--d, 0) * 110ms + 80ms);
        }

        /* Right column: orb container */
        .nt-orb-side {
            position: relative;
            height: 100%;
            min-height: calc(100svh - 64px);
        }

        /* Parallax carrier (mouse-follow via JS) */
        .nt-parallax { position: absolute; inset: 0; will-change: transform; }

        /* Orb sits inside right column — wider than column so it gets trimmed */
        .nt-orb-wrap {
            position: absolute;
            top: 50%;
            left: 50%;
            width: min(500px, 150%);
            height: min(500px, 150%);
            z-index: 2;
            animation: orb-float 7s ease-in-out infinite;
        }

        .nt-orb {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background:
                radial-gradient(circle at 33% 27%,
                    rgba(255,248,175,0.98)  0%,
                    rgba(238,185,48,0.96)  10%,
                    rgba(198,116,16,0.9)   28%,
                    rgba(140,60,6,0.88)    48%,
                    rgba(70,20,2,0.96)     68%,
                    rgba(8,2,0,1)          86%
                );
            box-shadow:
                inset -28px -28px 55px rgba(0,0,0,0.9),
                inset  8px   8px 22px rgba(255,210,80,0.07),
                0  20px 60px rgba(200,130,28,0.26),
                0   0  100px rgba(200,130,28,0.1),
                0  50px 100px rgba(6,182,212,0.08);
            animation: orb-arrive 1.4s cubic-bezier(0.22,1,0.36,1) both;
        }
        @keyframes orb-arrive {
            from { opacity: 0; transform: scale(0.9); }
            to   { opacity: 1; transform: scale(1); }
        }

        /* Specular highlight */
        .nt-orb::before {
            content: '';
            position: absolute;
            top: 7%; left: 16%;
            width: 32%; height: 22%;
            border-radius: 50%;
            background: radial-gradient(circle,
                rgba(255,255,200,0.9) 0%,
                rgba(255,235,138,0.35) 55%,
                transparent 100%);
            filter: blur(3px);
        }

        /* Cyan bottom reflection on orb */
        .nt-orb::after {
            content: '';
            position: absolute;
            bottom: 14%; right: 16%;
            width: 16%; height: 10%;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(6,182,212,0.5) 0%, transparent 100%);
            filter: blur(5px);
        }

        /* Comet arc orbiting the planet + leading satellite dot */
        .nt-orb-orbit {
            position: absolute;
            inset: -5.5%;
            border-radius: 50%;
            animation: orbit-spin 16s linear infinite;
            pointer-events: none;
        }
        .nt-orb-orbit::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: conic-gradient(from -18deg,
                transparent 0 68%,
                rgba(34,211,238,0.02) 74%,
                rgba(34,211,238,0.4) 96%,
                transparent 100%);
            -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 2px), #000 calc(100% - 1px));
            mask: radial-gradient(farthest-side, transparent calc(100% - 2px), #000 calc(100% - 1px));
        }
        .nt-orb-orbit::after {
            content: '';
            position: absolute;
            top: -2px; left: 50%;
            width: 7px; height: 7px;
            margin-left: -3.5px;
            border-radius: 50%;
            background: #22d3ee;
            box-shadow: 0 0 12px 3px rgba(34,211,238,0.75);
        }
        @keyframes orbit-spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }

        /* Ground glow */
        .nt-orb-glow {
            position: absolute;
            bottom: -10%; left: 50%;
            transform: translateX(-50%);
            width: 68%; height: 26%;
            background: radial-gradient(ellipse,
                rgba(6,182,212,0.28) 0%,
                rgba(6,182,212,0.06) 58%,
                transparent 100%);
            border-radius: 50%;
            filter: blur(20px);
        }

        /* ── Floating stat cards ── */
        .nt-float-card {
            position: absolute;
            z-index: 15;
            display: none; /* hidden on smallest screens */
            opacity: 0;
            animation: hero-in 0.9s cubic-bezier(0.22,1,0.36,1) forwards;
            animation-delay: calc(var(--d, 0) * 110ms + 80ms);
            will-change: transform;
        }
        @media (min-width: 480px) { .nt-float-card { display: block; } }
        .nt-float-a { left: 2%;  bottom: 21%; }
        .nt-float-b { right: 5%; top: 17%; }

        .nt-fc-inner {
            background: rgba(255,255,255,0.055);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 14px;
            padding: 12px 14px;
            min-width: 128px;
            box-shadow: 0 18px 45px rgba(0,0,0,0.35);
            animation: card-drift var(--fd, 6s) ease-in-out infinite;
            animation-delay: var(--fdd, 0s);
        }
        @keyframes card-drift {
            0%,100% { transform: translateY(0); }
            50%      { transform: translateY(-10px); }
        }
        .nt-fc-header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:6px; }
        .nt-fc-label  { font-size:9px; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:rgba(255,255,255,0.42); }
        .nt-fc-value  { font-size:19px; font-weight:800; color:#fff; line-height:1; margin-bottom:3px; }
        .nt-fc-sub    { font-size:9px; color:rgba(255,255,255,0.36); }
        .nt-fc-bar    { position:relative; height:2px; background:rgba(255,255,255,0.08); border-radius:1px; margin-top:8px; overflow:hidden; }
        .nt-fc-bar::after {
            content:''; position:absolute; inset:0;
            background:linear-gradient(to right,#06b6d4,#22d3ee);
            transform-origin:left;
            animation: bar-fill 1.1s cubic-bezier(0.22,1,0.36,1) 0.9s both;
        }
        @keyframes bar-fill { from { transform: scaleX(0); } to { transform: scaleX(0.7); } }
        .nt-live-dot {
            width:6px; height:6px; border-radius:50%;
            background:#34d399;
            box-shadow:0 0 0 0 rgba(52,211,153,0.6);
            animation: live-ping 2s cubic-bezier(0.4,0,0.6,1) infinite;
        }
        @keyframes live-ping {
            0%   { box-shadow: 0 0 0 0 rgba(52,211,153,0.55); }
            70%  { box-shadow: 0 0 0 7px rgba(52,211,153,0); }
            100% { box-shadow: 0 0 0 0 rgba(52,211,153,0); }
        }
        .nt-spark { display:block; margin-top:8px; filter: drop-shadow(0 0 6px rgba(34,211,238,0.45)); }
        .nt-spark path {
            stroke-dasharray: 120;
            stroke-dashoffset: 120;
            animation: spark-draw 1.4s cubic-bezier(0.22,1,0.36,1) 0.8s forwards;
        }
        @keyframes spark-draw { to { stroke-dashoffset: 0; } }

        /* ── Text ── */
        .nt-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            color: rgba(255,255,255,0.38);
            font-size: 10px; font-weight:600;
            letter-spacing: 0.08em; text-transform: uppercase;
            margin-bottom: 0.85rem;
        }
        .nt-h1 {
            font-size: clamp(2.2rem, 8.5vw, 4.2rem);
            font-weight: 800; line-height: 1.1;
            letter-spacing: -0.025em; color: #fff;
            margin-bottom: 0.75rem;
        }
        .nt-h1 span {
            background: linear-gradient(120deg, #06b6d4 0%, #38bdf8 30%, #a5f3fc 50%, #38bdf8 70%, #06b6d4 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient-slide 6s ease-in-out infinite;
        }
        @keyframes gradient-slide {
            0%, 100% { background-position: 0% center; }
            50%      { background-position: 100% center; }
        }
        .nt-desc {
            color: rgba(255,255,255,0.44);
            font-size: 13px; line-height: 1.6;
            max-width: 46ch;
            margin-bottom: 0.35rem;
        }
        .nt-terms { color:rgba(255,255,255,0.2); font-size:9px; margin-bottom:1.25rem; }
        .nt-cta-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .nt-btn-primary {
            position: relative; overflow: hidden;
            display: inline-flex; align-items: center;
            background: #fff; color: #000;
            font-weight: 700; font-size: 12px;
            padding: 10px 20px; border-radius: 999px;
            text-decoration: none;
            transition: background 0.18s, box-shadow 0.18s, transform 0.18s;
            white-space: nowrap;
        }
        .nt-btn-primary:hover { background:#dcfeff; box-shadow:0 0 40px rgba(6,182,212,0.25); transform: translateY(-1px); }
        .nt-btn-ghost {
            display: inline-flex; align-items: center; gap: 3px;
            color: rgba(255,255,255,0.5); font-size: 11px; font-weight:500;
            text-decoration: none; transition: color 0.15s, gap 0.15s;
        }
        .nt-btn-ghost:hover { color:#fff; gap: 6px; }

        @keyframes orb-float {
            0%,100% { transform:translate(-50%, -50%) translateY(0); }
            50%      { transform:translate(-50%, -50%) translateY(-18px); }
        }

        /* Mobile (< 768px): single column — text on top, orb below */
        @media (max-width: 767px) {
            .nt-hero {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
            }
            .nt-hero-content {
                padding-top: 2.6rem;
                padding-right: 1.5rem;
            }
            .nt-orb-side { min-height: 46svh; }
            .nt-orb-wrap {
                top: 52%;
                /* Both dimensions use vw so the orb is always a perfect circle */
                width:  clamp(240px, 76vw, 400px);
                height: clamp(240px, 76vw, 400px);
            }
            .nt-float-a { left: 4%;  bottom: 14%; }
            .nt-float-b { right: 4%; top: 4%; }
        }

        /* Tablet / Desktop (768px+) */
        @media (min-width: 768px) {
            .nt-hero { grid-template-columns: 1fr 1fr; }
            .nt-hero-content { padding: 0 2rem 0 max(2rem, 5vw); }
            /* Both dimensions use vw — always a perfect circle */
            .nt-orb-wrap { width: clamp(380px, 44vw, 620px); height: clamp(380px, 44vw, 620px); }
            .nt-h1   { font-size: clamp(2.8rem,4.5vw,4.5rem); }
            .nt-desc { font-size: 15px; }
            .nt-btn-primary { font-size:15px; padding:13px 30px; }
            .nt-eyebrow { font-size:13px; }
            .nt-terms { font-size:11px; }
        }
        @media (min-width: 1280px) {
            .nt-hero-content { padding: 0 3rem 0 calc((100vw - 1280px) / 2 + 2rem); }
        }
    </style>

    <section class="nt-hero">

        {{-- Shooting stars --}}
        <div class="nt-shooting-star" style="top:12%; left:78%; --sd:1.5s;"></div>
        <div class="nt-shooting-star" style="top:6%; left:38%; --sd:6s; width:80px;"></div>

        {{-- LEFT: text --}}
        <div class="nt-hero-content">
            <p class="nt-eyebrow hero-in" style="--d:0">
                <span class="nt-live-dot"></span>
                Live Markets &middot; Crypto &amp; Forex
            </p>

            <h1 class="nt-h1 hero-in" style="--d:1">
                Elevate Your<br>
                <span>Trading<br>Experience</span>
            </h1>

            <p class="nt-desc hero-in" style="--d:2">Trade crypto, forex and commodities on infrastructure built for speed — AI bots, instant deposits, and payouts in minutes.</p>
            <p class="nt-terms hero-in" style="--d:3">Capital at risk.</p>

            <div class="nt-cta-row hero-in" style="--d:4">
                <a href="{{ route('register') }}" class="nt-btn-primary">Start Trading</a>
                <a href="{{ route('login') }}" class="nt-btn-ghost">
                    Login
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

        {{-- RIGHT: orb column — orb is oversized so it gets trimmed at edges --}}
        <div class="nt-orb-side">
            <div class="nt-parallax" data-px="16" data-py="10">
                <div class="nt-orb-wrap">
                    <div class="nt-orb"></div>
                    <div class="nt-orb-orbit"></div>
                    <div class="nt-orb-glow"></div>
                </div>
            </div>

            {{-- Floating glass stat cards --}}
            <div class="nt-float-card nt-float-a" style="--d:6" data-px="32" data-py="20">
                <div class="nt-fc-inner" style="--fd:6.5s">
                    <div class="nt-fc-header">
                        <span class="nt-fc-label">BTC/USD &middot; Buy</span>
                        <span class="nt-live-dot"></span>
                    </div>
                    <p class="nt-fc-value" style="color:#34d399;">+$1,284.50</p>
                    <p class="nt-fc-sub">Trade won &middot; just now</p>
                    <svg class="nt-spark" width="96" height="24" viewBox="0 0 96 24" fill="none">
                        <path d="M1 18 L13 13 L24 15 L36 9 L48 12 L60 5 L72 9 L84 3 L95 6"
                              stroke="#22d3ee" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>

            <div class="nt-float-card nt-float-b" style="--d:7" data-px="24" data-py="30">
                <div class="nt-fc-inner" style="--fd:7.5s; --fdd:0.8s;">
                    <div class="nt-fc-header">
                        <span class="nt-fc-label">24h Volume</span>
                        <span style="font-size:9px;color:#34d399;font-weight:700;">▲ 12%</span>
                    </div>
                    <p class="nt-fc-value">$2.4B</p>
                    <p class="nt-fc-sub">Across 96 market pairs</p>
                    <div class="nt-fc-bar"></div>
                </div>
            </div>
        </div>

    </section>

    {{-- ══════════════════ STATS BAR ══════════════════ --}}
    <section class="border-y border-white/5 bg-gray-950/80 py-5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 text-center">
                @foreach([
                    ['$2.4B+', 'Trading Volume'],
                    ['96+', 'Market Pairs'],
                    ['150K+', 'Active Traders'],
                    ['99.9%', 'Platform Uptime'],
                ] as $i => [$val, $label])
                <div class="reveal" style="--rd: {{ $i * 90 }}ms">
                    <p class="text-2xl font-black text-white" data-countup="{{ $val }}">{{ $val }}</p>
                    <p class="text-xs text-gray-500 mt-0.5 font-medium">{{ $label }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════════════ FEATURES (FlowArt stacked scroll) ══════════════════ --}}
    <style>
        /* ── FlowArt: stacked scroll sections ── */
        .fa-wrap { position: relative; overflow: hidden; }
        .fa-section {
            position: relative;
            width: 100%;
            height: 100vh;
            min-height: 580px;
            display: flex;
            align-items: center;
            will-change: transform;
            overflow: hidden;
            box-shadow: 0 -34px 90px rgba(0,0,0,0.55);
        }
        .fa-inner {
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        .fa-tag {
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(6,182,212,0.75);
            margin-bottom: 1.1rem;
        }
        .fa-title {
            font-size: clamp(2.1rem, 5vw, 4.6rem);
            font-weight: 900;
            line-height: 1.06;
            letter-spacing: -0.03em;
            color: #fff;
            margin-bottom: 1.4rem;
        }
        .fa-body {
            font-size: clamp(0.82rem, 1.3vw, 1rem);
            line-height: 1.75;
            color: rgba(255,255,255,0.42);
            max-width: 44ch;
        }
        .fa-visual {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .fa-bg-num {
            position: absolute;
            right: -0.02em;
            bottom: -0.12em;
            font-size: clamp(8rem, 25vw, 22rem);
            font-weight: 900;
            line-height: 0.85;
            color: rgba(255,255,255,0.03);
            user-select: none;
            pointer-events: none;
            z-index: 1;
        }
        /* Radar rings (section 01) */
        .fa-radar-sweep {
            position: absolute; inset: 0; border-radius: 50%;
            background: conic-gradient(from 0deg, rgba(6,182,212,0.22) 0%, transparent 24%);
            animation: orbit-spin 6s linear infinite;
            -webkit-mask: radial-gradient(farthest-side, #000 0%, #000 100%);
        }
        @media (min-width: 640px)  { .fa-inner { padding: 0 1.5rem; } }
        @media (min-width: 1024px) { .fa-inner { padding: 0 2rem; } }
        @media (max-width: 767px) {
            .fa-section { height: auto; min-height: 100svh; }
            .fa-inner { grid-template-columns: 1fr; padding-top: 4.5rem; padding-bottom: 2.5rem; gap: 2rem; }
            .fa-visual { justify-content: flex-start; }
        }
    </style>

    <section id="features" class="fa-wrap">

        {{-- 01 — Who We Are --}}
        <div class="fa-section" data-flow-section style="background:#040e1c;">
            <div class="fa-inner">
                <div>
                    <span class="fa-tag">01 — Who We Are</span>
                    <h2 class="fa-title">A platform<br>built around<br>you</h2>
                    <p class="fa-body">NextTrade is a regulated, multi-asset trading platform engineered for the modern trader. Whether you're new or experienced, we give you the infrastructure to trade crypto, forex, and commodities with confidence.</p>
                </div>
                <div class="fa-visual">
                    <div style="position:relative;width:210px;height:210px;flex-shrink:0;">
                        <div class="fa-radar-sweep"></div>
                        <div style="position:absolute;inset:0;border-radius:50%;border:1px solid rgba(6,182,212,0.1);"></div>
                        <div style="position:absolute;inset:15%;border-radius:50%;border:1px solid rgba(6,182,212,0.16);"></div>
                        <div style="position:absolute;inset:30%;border-radius:50%;border:1px solid rgba(6,182,212,0.2);"></div>
                        <div style="position:absolute;inset:44%;border-radius:50%;background:radial-gradient(circle,rgba(6,182,212,0.28) 0%,rgba(6,182,212,0.04) 60%,transparent);box-shadow:0 0 38px rgba(6,182,212,0.14);"></div>
                        <div style="position:absolute;top:6%;left:50%;width:7px;height:7px;background:#22d3ee;border-radius:50%;transform:translateX(-50%);box-shadow:0 0 10px rgba(34,211,238,0.85);"></div>
                        <div style="position:absolute;bottom:11%;right:13%;width:5px;height:5px;background:rgba(34,211,238,0.55);border-radius:50%;box-shadow:0 0 8px rgba(34,211,238,0.45);"></div>
                    </div>
                </div>
            </div>
            <div class="fa-bg-num">01</div>
        </div>

        {{-- 02 — Our Mission --}}
        <div class="fa-section" data-flow-section style="background:#030712;">
            <div class="fa-inner">
                <div>
                    <span class="fa-tag">02 — Our Mission</span>
                    <h2 class="fa-title">Markets open<br>to every<br>trader</h2>
                    <p class="fa-body">We exist to make professional-grade trading accessible to everyone. Transparent fees, no hidden costs, and the same tools used by institutional traders — now in your hands.</p>
                </div>
                <div class="fa-visual">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;width:240px;">
                        @foreach([['$2.4B+','Trading Volume'],['96+','Market Pairs'],['150K+','Active Traders'],['99.9%','Platform Uptime']] as [$v,$l])
                        <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);border-radius:14px;padding:18px 14px;">
                            <p style="font-size:1.55rem;font-weight:900;color:#fff;line-height:1;margin-bottom:5px;">{{ $v }}</p>
                            <p style="font-size:10px;color:rgba(255,255,255,0.3);text-transform:uppercase;letter-spacing:0.06em;">{{ $l }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="fa-bg-num">02</div>
        </div>

        {{-- 03 — How It Works --}}
        <div class="fa-section" data-flow-section style="background:#050f1a;">
            <div class="fa-inner">
                <div>
                    <span class="fa-tag">03 — How It Works</span>
                    <h2 class="fa-title">Live in<br>under<br>3 minutes</h2>
                    <p class="fa-body">Our onboarding is built for speed. No lengthy verification, no complex forms. Sign up, fund your account, and place your first trade in minutes.</p>
                </div>
                <div class="fa-visual" style="flex-direction:column;align-items:flex-start;gap:18px;">
                    @foreach([
                        ['Create Account','Sign up with your email. Verify in seconds.'],
                        ['Fund Your Wallet','Deposit via M-Pesa, bank transfer, or crypto.'],
                        ['Place Your Trade','Pick an asset, set your amount, go live.'],
                    ] as $idx => [$title,$desc])
                    <div style="display:flex;align-items:flex-start;gap:14px;">
                        <div style="flex-shrink:0;width:34px;height:34px;border-radius:9px;background:rgba(6,182,212,0.08);border:1px solid rgba(6,182,212,0.22);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:#22d3ee;">0{{ $idx+1 }}</div>
                        <div>
                            <p style="font-size:14px;font-weight:700;color:#fff;margin-bottom:3px;">{{ $title }}</p>
                            <p style="font-size:12px;color:rgba(255,255,255,0.38);line-height:1.5;">{{ $desc }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="fa-bg-num">03</div>
        </div>

        {{-- 04 — The Platform --}}
        <div class="fa-section" data-flow-section style="background:#040810;">
            <div class="fa-inner">
                <div>
                    <span class="fa-tag">04 — The Platform</span>
                    <h2 class="fa-title">Everything<br>a serious<br>trader needs</h2>
                    <p class="fa-body">From millisecond order execution to AI-powered bots, every feature is precision-engineered for performance and reliability — all in one place.</p>
                </div>
                <div class="fa-visual" style="flex-wrap:wrap;gap:9px;max-width:290px;align-items:flex-start;align-content:flex-start;">
                    @foreach([
                        ['Real-Time Execution','M13 10V3L4 14h7v7l9-11h-7z'],
                        ['Advanced Analytics','M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                        ['Bank-Grade Security','M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
                        ['AI Trading Bots','M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
                        ['Instant Deposits','M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['Regulated Platform','M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3'],
                    ] as [$label,$path])
                    <div style="display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:999px;padding:7px 13px;white-space:nowrap;">
                        <svg width="12" height="12" fill="none" stroke="#22d3ee" viewBox="0 0 24 24" style="flex-shrink:0;opacity:0.8;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}"/>
                        </svg>
                        <span style="font-size:11px;color:rgba(255,255,255,0.58);">{{ $label }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="fa-bg-num">04</div>
        </div>

        {{-- 05 — Join Us --}}
        <div class="fa-section" data-flow-section style="background:#020508;">
            <div class="fa-inner" style="grid-template-columns:1fr;text-align:center;justify-items:center;">
                <div style="max-width:560px;">
                    <span class="fa-tag" style="text-align:center;">05 — Start Today</span>
                    <h2 class="fa-title" style="text-align:center;">Your edge<br>starts here</h2>
                    <p class="fa-body" style="text-align:center;margin:0 auto 2.5rem;max-width:42ch;">Join over 150,000 traders using NextTrade to grow their portfolio. Start with a free demo or go live today.</p>
                    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                        <a href="{{ route('register') }}" class="nt-btn-primary" style="font-size:15px;padding:14px 34px;">Start Trading Free</a>
                        <a href="{{ route('login') }}" style="display:inline-flex;align-items:center;gap:5px;color:rgba(255,255,255,0.38);font-size:13px;font-weight:500;text-decoration:none;padding:14px 0;transition:color 0.15s;" onmouseover="this.style.color='rgba(255,255,255,0.7)'" onmouseout="this.style.color='rgba(255,255,255,0.38)'">
                            Already have an account
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </div>
            <div class="fa-bg-num">05</div>
        </div>

    </section>

    {{-- ══════════════════ HOW IT WORKS ══════════════════ --}}
    <section id="how-it-works" class="py-24" style="background: radial-gradient(ellipse at 50% 50%, rgba(6,182,212,0.04) 0%, transparent 70%), #030712;">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-cyan-400 text-sm font-semibold uppercase tracking-widest mb-3 reveal">Get Started</p>
            <h2 class="text-4xl lg:text-5xl font-black text-white mb-16 reveal" style="--rd:80ms">Trade in 3 steps</h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 relative">
                {{-- Connector line --}}
                <div class="hidden sm:block absolute top-8 left-1/4 right-1/4 h-px bg-gradient-to-r from-transparent via-cyan-500/30 to-transparent"></div>

                @foreach([
                    ['01', 'Create Account', 'Sign up with your email in seconds.'],
                    ['02', 'Fund Wallet', 'Deposit via M-Pesa or bank transfer.'],
                    ['03', 'Start Trading', 'Pick an asset and place your trade.'],
                ] as $i => [$num, $title, $desc])
                <div class="flex flex-col items-center reveal" style="--rd: {{ 150 + $i * 130 }}ms">
                    <div class="w-16 h-16 rounded-2xl border border-cyan-500/30 bg-cyan-500/5 flex items-center justify-center mb-5 relative z-10 transition-all duration-300 hover:border-cyan-400/60 hover:bg-cyan-500/10 hover:-translate-y-1 hover:shadow-[0_10px_40px_rgba(6,182,212,0.2)]">
                        <span class="text-2xl font-black text-cyan-400">{{ $num }}</span>
                    </div>
                    <h3 class="font-bold text-white text-lg mb-2">{{ $title }}</h3>
                    <p class="text-sm text-gray-400 leading-relaxed max-w-xs mx-auto">{{ $desc }}</p>
                </div>
                @endforeach
            </div>

            <div class="mt-14 reveal" style="--rd:500ms">
                <a href="{{ route('register') }}"
                   class="cta-btn inline-flex items-center gap-2 px-10 py-4 rounded-2xl font-bold text-base">
                    Open Free Account
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    {{-- ══════════════════ MARKETS TICKER ══════════════════ --}}
    <section id="markets" class="border-y border-white/5 py-5 bg-gray-950/60 overflow-hidden ticker-mask">
        @php
            $tickers = [
                ['BTC/USD', '+2.4%', true], ['ETH/USD', '+1.1%', true], ['XRP/USD', '-0.5%', false],
                ['EUR/USD', '+0.3%', true], ['GBP/USD', '-0.2%', false], ['GOLD', '+0.8%', true],
                ['OIL', '-1.2%', false], ['S&P 500', '+0.6%', true], ['NASDAQ', '+1.3%', true],
            ];
        @endphp
        <div class="ticker-track">
            {{-- Exact double of the set → translateX(-50%) loops seamlessly --}}
            @foreach([1, 2] as $pass)
                @foreach($tickers as [$pair, $change, $up])
                <div class="ticker-item">
                    <span class="text-sm font-semibold text-white">{{ $pair }}</span>
                    <span class="text-xs font-medium {{ $up ? 'text-emerald-400' : 'text-red-400' }}">{{ $change }}</span>
                </div>
                @endforeach
            @endforeach
        </div>
    </section>

    {{-- ══════════════════ FAQ ══════════════════ --}}
    <section id="faq" class="py-24 bg-gray-950">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <p class="text-cyan-400 text-sm font-semibold uppercase tracking-widest mb-3 reveal">FAQ</p>
                <h2 class="text-4xl font-black text-white reveal" style="--rd:80ms">Common questions</h2>
            </div>

            <div class="space-y-3">
                @foreach([
                    ['Is the platform safe?', 'Yes — funds are held in segregated accounts with 256-bit SSL encryption and 2FA.'],
                    ['What is the minimum deposit?', 'Start with as little as KES 1,000. Demo accounts are free.'],
                    ['Which assets can I trade?', 'Forex, Crypto, Commodities, and Indices — 96+ pairs total.'],
                    ['How do I withdraw?', 'Withdrawals process within 24 hours to M-Pesa or bank.'],
                    ['Can I use trading bots?', 'Yes — our AI bots automate your strategy around the clock.'],
                ] as $i => [$q, $a])
                <div class="glassmorphism rounded-xl overflow-hidden reveal transition-colors duration-300 hover:border-cyan-500/25"
                     style="--rd: {{ $i * 70 }}ms" x-data="{ show: false }">
                    <button @click="show = !show"
                            class="w-full flex items-center justify-between px-5 py-4 text-left">
                        <span class="font-semibold text-white text-sm">{{ $q }}</span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-4"
                             :class="show ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="show" x-collapse class="px-5 pb-4">
                        <p class="text-sm text-gray-400 leading-relaxed">{{ $a }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════════════ CTA BANNER ══════════════════ --}}
    <section class="py-20" style="background: radial-gradient(ellipse at 50% 50%, rgba(6,182,212,0.08) 0%, transparent 70%), #030712;">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl lg:text-5xl font-black text-white mb-8 reveal">Ready to trade?</h2>
            <div class="reveal" style="--rd:120ms">
                <a href="{{ route('register') }}"
                   class="cta-btn inline-flex items-center gap-2 px-10 py-4 rounded-2xl font-bold text-lg">
                    Create Free Account
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
                <p class="text-xs text-gray-600 mt-4">No credit card required</p>
            </div>
        </div>
    </section>

    {{-- ══════════════════ FOOTER ══════════════════ --}}
    <footer class="border-t border-white/5 bg-gray-950 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2.5">
                    <x-site-logo :size="28" :showText="true" />
                </div>
                <div class="flex items-center gap-6 text-xs text-gray-600">
                    <a href="#" class="hover:text-gray-400 transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-gray-400 transition-colors">Terms of Service</a>
                    <a href="#" class="hover:text-gray-400 transition-colors">Contact</a>
                </div>
                <p class="text-xs text-gray-600">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            /* ── Navbar background on scroll ── */
            var nav = document.querySelector('nav.nav-glassmorphism');
            if (nav) {
                var onScroll = function () {
                    nav.classList.toggle('nav-scrolled', window.scrollY > 30);
                };
                window.addEventListener('scroll', onScroll, { passive: true });
                onScroll();
            }

            /* ── Scroll reveal ── */
            var revealEls = document.querySelectorAll('.reveal');
            if (reduced || !('IntersectionObserver' in window)) {
                revealEls.forEach(function (el) { el.classList.add('is-visible'); });
            } else {
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
                revealEls.forEach(function (el) { io.observe(el); });
            }

            /* ── Stat count-up (parses "$2.4B+", "99.9%" etc.) ── */
            function countUp(el) {
                var m = el.dataset.countup.match(/^([^0-9]*)([\d.]+)(.*)$/);
                if (!m) return;
                var prefix = m[1], target = parseFloat(m[2]), suffix = m[3];
                var decimals = (m[2].split('.')[1] || '').length;
                var start = null, duration = 1400;
                function frame(ts) {
                    if (!start) start = ts;
                    var p = Math.min((ts - start) / duration, 1);
                    var eased = 1 - Math.pow(1 - p, 3);
                    el.textContent = prefix + (target * eased).toFixed(decimals) + suffix;
                    if (p < 1) requestAnimationFrame(frame);
                }
                requestAnimationFrame(frame);
            }
            var countEls = document.querySelectorAll('[data-countup]');
            if (!reduced && 'IntersectionObserver' in window) {
                var cio = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            countUp(entry.target);
                            cio.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.6 });
                countEls.forEach(function (el) { cio.observe(el); });
            }

            /* ── Hero mouse parallax (desktop, fine pointers only) ── */
            var hero = document.querySelector('.nt-hero');
            if (hero && !reduced && window.matchMedia('(pointer: fine)').matches) {
                var items = Array.prototype.slice.call(hero.querySelectorAll('[data-px]'));
                var tx = 0, ty = 0, cx = 0, cy = 0, raf = null;
                function loop() {
                    cx += (tx - cx) * 0.06;
                    cy += (ty - cy) * 0.06;
                    items.forEach(function (el) {
                        var px = parseFloat(el.dataset.px || 0), py = parseFloat(el.dataset.py || 0);
                        el.style.transform = 'translate3d(' + (cx * px).toFixed(2) + 'px,' + (cy * py).toFixed(2) + 'px,0)';
                    });
                    if (Math.abs(tx - cx) > 0.001 || Math.abs(ty - cy) > 0.001) {
                        raf = requestAnimationFrame(loop);
                    } else {
                        raf = null;
                    }
                }
                function kick() { if (!raf) raf = requestAnimationFrame(loop); }
                hero.addEventListener('pointermove', function (e) {
                    var r = hero.getBoundingClientRect();
                    tx = ((e.clientX - r.left) / r.width - 0.5) * 2;
                    ty = ((e.clientY - r.top) / r.height - 0.5) * 2;
                    kick();
                });
                hero.addEventListener('pointerleave', function () { tx = 0; ty = 0; kick(); });
            }
        })();
    </script>

    {{-- ── FlowArt: GSAP stacked-scroll init ── --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script>
        (function () {
            if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            gsap.registerPlugin(ScrollTrigger);

            var sections = gsap.utils.toArray('[data-flow-section]');
            sections.forEach(function (section, i) {
                gsap.set(section, { zIndex: i + 1 });

                if (i > 0) {
                    gsap.fromTo(section,
                        { rotation: 22, transformOrigin: 'bottom left' },
                        {
                            rotation: 0,
                            ease: 'none',
                            scrollTrigger: {
                                trigger: section,
                                start: 'top bottom',
                                end: 'top 22%',
                                scrub: true,
                            }
                        }
                    );
                }

                if (i < sections.length - 1) {
                    ScrollTrigger.create({
                        trigger: section,
                        start: 'top top',
                        end: 'bottom top',
                        pin: true,
                        pinSpacing: false,
                    });
                }

                // Content lifts in with a stagger as the section arrives
                var inner = section.querySelector('.fa-inner');
                if (inner) {
                    gsap.from(inner.children, {
                        y: 44, opacity: 0,
                        duration: 0.9, ease: 'power3.out', stagger: 0.14,
                        scrollTrigger: { trigger: section, start: 'top 55%', once: true }
                    });
                }

                // Giant background number drifts slower than the section (parallax)
                var num = section.querySelector('.fa-bg-num');
                if (num) {
                    gsap.fromTo(num,
                        { yPercent: 16 },
                        {
                            yPercent: -6,
                            ease: 'none',
                            scrollTrigger: { trigger: section, start: 'top bottom', end: 'bottom top', scrub: true }
                        }
                    );
                }
            });
        })();
    </script>
</body>
</html>
