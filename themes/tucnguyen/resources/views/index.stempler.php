@include('parts/header')

    <main class="pw-framework-landing">
        <!-- Hero Section -->
        <section class="hero container">
            <div class="hero-content">
                <div class="hero-badge">🚀 Next-Generation PHP Framework</div>
                <h1>Build <span class="gradient-text">state-of-the-art</span> applications with PrestoWorld</h1>
                <p>Welcome to the multi-runtime ecosystem designed for speed, scalability, and developer experience. Transition seamlessly between traditional FPM and long-running RoadRunner environments.</p>
                <div class="hero-btns">
                    <a href="/dashboard" class="btn-orange">Open Console</a>
                    <a href="https://prestoworld.com/docs" class="btn-outline" target="_blank">Read Documentation</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&q=80&w=800" alt="Code Architecture">
            </div>
        </section>

        <!-- Feature Grid -->
        <section class="features container">
            <div class="section-header">
                <h2>Why PrestoWorld?</h2>
                <p>Architected for the modern web with performance in mind.</p>
            </div>
            <div class="features-grid">
                <div class="feat-card">
                    <div class="feat-icon" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">⚡</div>
                    <h3>Multi-Runtime</h3>
                    <p>Detect and adapt to RoadRunner, Swoole, or Apache/Nginx runtimes automatically.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">🏗️</div>
                    <h3>Modular Core</h3>
                    <p>Full-featured component scanning and MU-plugin support for enterprise modularity.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">🎭</div>
                    <h3>Stempler Engine</h3>
                    <p>High-performance template engine with context-aware escaping and dynamic grammars.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">🛡️</div>
                    <h3>Secure by Design</h3>
                    <p>Strict architectural standards and robust exception handling out of the box.</p>
                </div>
            </div>
        </section>

        <!-- Quick Start -->
        <section class="quick-start">
            <div class="container">
                <div class="qs-card">
                    <div class="qs-content">
                        <h2>Ready to start?</h2>
                        <p>Join the thousands of developers building the future of the web with PrestoWorld Framework.</p>
                    </div>
                    <div class="qs-action">
                        <code>composer create-project prestoworld/core</code>
                        <a href="/dashboard" class="btn-white">Go to Admin</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <style>
        .pw-framework-landing { padding-bottom: 80px; }
        .hero { display: flex; align-items: center; gap: 60px; padding: 100px 0; }
        .hero-content { flex: 1; }
        .hero-badge { display: inline-block; padding: 6px 12px; background: rgba(99,102,241,0.1); color: #6366f1; border-radius: 20px; font-weight: 700; font-size: 14px; margin-bottom: 20px; }
        .hero h1 { font-size: 56px; font-weight: 850; line-height: 1.1; margin-bottom: 24px; color: #0f172a; }
        .gradient-text { background: linear-gradient(135deg, #6366F1, #A855F7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero p { font-size: 18px; color: #64748b; line-height: 1.6; margin-bottom: 40px; }
        .hero-btns { display: flex; gap: 16px; }
        .hero-image { flex: 1; }
        .hero-image img { width: 100%; border-radius: 24px; box-shadow: 0 40px 100px rgba(0,0,0,0.1); }
        .btn-orange { background: #f97316; color: white; padding: 14px 28px; border-radius: 12px; font-weight: 700; text-decoration: none; transition: 0.3s; }
        .btn-outline { border: 2px solid #e2e8f0; color: #0f172a; padding: 12px 28px; border-radius: 12px; font-weight: 700; text-decoration: none; transition: 0.3s; }
        .section-header { text-align: center; margin-bottom: 60px; }
        .section-header h2 { font-size: 36px; font-weight: 800; margin-bottom: 12px; }
        .features-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px; }
        .feat-card { padding: 32px; border: 1px solid #f1f5f9; border-radius: 24px; transition: 0.3s; }
        .feat-card:hover { border-color: #6366f1; transform: translateY(-5px); }
        .feat-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 20px; }
        .feat-card h3 { font-size: 20px; font-weight: 750; margin-bottom: 12px; }
        .feat-card p { color: #64748b; line-height: 1.5; font-size: 15px; }
        .quick-start { margin-top: 100px; }
        .qs-card { background: #0f172a; border-radius: 32px; padding: 60px; display: flex; align-items: center; justify-content: space-between; color: white; }
        .qs-content h2 { font-size: 40px; font-weight: 800; margin-bottom: 16px; }
        .qs-content p { color: #94a3b8; font-size: 18px; }
        .qs-action { text-align: right; }
        .qs-action code { display: block; background: rgba(255,255,255,0.05); padding: 12px 24px; border-radius: 12px; font-family: monospace; font-size: 16px; color: #4ade80; margin-bottom: 20px; }
        .btn-white { background: white; color: #0f172a; padding: 14px 28px; border-radius: 12px; font-weight: 700; text-decoration: none; display: inline-block; }
    </style>

@include('parts/footer')
