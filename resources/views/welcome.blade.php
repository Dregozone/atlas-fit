<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Atlas Fit – Your intelligent health, fitness and nutrition companion. Track workouts, monitor nutrition and crush your goals.">
        <meta name="theme-color" content="#0a0a0a">

        <title>{{ config('app.name', 'Atlas Fit') }} – Transform Your Body, Elevate Your Life</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        {{-- <link rel="icon" href="/favicon.svg" type="image/svg+xml"> --}}
        {{-- <link rel="apple-touch-icon" href="/apple-touch-icon.png"> --}}

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            @keyframes float {
                0%, 100% { transform: translateY(0px); }
                50% { transform: translateY(-20px); }
            }
            @keyframes pulse-glow {
                0%, 100% { box-shadow: 0 0 20px rgba(16, 185, 129, 0.3); }
                50% { box-shadow: 0 0 60px rgba(16, 185, 129, 0.6); }
            }
            @keyframes gradient-shift {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
            @keyframes scroll-left {
                0% { transform: translateX(0); }
                100% { transform: translateX(-50%); }
            }
            .float { animation: float 6s ease-in-out infinite; }
            .float-delay-1 { animation: float 6s ease-in-out 1s infinite; }
            .float-delay-2 { animation: float 6s ease-in-out 2s infinite; }
            .reveal {
                opacity: 0;
                transform: translateY(40px);
                transition: opacity 0.8s ease, transform 0.8s ease;
            }
            .reveal.visible { opacity: 1; transform: translateY(0); }
            .reveal-delay-1 { transition-delay: 0.1s; }
            .reveal-delay-2 { transition-delay: 0.2s; }
            .reveal-delay-3 { transition-delay: 0.3s; }
            .reveal-delay-4 { transition-delay: 0.45s; }
            .gradient-text {
                background: linear-gradient(135deg, #10b981, #06b6d4, #a78bfa);
                background-size: 200% 200%;
                animation: gradient-shift 6s ease infinite;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            .glass-card {
                background: rgba(255, 255, 255, 0.04);
                backdrop-filter: blur(12px);
                border: 1px solid rgba(255, 255, 255, 0.08);
            }
            .mesh-bg {
                background:
                    radial-gradient(ellipse at 20% 50%, rgba(16, 185, 129, 0.12) 0%, transparent 50%),
                    radial-gradient(ellipse at 80% 20%, rgba(6, 182, 212, 0.12) 0%, transparent 50%),
                    radial-gradient(ellipse at 60% 100%, rgba(139, 92, 246, 0.08) 0%, transparent 50%),
                    #0a0a0a;
            }
            #navbar { transition: background 0.3s ease, box-shadow 0.3s ease; }
            #navbar.scrolled {
                background: rgba(10, 10, 10, 0.95);
                backdrop-filter: blur(20px);
                box-shadow: 0 1px 0 rgba(255, 255, 255, 0.08);
            }
            #mobile-menu { max-height: 0; overflow: hidden; transition: max-height 0.4s ease; }
            #mobile-menu.open { max-height: 420px; }
            .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.4s ease; }
            .faq-answer.open { max-height: 500px; }
            .faq-icon { transition: transform 0.3s ease; }
            .faq-item.open .faq-icon { transform: rotate(45deg); }
            .testimonial-track {
                display: flex;
                gap: 1.5rem;
                animation: scroll-left 45s linear infinite;
            }
            .testimonial-track:hover { animation-play-state: paused; }
            .glow-btn { animation: pulse-glow 3s ease-in-out infinite; }
            .pricing-popular { position: relative; }
            .pricing-popular::before {
                content: \'\';
                position: absolute;
                inset: -2px;
                background: linear-gradient(135deg, #10b981, #06b6d4);
                border-radius: 18px;
                z-index: -1;
            }
            #cookie-banner { transition: transform 0.5s ease, opacity 0.5s ease; }
            #cookie-banner.hidden { display: none; }
        </style>
    </head>

    <body class="bg-[#0a0a0a] text-white antialiased overflow-x-hidden">

        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[100] focus:px-4 focus:py-2 focus:bg-emerald-500 focus:text-black focus:rounded-lg focus:font-semibold">
            Skip to main content
        </a>

        <nav id="navbar" class="fixed top-0 left-0 right-0 z-50 px-6 py-4" role="navigation" aria-label="Main navigation">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-3 group" aria-label="Atlas Fit - go to homepage">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-emerald-500 group-hover:bg-emerald-400 transition-colors" aria-hidden="true">
                        <x-app-logo-icon class="size-5 fill-current text-white" />
                    </div>
                    <span class="text-lg font-bold text-white">Atlas<span class="text-emerald-400">Fit</span></span>
                </a>

                <ul class="hidden md:flex items-center gap-8 list-none" role="menubar">
                    <li role="none"><a href="#features" class="text-sm text-zinc-400 hover:text-white transition-colors" role="menuitem">Features</a></li>
                    <li role="none"><a href="#how-it-works" class="text-sm text-zinc-400 hover:text-white transition-colors" role="menuitem">How It Works</a></li>
                    <li role="none"><a href="#pricing" class="text-sm text-zinc-400 hover:text-white transition-colors" role="menuitem">Pricing</a></li>
                    <li role="none"><a href="#faq" class="text-sm text-zinc-400 hover:text-white transition-colors" role="menuitem">FAQ</a></li>
                </ul>

                <div class="hidden md:flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-500 rounded-lg transition-colors">Dashboard</a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-zinc-300 hover:text-white transition-colors">Log in</a>
                        @endif
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-5 py-2 text-sm font-semibold text-black bg-emerald-400 hover:bg-emerald-300 rounded-lg transition-all glow-btn">Start Free</a>
                        @endif
                    @endauth
                </div>

                <button id="mobile-menu-btn" class="md:hidden flex flex-col gap-1.5 p-2 rounded-lg hover:bg-white/10 transition-colors" aria-expanded="false" aria-controls="mobile-menu" aria-label="Toggle mobile menu" type="button">
                    <span class="block w-5 h-0.5 bg-white rounded transition-all" aria-hidden="true"></span>
                    <span class="block w-5 h-0.5 bg-white rounded transition-all" aria-hidden="true"></span>
                    <span class="block w-5 h-0.5 bg-white rounded transition-all" aria-hidden="true"></span>
                </button>
            </div>

            <div id="mobile-menu" role="menu" aria-label="Mobile navigation">
                <div class="max-w-7xl mx-auto mt-4 pb-4 flex flex-col gap-1 border-t border-white/10 pt-4">
                    <a href="#features" class="text-sm text-zinc-400 hover:text-white py-2 transition-colors" role="menuitem">Features</a>
                    <a href="#how-it-works" class="text-sm text-zinc-400 hover:text-white py-2 transition-colors" role="menuitem">How It Works</a>
                    <a href="#pricing" class="text-sm text-zinc-400 hover:text-white py-2 transition-colors" role="menuitem">Pricing</a>
                    <a href="#faq" class="text-sm text-zinc-400 hover:text-white py-2 transition-colors" role="menuitem">FAQ</a>
                    <div class="flex gap-3 mt-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="flex-1 text-center px-4 py-2.5 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-500 rounded-lg transition-colors">Dashboard</a>
                        @else
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="flex-1 text-center px-4 py-2.5 text-sm font-medium text-zinc-300 border border-white/20 hover:border-white/40 rounded-lg transition-colors">Log in</a>
                            @endif
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="flex-1 text-center px-4 py-2.5 text-sm font-semibold text-black bg-emerald-400 hover:bg-emerald-300 rounded-lg transition-colors">Start Free</a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <main id="main-content">

            <section class="relative min-h-screen mesh-bg flex items-center justify-center overflow-hidden pt-20" aria-labelledby="hero-heading">
                <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl float pointer-events-none" aria-hidden="true"></div>
                <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-cyan-500/10 rounded-full blur-3xl float-delay-1 pointer-events-none" aria-hidden="true"></div>
                <div class="absolute top-1/2 right-1/3 w-64 h-64 bg-violet-500/10 rounded-full blur-3xl float-delay-2 pointer-events-none" aria-hidden="true"></div>
                <div class="absolute inset-0 opacity-[0.03] pointer-events-none" aria-hidden="true" style="background-image: linear-gradient(rgba(255,255,255,.6) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.6) 1px, transparent 1px); background-size: 60px 60px;"></div>

                <div class="relative z-10 max-w-7xl mx-auto px-6 py-20 text-center">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-medium mb-8 reveal">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" aria-hidden="true"></span>
                        Your intelligent fitness companion
                    </div>

                    <h1 id="hero-heading" class="text-5xl md:text-7xl lg:text-8xl font-black leading-none mb-6 reveal reveal-delay-1">
                        Transform Your Body.<br>
                        <span class="gradient-text">Elevate Your Life.</span>
                    </h1>

                    <p class="max-w-2xl mx-auto text-lg md:text-xl text-zinc-400 leading-relaxed mb-10 reveal reveal-delay-2">
                        Atlas Fit combines intelligent workout planning, precision nutrition tracking, and data-driven insights to help you achieve your best shape &#8212; faster than ever before.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16 reveal reveal-delay-3">
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-4 text-base font-bold text-black bg-emerald-400 hover:bg-emerald-300 rounded-xl transition-all hover:scale-105 hover:shadow-[0_0_40px_rgba(16,185,129,0.5)] glow-btn" aria-label="Create your free Atlas Fit account">
                                Start for Free &#8594;
                            </a>
                        @endif
                        <a href="#how-it-works" class="w-full sm:w-auto px-8 py-4 text-base font-medium text-white border border-white/20 hover:border-white/40 rounded-xl transition-all hover:bg-white/5">
                            See how it works
                        </a>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 max-w-3xl mx-auto reveal reveal-delay-4">
                        <div class="glass-card rounded-2xl p-5 text-center">
                            <div class="text-3xl md:text-4xl font-black text-white mb-1">50K+</div>
                            <div class="text-xs text-zinc-500 uppercase tracking-widest">Active Members</div>
                        </div>
                        <div class="glass-card rounded-2xl p-5 text-center">
                            <div class="text-3xl md:text-4xl font-black text-emerald-400 mb-1">2M+</div>
                            <div class="text-xs text-zinc-500 uppercase tracking-widest">Workouts Logged</div>
                        </div>
                        <div class="glass-card rounded-2xl p-5 text-center">
                            <div class="text-3xl md:text-4xl font-black text-cyan-400 mb-1">500K+</div>
                            <div class="text-xs text-zinc-500 uppercase tracking-widest">Meals Tracked</div>
                        </div>
                        <div class="glass-card rounded-2xl p-5 text-center">
                            <div class="text-3xl md:text-4xl font-black text-violet-400 mb-1">4.9&#9733;</div>
                            <div class="text-xs text-zinc-500 uppercase tracking-widest">User Rating</div>
                        </div>
                    </div>
                </div>

                <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 text-zinc-600 animate-bounce" aria-hidden="true">
                    <span class="text-xs uppercase tracking-widest">Scroll</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </section>

            <section class="py-12 border-y border-white/5" aria-label="Trusted by fitness enthusiasts across Europe">
                <div class="max-w-7xl mx-auto px-6">
                    <p class="text-center text-xs uppercase tracking-widest text-zinc-600 mb-8">Trusted by fitness enthusiasts across Europe</p>
                    <div class="flex flex-wrap items-center justify-center gap-x-12 gap-y-4 opacity-30" aria-hidden="true">
                        <span class="text-xl font-black tracking-tight text-zinc-300">FitLife</span>
                        <span class="text-xl font-black tracking-tight text-zinc-300">NutriPro</span>
                        <span class="text-xl font-black tracking-tight text-zinc-300">IronGym</span>
                        <span class="text-xl font-black tracking-tight text-zinc-300">WellnessHub</span>
                        <span class="text-xl font-black tracking-tight text-zinc-300">AthleteCo</span>
                    </div>
                </div>
            </section>

            <section id="features" class="py-24 px-6" aria-labelledby="features-heading">
                <div class="max-w-7xl mx-auto">
                    <div class="text-center mb-16 reveal">
                        <span class="text-emerald-400 text-sm font-semibold uppercase tracking-widest">Everything you need</span>
                        <h2 id="features-heading" class="text-4xl md:text-5xl font-black text-white mt-3 mb-4">Your complete fitness arsenal</h2>
                        <p class="text-zinc-400 max-w-xl mx-auto leading-relaxed">From structured workouts to macro tracking and schedule planning &#8212; we&#8217;ve built every tool you need to succeed in one seamless platform.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="glass-card rounded-2xl p-8 transition-all group reveal reveal-delay-1 hover:bg-white/[0.07]">
                            <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center mb-6" aria-hidden="true">
                                <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-white mb-3">Smart Workout Tracking</h3>
                            <p class="text-zinc-400 text-sm leading-relaxed">Log sets, reps and weights with ease. Our system detects plateaus and suggests progressive overload strategies to keep you advancing.</p>
                        </div>

                        <div class="glass-card rounded-2xl p-8 transition-all group reveal reveal-delay-2">
                            <div class="w-12 h-12 rounded-xl bg-cyan-500/20 flex items-center justify-center mb-6" aria-hidden="true">
                                <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-white mb-3">Precision Nutrition</h3>
                            <p class="text-zinc-400 text-sm leading-relaxed">Track macros, calories and micronutrients with our extensive food database. Set custom goals and watch your progress update in real time.</p>
                        </div>

                        <div class="glass-card rounded-2xl p-8 transition-all group reveal reveal-delay-3">
                            <div class="w-12 h-12 rounded-xl bg-violet-500/20 flex items-center justify-center mb-6" aria-hidden="true">
                                <svg class="w-6 h-6 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-white mb-3">Intelligent Scheduling</h3>
                            <p class="text-zinc-400 text-sm leading-relaxed">Plan your training week with rotating splits. Never miss a session &#8212; our smart scheduler adapts to your lifestyle and recovery needs.</p>
                        </div>

                        <div class="glass-card rounded-2xl p-8 transition-all group reveal reveal-delay-1">
                            <div class="w-12 h-12 rounded-xl bg-orange-500/20 flex items-center justify-center mb-6" aria-hidden="true">
                                <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-white mb-3">Body Weight Goals</h3>
                            <p class="text-zinc-400 text-sm leading-relaxed">Set weight targets and track your journey with beautiful trend charts. We calculate your projected timeline so you know exactly where you&#8217;re headed.</p>
                        </div>

                        <div class="glass-card rounded-2xl p-8 transition-all group reveal reveal-delay-2">
                            <div class="w-12 h-12 rounded-xl bg-pink-500/20 flex items-center justify-center mb-6" aria-hidden="true">
                                <svg class="w-6 h-6 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-white mb-3">Deep Analytics</h3>
                            <p class="text-zinc-400 text-sm leading-relaxed">Visualise trends across weeks, months and years. Understand your patterns and make informed decisions backed by your own data.</p>
                        </div>

                        <div class="glass-card rounded-2xl p-8 transition-all group reveal reveal-delay-3">
                            <div class="w-12 h-12 rounded-xl bg-yellow-500/20 flex items-center justify-center mb-6" aria-hidden="true">
                                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-white mb-3">API Integrations</h3>
                            <p class="text-zinc-400 text-sm leading-relaxed">Connect Atlas Fit to your favourite apps and wearables. Our open API lets you push and pull data effortlessly across your entire ecosystem.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="how-it-works" class="py-24 px-6 border-y border-white/5" style="background:rgba(255,255,255,0.02)" aria-labelledby="how-it-works-heading">
                <div class="max-w-7xl mx-auto">
                    <div class="text-center mb-16 reveal">
                        <span class="text-emerald-400 text-sm font-semibold uppercase tracking-widest">Simple setup</span>
                        <h2 id="how-it-works-heading" class="text-4xl md:text-5xl font-black text-white mt-3 mb-4">Get started in 3 steps</h2>
                        <p class="text-zinc-400 max-w-xl mx-auto">From sign-up to your first logged workout in under 5 minutes. No complicated setup &#8212; just results.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                        <div class="text-center reveal reveal-delay-1">
                            <div class="relative inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 mb-6 mx-auto">
                                <span class="text-2xl font-black text-emerald-400" aria-hidden="true">1</span>
                                <div class="absolute -top-2 -right-2 w-5 h-5 bg-emerald-500 rounded-full flex items-center justify-center" aria-hidden="true">
                                    <svg class="w-3 h-3 text-black" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </div>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-3">Create your account</h3>
                            <p class="text-zinc-400 text-sm leading-relaxed">Sign up free in seconds. Enter your goals, current stats and training experience &#8212; we handle the rest.</p>
                        </div>

                        <div class="text-center reveal reveal-delay-2">
                            <div class="relative inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-cyan-500/20 border border-cyan-500/30 mb-6 mx-auto">
                                <span class="text-2xl font-black text-cyan-400" aria-hidden="true">2</span>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-3">Plan your programme</h3>
                            <p class="text-zinc-400 text-sm leading-relaxed">Build or choose from expert-designed workout splits. Set your nutrition targets and schedule your training week effortlessly.</p>
                        </div>

                        <div class="text-center reveal reveal-delay-3">
                            <div class="relative inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-violet-500/20 border border-violet-500/30 mb-6 mx-auto">
                                <span class="text-2xl font-black text-violet-400" aria-hidden="true">3</span>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-3">Track &amp; improve</h3>
                            <p class="text-zinc-400 text-sm leading-relaxed">Log workouts, meals and body stats daily. Watch your analytics evolve and celebrate every milestone on your journey.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-24 overflow-hidden" aria-labelledby="testimonials-heading">
                <div class="max-w-7xl mx-auto px-6 mb-12 text-center reveal">
                    <span class="text-emerald-400 text-sm font-semibold uppercase tracking-widest">Real stories</span>
                    <h2 id="testimonials-heading" class="text-4xl md:text-5xl font-black text-white mt-3 mb-3">What our members say</h2>
                    <div class="flex items-center justify-center gap-1 mt-2" aria-label="Rated 4.9 out of 5 stars by over 2,400 reviewers">
                        @for ($i = 0; $i < 5; $i++)
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                        <span class="ml-2 text-zinc-400 text-sm">4.9/5 from 2,400+ reviews</span>
                    </div>
                </div>

                @php
                    $testimonials = [
                        ['name' => 'Sarah M.', 'role' => 'Lost 18 kg in 6 months', 'quote' => 'Atlas Fit completely changed how I approach fitness. The nutrition tracking is so accurate and the workout logging is genuinely fun. I\'ve hit goals I never thought possible.', 'initials' => 'SM', 'color' => 'emerald'],
                        ['name' => 'James K.', 'role' => 'Gained 8 kg muscle mass', 'quote' => 'The scheduling feature is a game-changer. I always know exactly what session is next and my progressive overload is now automatic. Best fitness app I\'ve ever used.', 'initials' => 'JK', 'color' => 'cyan'],
                        ['name' => 'Priya L.', 'role' => 'Marathon runner', 'quote' => 'As a runner tracking both cardio and strength, Atlas Fit handles everything in one place. The analytics dashboard gives me insights my old spreadsheet never could.', 'initials' => 'PL', 'color' => 'violet'],
                        ['name' => 'Tom H.', 'role' => 'Personal trainer', 'quote' => 'I recommend Atlas Fit to all my clients. The API integrations with wearables are flawless and the data export features save me hours every week.', 'initials' => 'TH', 'color' => 'orange'],
                        ['name' => 'Elena R.', 'role' => 'Yoga & HIIT enthusiast', 'quote' => 'I was sceptical at first but the mobile-first design makes it so easy to log on the go. The macro goals have transformed my relationship with food completely.', 'initials' => 'ER', 'color' => 'pink'],
                        ['name' => 'Marcus D.', 'role' => 'Powerlifter', 'quote' => 'Tracking my compound lifts and seeing the progression graphs is incredibly motivating. The weight goal projection feature keeps me accountable every single day.', 'initials' => 'MD', 'color' => 'yellow'],
                    ];
                    $colorMap = ['emerald' => 'bg-emerald-500/20 text-emerald-400', 'cyan' => 'bg-cyan-500/20 text-cyan-400', 'violet' => 'bg-violet-500/20 text-violet-400', 'orange' => 'bg-orange-500/20 text-orange-400', 'pink' => 'bg-pink-500/20 text-pink-400', 'yellow' => 'bg-yellow-500/20 text-yellow-400'];
                    $all = array_merge($testimonials, $testimonials);
                @endphp

                <div class="relative overflow-hidden" aria-label="Customer testimonials">
                    <div class="testimonial-track" style="width: max-content;" role="list">
                        @foreach ($all as $t)
                            <article class="glass-card rounded-2xl p-6 w-80 shrink-0" role="listitem">
                                <div class="flex items-center gap-1 mb-4" role="img" aria-label="5 star review">
                                    @for ($i = 0; $i < 5; $i++)
                                        <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    @endfor
                                </div>
                                <blockquote>
                                    <p class="text-zinc-300 text-sm leading-relaxed mb-4">&#8220;{{ $t['quote'] }}&#8221;</p>
                                    <footer class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold shrink-0 {{ $colorMap[$t['color']] }}" aria-hidden="true">{{ $t['initials'] }}</div>
                                        <div>
                                            <cite class="text-white text-sm font-semibold not-italic">{{ $t['name'] }}</cite>
                                            <div class="text-zinc-500 text-xs">{{ $t['role'] }}</div>
                                        </div>
                                    </footer>
                                </blockquote>
                            </article>
                        @endforeach
                    </div>
                    <div class="absolute inset-y-0 left-0 w-24 pointer-events-none" aria-hidden="true" style="background: linear-gradient(to right, #0a0a0a, transparent);"></div>
                    <div class="absolute inset-y-0 right-0 w-24 pointer-events-none" aria-hidden="true" style="background: linear-gradient(to left, #0a0a0a, transparent);"></div>
                </div>
            </section>

            <section id="pricing" class="py-24 px-6 border-y border-white/5" style="background:rgba(255,255,255,0.02)" aria-labelledby="pricing-heading">
                <div class="max-w-7xl mx-auto">
                    <div class="text-center mb-16 reveal">
                        <span class="text-emerald-400 text-sm font-semibold uppercase tracking-widest">Transparent pricing</span>
                        <h2 id="pricing-heading" class="text-4xl md:text-5xl font-black text-white mt-3 mb-4">Choose your plan</h2>
                        <p class="text-zinc-400 max-w-xl mx-auto">Start free and upgrade when you&#8217;re ready. All plans include our core tracking features. No hidden fees, ever.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto items-start">
                        <div class="glass-card rounded-2xl p-8 reveal reveal-delay-1">
                            <h3 class="text-xl font-bold text-white mb-1">Starter</h3>
                            <p class="text-zinc-500 text-sm mb-6">Perfect for beginners</p>
                            <div class="flex items-end gap-1 mb-1"><span class="text-5xl font-black text-white">Free</span></div>
                            <p class="text-zinc-600 text-xs mb-8">Forever free, no credit card</p>
                            <ul class="space-y-3 mb-8" role="list" aria-label="Starter plan features">
                                @foreach(['Workout logging (up to 20/month)', 'Basic nutrition tracking', 'Weight tracking & goals', '7-day workout schedule', 'Community access'] as $feature)
                                    <li class="flex items-center gap-3 text-sm text-zinc-400">
                                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('register') }}" class="block w-full text-center py-3 rounded-xl border border-white/20 text-white font-medium hover:bg-white/5 transition-colors" aria-label="Get started free with the Starter plan">Get started free</a>
                        </div>

                        <div class="pricing-popular rounded-2xl reveal reveal-delay-2">
                            <div class="bg-[#0a0a0a] rounded-2xl p-8">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="text-xl font-bold text-white">Pro</h3>
                                    <span class="text-xs font-bold text-black bg-emerald-400 px-2.5 py-1 rounded-full">Most Popular</span>
                                </div>
                                <p class="text-zinc-500 text-sm mb-6">For serious athletes</p>
                                <div class="flex items-end gap-1 mb-1"><span class="text-5xl font-black text-white">&#8364;9</span><span class="text-zinc-500 text-sm pb-2">/month</span></div>
                                <p class="text-zinc-600 text-xs mb-8">Billed monthly, cancel anytime</p>
                                <ul class="space-y-3 mb-8" role="list" aria-label="Pro plan features">
                                    @foreach(['Unlimited workout logging', 'Advanced macro tracking', 'Full analytics dashboard', 'AI-powered insights', 'Rotating split scheduling', 'API access', 'Priority support'] as $feature)
                                        <li class="flex items-center gap-3 text-sm text-zinc-300">
                                            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                </ul>
                                <a href="{{ route('register') }}" class="block w-full text-center py-3 rounded-xl bg-emerald-500 text-black font-bold hover:bg-emerald-400 transition-colors" aria-label="Start the Pro plan for 9 euros per month">Start Pro &#8594;</a>
                            </div>
                        </div>

                        <div class="glass-card rounded-2xl p-8 reveal reveal-delay-3">
                            <h3 class="text-xl font-bold text-white mb-1">Elite</h3>
                            <p class="text-zinc-500 text-sm mb-6">For coaches &amp; teams</p>
                            <div class="flex items-end gap-1 mb-1"><span class="text-5xl font-black text-white">&#8364;24</span><span class="text-zinc-500 text-sm pb-2">/month</span></div>
                            <p class="text-zinc-600 text-xs mb-8">Up to 10 team members included</p>
                            <ul class="space-y-3 mb-8" role="list" aria-label="Elite plan features">
                                @foreach(['Everything in Pro', 'Coach dashboard', 'Up to 10 client accounts', 'White-label exports', 'Custom integrations', 'Dedicated account manager', 'SLA support'] as $feature)
                                    <li class="flex items-center gap-3 text-sm text-zinc-400">
                                        <svg class="w-4 h-4 text-violet-400 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('register') }}" class="block w-full text-center py-3 rounded-xl border border-violet-500/40 text-violet-400 font-medium hover:bg-violet-500/10 transition-colors" aria-label="Contact us about the Elite plan at 24 euros per month">Contact us</a>
                        </div>
                    </div>

                    <p class="text-center text-zinc-600 text-xs mt-8">
                        All prices include VAT. Prices shown in EUR.
                        <a href="#faq" class="text-zinc-400 hover:text-white underline underline-offset-2 transition-colors ml-1">See our refund policy &#8594;</a>
                    </p>
                </div>
            </section>

            <section class="py-24 px-6" aria-labelledby="stats-heading">
                <div class="max-w-7xl mx-auto">
                    <h2 id="stats-heading" class="sr-only">Atlas Fit by the numbers</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-8 reveal">
                        <div class="text-center">
                            <div class="text-5xl md:text-6xl font-black text-emerald-400 mb-2">50K+</div>
                            <div class="text-zinc-500 text-sm">Active members</div>
                        </div>
                        <div class="text-center">
                            <div class="text-5xl md:text-6xl font-black text-cyan-400 mb-2">98%</div>
                            <div class="text-zinc-500 text-sm">Satisfaction rate</div>
                        </div>
                        <div class="text-center">
                            <div class="text-5xl md:text-6xl font-black text-violet-400 mb-2">12 kg</div>
                            <div class="text-zinc-500 text-sm">Average weight lost</div>
                        </div>
                        <div class="text-center">
                            <div class="text-5xl md:text-6xl font-black text-orange-400 mb-2">8 wks</div>
                            <div class="text-zinc-500 text-sm">Average to see results</div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="faq" class="py-24 px-6 border-y border-white/5" style="background:rgba(255,255,255,0.02)" aria-labelledby="faq-heading">
                <div class="max-w-3xl mx-auto">
                    <div class="text-center mb-16 reveal">
                        <span class="text-emerald-400 text-sm font-semibold uppercase tracking-widest">Got questions?</span>
                        <h2 id="faq-heading" class="text-4xl md:text-5xl font-black text-white mt-3 mb-4">Frequently asked</h2>
                    </div>

                    @php
                        $faqs = [
                            ['q' => 'Is Atlas Fit really free to start?', 'a' => 'Yes! Our Starter plan is completely free with no credit card required. You get access to workout logging, nutrition tracking, weight goals and scheduling &#8212; everything you need to get started on your fitness journey.'],
                            ['q' => 'Can I cancel my subscription at any time?', 'a' => 'Absolutely. There are no lock-in contracts. You can cancel your Pro or Elite subscription at any time from your account settings and you\'ll retain access until the end of your billing period.'],
                            ['q' => 'How does Atlas Fit handle my personal data?', 'a' => 'We take your privacy seriously. Atlas Fit is fully GDPR compliant. Your health data is encrypted at rest and in transit, never sold to third parties, and you can request a full export or deletion of your data at any time. See our Privacy Policy for full details.'],
                            ['q' => 'Does Atlas Fit work on mobile devices?', 'a' => 'Atlas Fit is designed mobile-first and works beautifully on all screen sizes. Access your dashboard, log workouts and track nutrition seamlessly from any smartphone, tablet or desktop.'],
                            ['q' => 'What API integrations are supported?', 'a' => 'Pro and Elite plans include full API access to connect with popular fitness wearables and apps. Our REST API lets you push workout and nutrition data to and from Atlas Fit programmatically.'],
                            ['q' => 'Do you offer a money-back guarantee?', 'a' => 'We offer a 14-day money-back guarantee on all paid plans. If you\'re not completely satisfied within your first 14 days, contact our support team for a full refund &#8212; no questions asked.'],
                        ];
                    @endphp

                    <div class="space-y-3">
                        @foreach ($faqs as $idx => $faq)
                            <div class="faq-item glass-card rounded-2xl overflow-hidden reveal">
                                <button class="w-full flex items-center justify-between p-6 text-left transition-colors" aria-expanded="false" aria-controls="faq-answer-{{ $idx }}" type="button">
                                    <span class="text-white font-semibold text-sm md:text-base pr-4">{{ $faq['q'] }}</span>
                                    <svg class="faq-icon w-5 h-5 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>
                                <div id="faq-answer-{{ $idx }}" class="faq-answer" role="region">
                                    <p class="px-6 pb-6 text-zinc-400 text-sm leading-relaxed">{!! $faq['a'] !!}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="py-32 px-6" aria-labelledby="cta-heading">
                <div class="max-w-4xl mx-auto text-center reveal">
                    <h2 id="cta-heading" class="text-5xl md:text-7xl font-black text-white mb-6">
                        Ready to become<br>
                        <span class="gradient-text">unstoppable?</span>
                    </h2>
                    <p class="text-zinc-400 text-lg mb-10 max-w-xl mx-auto">Join over 50,000 members who have already transformed their health and fitness with Atlas Fit. Your journey starts today.</p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="w-full sm:w-auto px-10 py-4 text-base font-bold text-black bg-emerald-400 hover:bg-emerald-300 rounded-xl transition-all hover:scale-105 hover:shadow-[0_0_40px_rgba(16,185,129,0.5)]" aria-label="Create your free Atlas Fit account">
                                Create your free account &#8594;
                            </a>
                        @endif
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="w-full sm:w-auto px-10 py-4 text-base font-medium text-zinc-300 border border-white/20 hover:border-white/40 rounded-xl transition-all hover:bg-white/5">
                                Already have an account?
                            </a>
                        @endif
                    </div>
                    <p class="text-zinc-600 text-xs mt-6">Free forever plan available. No credit card required. GDPR compliant.</p>
                </div>
            </section>

        </main>

        <footer class="border-t border-white/10" style="background:rgba(0,0,0,0.5)" role="contentinfo">
            <div class="max-w-7xl mx-auto px-6 py-16">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 mb-12">
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-500" aria-hidden="true">
                                <x-app-logo-icon class="size-4 fill-current text-white" />
                            </div>
                            <span class="text-base font-bold text-white">Atlas<span class="text-emerald-400">Fit</span></span>
                        </div>
                        <p class="text-zinc-500 text-sm leading-relaxed mb-6 max-w-xs">Your intelligent health, fitness and nutrition companion. Built for real people with real goals.</p>
                        {{-- Social links: replace # with real URLs when social profiles are set up --}}
                        <div class="flex gap-3">
                            <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(255,255,255,0.05)" aria-label="Follow Atlas Fit on X (Twitter) — coming soon">
                                <svg class="w-4 h-4 text-zinc-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </span>
                            <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(255,255,255,0.05)" aria-label="Follow Atlas Fit on Instagram — coming soon">
                                <svg class="w-4 h-4 text-zinc-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </span>
                            <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(255,255,255,0.05)" aria-label="Connect with Atlas Fit on LinkedIn — coming soon">
                                <svg class="w-4 h-4 text-zinc-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </span>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-white font-semibold text-sm mb-4">Product</h3>
                        <ul class="space-y-3">
                            <li><a href="#features" class="text-zinc-500 hover:text-zinc-300 text-sm transition-colors">Features</a></li>
                            <li><a href="#pricing" class="text-zinc-500 hover:text-zinc-300 text-sm transition-colors">Pricing</a></li>
                            <li><a href="#how-it-works" class="text-zinc-500 hover:text-zinc-300 text-sm transition-colors">How it works</a></li>
                            <li><a href="{{ route('register') }}" class="text-zinc-500 hover:text-zinc-300 text-sm transition-colors">Sign up free</a></li>
                            <li><a href="{{ route('login') }}" class="text-zinc-500 hover:text-zinc-300 text-sm transition-colors">Log in</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-white font-semibold text-sm mb-4">Company</h3>
                        {{-- These links will be replaced with real routes as pages are built --}}
                        <ul class="space-y-3">
                            <li><span class="text-zinc-600 text-sm cursor-default">About us</span></li>
                            <li><span class="text-zinc-600 text-sm cursor-default">Blog</span></li>
                            <li><span class="text-zinc-600 text-sm cursor-default">Careers</span></li>
                            <li><span class="text-zinc-600 text-sm cursor-default">Press</span></li>
                            <li><span class="text-zinc-600 text-sm cursor-default">Contact</span></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-white font-semibold text-sm mb-4">Legal</h3>
                        {{-- These links will be replaced with real routes as legal pages are built --}}
                        <ul class="space-y-3">
                            <li><span class="text-zinc-600 text-sm cursor-default">Privacy Policy</span></li>
                            <li><span class="text-zinc-600 text-sm cursor-default">Terms of Service</span></li>
                            <li><span class="text-zinc-600 text-sm cursor-default">Cookie Policy</span></li>
                            <li><span class="text-zinc-600 text-sm cursor-default">GDPR</span></li>
                            <li><button id="cookie-settings-btn" class="text-zinc-500 hover:text-zinc-300 text-sm transition-colors text-left" type="button">Cookie Settings</button></li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-white/10 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                    <p class="text-zinc-600 text-xs">&copy; {{ date('Y') }} {{ config('app.name', 'Atlas Fit') }}. All rights reserved.</p>
                    <p class="text-zinc-700 text-xs text-center md:text-right max-w-md">Atlas Fit is a health and fitness tracking platform &#8212; not a medical device. Always consult a healthcare professional before starting any new exercise or nutrition programme.</p>
                </div>
            </div>
        </footer>

        <div id="cookie-banner" class="fixed bottom-0 left-0 right-0 z-50 p-4 md:p-6" role="dialog" aria-modal="false" aria-label="Cookie consent" aria-describedby="cookie-description">
            <div class="max-w-4xl mx-auto rounded-2xl p-6 md:p-8 shadow-2xl" style="background:#111;border:1px solid rgba(255,255,255,0.1)">
                <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <h3 class="text-white font-bold text-sm">We value your privacy</h3>
                        </div>
                        <p id="cookie-description" class="text-zinc-400 text-xs leading-relaxed">
                            We use cookies to enhance your experience, analyse site traffic and personalise content. By clicking &#8220;Accept All&#8221;, you consent to our use of cookies as described in our
                            <a href="#" class="text-emerald-400 hover:text-emerald-300 underline underline-offset-2" aria-label="View Cookie Policy (page coming soon)">Cookie Policy</a>.
                            You may manage your preferences or withdraw consent at any time.
                        </p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 shrink-0 w-full md:w-auto">
                        <button id="cookie-reject-btn" class="px-5 py-2.5 text-sm text-zinc-400 rounded-lg transition-colors whitespace-nowrap" style="border:1px solid rgba(255,255,255,0.15)" aria-label="Reject all non-essential cookies" type="button">Reject All</button>
                        <button id="cookie-preferences-btn" class="px-5 py-2.5 text-sm text-white rounded-lg transition-colors whitespace-nowrap" style="border:1px solid rgba(255,255,255,0.25)" aria-label="Manage cookie preferences" type="button">Manage Preferences</button>
                        <button id="cookie-accept-btn" class="px-5 py-2.5 text-sm font-semibold text-black bg-emerald-400 hover:bg-emerald-300 rounded-lg transition-colors whitespace-nowrap" aria-label="Accept all cookies" type="button">Accept All</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var navbar = document.getElementById('navbar');
                window.addEventListener('scroll', function () {
                    navbar.classList.toggle('scrolled', window.scrollY > 60);
                }, { passive: true });

                var mobileMenuBtn = document.getElementById('mobile-menu-btn');
                var mobileMenu    = document.getElementById('mobile-menu');
                if (mobileMenuBtn && mobileMenu) {
                    mobileMenuBtn.addEventListener('click', function () {
                        var open = mobileMenu.classList.toggle('open');
                        mobileMenuBtn.setAttribute('aria-expanded', String(open));
                    });
                    mobileMenu.querySelectorAll('a').forEach(function (a) {
                        a.addEventListener('click', function () {
                            mobileMenu.classList.remove('open');
                            mobileMenuBtn.setAttribute('aria-expanded', 'false');
                        });
                    });
                }

                document.querySelectorAll('a[href^="#"]').forEach(function (a) {
                    a.addEventListener('click', function (e) {
                        var id = this.getAttribute('href');
                        if (!id || id === '#') { e.preventDefault(); return; }
                        var el = document.querySelector(id);
                        if (el) { e.preventDefault(); el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
                    });
                });

                var revealEls = document.querySelectorAll('.reveal');
                if ('IntersectionObserver' in window) {
                    var obs = new IntersectionObserver(function (entries) {
                        entries.forEach(function (e) { if (e.isIntersecting) e.target.classList.add('visible'); });
                    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
                    revealEls.forEach(function (el) { obs.observe(el); });
                } else {
                    revealEls.forEach(function (el) { el.classList.add('visible'); });
                }

                document.querySelectorAll('.faq-item button').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var item   = this.closest('.faq-item');
                        var wasOpen = item.classList.contains('open');
                        document.querySelectorAll('.faq-item').forEach(function (it) {
                            it.classList.remove('open');
                            it.querySelector('.faq-answer').classList.remove('open');
                            it.querySelector('button').setAttribute('aria-expanded', 'false');
                        });
                        if (!wasOpen) {
                            item.classList.add('open');
                            item.querySelector('.faq-answer').classList.add('open');
                            this.setAttribute('aria-expanded', 'true');
                        }
                    });
                });

                var banner   = document.getElementById('cookie-banner');
                var consent  = null;
                try { consent = localStorage.getItem('atlas_cookie_consent'); } catch (e) {}
                if (consent) banner.classList.add('hidden');

                function dismiss(val) {
                    try { localStorage.setItem('atlas_cookie_consent', val); } catch (e) {}
                    banner.style.transform = 'translateY(100%)';
                    banner.style.opacity   = '0';
                    setTimeout(function () { banner.classList.add('hidden'); }, 500);
                }

                var a = document.getElementById('cookie-accept-btn');
                var r = document.getElementById('cookie-reject-btn');
                var p = document.getElementById('cookie-preferences-btn');
                var s = document.getElementById('cookie-settings-btn');
                if (a) a.addEventListener('click', function () { dismiss('accepted'); });
                if (r) r.addEventListener('click', function () { dismiss('rejected'); });
                if (p) p.addEventListener('click', function () { dismiss('preferences'); });
                if (s) s.addEventListener('click', function () {
                    try { localStorage.removeItem('atlas_cookie_consent'); } catch (e) {}
                    banner.style.transform = '';
                    banner.style.opacity   = '';
                    banner.classList.remove('hidden');
                });
            });
        </script>

    </body>
</html>
