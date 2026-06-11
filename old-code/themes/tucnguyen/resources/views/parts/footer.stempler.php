    <footer style="background: #0F172A; color: #94A3B8; padding: 100px 0 50px; margin-top: 100px; border-top: 1px solid rgba(255,255,255,0.05);">
        <div class="container" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1.5fr; gap: 60px; padding-bottom: 60px; border-bottom: 1px solid rgba(255,255,255,0.05);">
            <div class="footer-col">
                <div class="logo" style="color: white; margin-bottom: 24px; font-size: 24px; font-weight: 800;">
                    Presto<span>World.</span>
                </div>
                <p style="line-height: 1.6; max-width: 300px;">Optimized PHP framework for the next generation of web applications. Build faster, scale further.</p>
                <div style="display: flex; gap: 16px; margin-top: 30px;">
                    <a href="#" style="color: white; font-size: 18px;">𝕏</a>
                    <a href="#" style="color: white; font-size: 18px;">🐙</a>
                    <a href="#" style="color: white; font-size: 18px;">💬</a>
                </div>
            </div>
            <div class="footer-col">
                <h4 style="color: white; margin-bottom: 24px; font-size: 16px; font-weight: 700;">Framework</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px;">
                    <li><a href="#" style="color: inherit; text-decoration: none;">Documentation</a></li>
                    <li><a href="#" style="color: inherit; text-decoration: none;">Architecture</a></li>
                    <li><a href="#" style="color: inherit; text-decoration: none;">RoadRunner</a></li>
                    <li><a href="#" style="color: inherit; text-decoration: none;">Swoole</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4 style="color: white; margin-bottom: 24px; font-size: 16px; font-weight: 700;">Ecosystem</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px;">
                    <li><a href="#" style="color: inherit; text-decoration: none;">Components</a></li>
                    <li><a href="#" style="color: inherit; text-decoration: none;">Themes</a></li>
                    <li><a href="#" style="color: inherit; text-decoration: none;">Plugins</a></li>
                    <li><a href="#" style="color: inherit; text-decoration: none;">Community</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4 style="color: white; margin-bottom: 24px; font-size: 16px; font-weight: 700;">Newsletter</h4>
                <p style="font-size: 14px; margin-bottom: 20px;">Get latest updates on PrestoWorld releases directly in your inbox.</p>
                <div style="display: flex; gap: 8px;">
                    <input type="email" placeholder="Email address" style="flex: 1; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px 16px; border-radius: 8px; color: white;">
                    <button style="background: #6366F1; color: white; border: none; padding: 0 20px; border-radius: 8px; font-weight: 700; cursor: pointer;">Join</button>
                </div>
            </div>
        </div>
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; padding-top: 40px; font-size: 14px;">
            <div>© {{ date('Y') }} PrestoWorld Framework Core. All rights reserved.</div>
            <div style="display: flex; gap: 30px;">
                <a href="#" style="color: inherit; text-decoration: none;">Privacy Policy</a>
                <a href="#" style="color: inherit; text-decoration: none;">Terms of Service</a>
                <a href="#" style="color: inherit; text-decoration: none;">Status</a>
            </div>
        </div>
    </footer>
    {!! app(\Witals\Framework\Support\AssetManager::class)->renderJs() !!}
</body>
</html>
