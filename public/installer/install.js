/**
 * PrestoWorld Installer — Real SPA Installer
 * Calls /api/setup/* backend endpoints and runs actual DB migrations.
 */
(function () {
  'use strict';

  // ─── State ───────────────────────────────────────────────────────────────
  const state = {
    step: 1,
    maxStep: 1,
    lang: 'vi',
    theme: 'light',
    db: {
      connection: 'pgsql',
      host: '127.0.0.1',
      port: '5432',
      name: 'prestoworld',
      username: 'prestoworld',
      password: '',
      prefix: 'pw_',
      tested: false,
    },
    site: {
      title: 'My PrestoWorld Site',
      admin_username: 'admin',
      admin_email: 'admin@example.com',
      admin_password: generatePassword(),
    },
    selectedTheme: 'timeless',
    installing: false,
    installProgress: 0,
    installed: false,
    envData: null,
    migrationResult: null,
    showAdminPass: false,
  };

  // ─── i18n ─────────────────────────────────────────────────────────────────
  const i18n = {
    vi: {
      brand: 'PrestoWorld',
      badge: 'v1.0 Installer',
      stepWelcome: 'Chào mừng',
      stepEnv: 'Môi trường',
      stepDb: 'Cơ sở dữ liệu',
      stepSite: 'Thông tin Website',
      stepInstall: 'Cài đặt',
      stepDone: 'Hoàn tất',
      next: 'Tiếp theo',
      back: 'Quay lại',
      install: 'Bắt đầu cài đặt',
      goToAdmin: 'Vào trang quản trị',
      welcomeTitle: 'Chào mừng đến PrestoWorld',
      welcomeDesc: 'Trình cài đặt hiện đại giúp bạn thiết lập PrestoWorld nhanh chóng và bảo mật. Hệ thống sẽ tự động chạy migrations, tạo schema database và ghi lại lịch sử đầy đủ.',
      feat1: 'Chạy migrations tự động với Cycle ORM',
      feat2: 'Ghi lịch sử migration đầy đủ vào bảng pw_migrations',
      feat3: 'Tạo tài khoản quản trị an toàn ngay khi cài đặt',
      feat4: 'Đánh dấu trạng thái installed trong pw_options',
      envTitle: 'Kiểm tra môi trường',
      envDesc: 'Hệ thống đã kiểm tra môi trường máy chủ của bạn.',
      dbTitle: 'Cấu hình Database',
      dbDesc: 'Nhập thông tin kết nối PostgreSQL hoặc MySQL. Hệ thống sẽ test kết nối trước khi tiến hành cài đặt.',
      testDb: 'Kiểm tra kết nối',
      dbTesting: 'Đang kiểm tra...',
      dbSuccess: 'Kết nối thành công',
      dbFail: 'Kết nối thất bại',
      dbMustTest: 'Vui lòng kiểm tra kết nối database trước',
      siteTitle: 'Thông tin Website',
      siteDesc: 'Đặt tên website và tài khoản quản trị viên.',
      installTitle: 'Đang cài đặt PrestoWorld',
      installDesc: 'Hệ thống đang chạy migrations, tạo bảng và khởi tạo dữ liệu ban đầu.',
      doneTitle: 'Cài đặt thành công! 🎉',
      doneDesc: 'PrestoWorld đã được cài đặt đầy đủ. Database đã được khởi tạo và lịch sử migration đã được ghi lại.',
      adminUrlLabel: 'Đường dẫn quản trị:',
      usernameLabel: 'Tên đăng nhập:',
      emailLabel: 'Email:',
      migrationsLabel: 'Migrations đã chạy:',
      lightMode: 'Sáng',
      darkMode: 'Tối',
    },
    en: {
      brand: 'PrestoWorld',
      badge: 'v1.0 Installer',
      stepWelcome: 'Welcome',
      stepEnv: 'Environment',
      stepDb: 'Database',
      stepSite: 'Site Info',
      stepInstall: 'Install',
      stepDone: 'Done',
      next: 'Continue',
      back: 'Back',
      install: 'Install Now',
      goToAdmin: 'Go to Dashboard',
      welcomeTitle: 'Welcome to PrestoWorld',
      welcomeDesc: 'The modern installer helps you set up PrestoWorld quickly and securely. The system will automatically run migrations, create the database schema and record a complete history.',
      feat1: 'Automatic migrations with Cycle ORM',
      feat2: 'Full migration history stored in pw_migrations',
      feat3: 'Secure admin account created during installation',
      feat4: 'Installation state tracked in pw_options',
      envTitle: 'Environment Check',
      envDesc: 'The system has scanned your server environment.',
      dbTitle: 'Database Configuration',
      dbDesc: 'Enter your PostgreSQL or MySQL connection details. The system will test the connection before proceeding.',
      testDb: 'Test Connection',
      dbTesting: 'Testing...',
      dbSuccess: 'Connection successful',
      dbFail: 'Connection failed',
      dbMustTest: 'Please test the database connection first',
      siteTitle: 'Site Information',
      siteDesc: 'Set your site name and administrator account.',
      installTitle: 'Installing PrestoWorld',
      installDesc: 'Running migrations, creating tables and seeding initial data.',
      doneTitle: 'Installation Complete! 🎉',
      doneDesc: 'PrestoWorld has been fully installed. The database is initialized and migration history has been recorded.',
      adminUrlLabel: 'Admin URL:',
      usernameLabel: 'Username:',
      emailLabel: 'Email:',
      migrationsLabel: 'Migrations run:',
      lightMode: 'Light',
      darkMode: 'Dark',
    },
  };

  function t(key) {
    return (i18n[state.lang] || i18n.vi)[key] || key;
  }

  // ─── Utilities ────────────────────────────────────────────────────────────
  function generatePassword() {
    const chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%^&*';
    const arr = new Uint8Array(16);
    crypto.getRandomValues(arr);
    return Array.from(arr).map(b => chars[b % chars.length]).join('');
  }

  function esc(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  function toast(msg, isError = false) {
    let container = document.getElementById('pw-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'pw-toast-container';
      container.className = 'pw-toast-container';
      document.body.appendChild(container);
    }
    const el = document.createElement('div');
    el.className = 'pw-toast' + (isError ? ' pw-toast-error' : '');
    el.innerHTML = isError
      ? `<span class="pw-toast-icon">✕</span>${esc(msg)}`
      : `<span class="pw-toast-icon">✓</span>${esc(msg)}`;
    container.appendChild(el);
    setTimeout(() => {
      el.style.opacity = '0';
      el.style.transform = 'translateX(30px)';
      setTimeout(() => el.remove(), 300);
    }, 3500);
  }

  // ─── Navigation ───────────────────────────────────────────────────────────
  function goToStep(n) {
    if (n < 1 || n > 6) return;
    state.step = n;
    if (n > state.maxStep) state.maxStep = n;
    render();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  // ─── API helpers ──────────────────────────────────────────────────────────
  async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      ...options,
    });
    const json = await res.json().catch(() => ({}));
    return { ok: res.ok, status: res.status, data: json };
  }

  // ─── DB config builder ───────────────────────────────────────────────────
  function getDbPayload() {
    const portNum = parseInt(state.db.port, 10) || (state.db.connection === 'pgsql' ? 5432 : 3306);
    return {
      connection: state.db.connection,
      host: state.db.host,
      port: portNum,
      name: state.db.name,
      username: state.db.username,
      password: state.db.password,
    };
  }

  function getInstallPayload() {
    const portNum = parseInt(state.db.port, 10) || (state.db.connection === 'pgsql' ? 5432 : 3306);
    return {
      db_connection: state.db.connection,
      db_host: state.db.host,
      db_port: portNum,
      db_name: state.db.name,
      db_username: state.db.username,
      db_password: state.db.password,
      db_prefix: state.db.prefix,
      site_title: state.site.title,
      admin_username: state.site.admin_username,
      admin_email: state.site.admin_email,
      admin_password: state.site.admin_password,
    };
  }

  // ─── Render ───────────────────────────────────────────────────────────────
  function render() {
    const root = document.getElementById('root');
    if (!root) return;

    root.innerHTML = `
      <div class="pw-app" data-theme="${esc(state.theme)}">
        ${renderHeader()}
        <div class="pw-layout">
          ${renderSidebar()}
          <main class="pw-content">
            ${renderStep()}
          </main>
        </div>
      </div>
    `;
    bindEvents();
  }

  function renderHeader() {
    return `
      <header class="pw-header">
        <div class="pw-header-brand">
          <div class="pw-logo">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
            </svg>
          </div>
          <span class="pw-brand-name">${esc(t('brand'))}</span>
          <span class="pw-brand-badge">${esc(t('badge'))}</span>
        </div>
        <div class="pw-header-actions">
          <select class="pw-lang-select" id="lang-select">
            <option value="vi" ${state.lang === 'vi' ? 'selected' : ''}>🇻🇳 Tiếng Việt</option>
            <option value="en" ${state.lang === 'en' ? 'selected' : ''}>🇺🇸 English</option>
          </select>
          <button class="pw-theme-btn" id="theme-toggle" title="${t('lightMode')} / ${t('darkMode')}">
            ${state.theme === 'light'
              ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`
              : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`
            }
          </button>
        </div>
      </header>
    `;
  }

  const STEPS = ['stepWelcome', 'stepEnv', 'stepDb', 'stepSite', 'stepInstall', 'stepDone'];

  function renderSidebar() {
    const items = STEPS.map((key, i) => {
      const num = i + 1;
      const isActive = state.step === num;
      const isDone = state.step > num;
      const isDisabled = num > state.maxStep && !isDone;
      let cls = 'pw-step-item';
      if (isActive) cls += ' active';
      if (isDone) cls += ' done';
      if (isDisabled) cls += ' disabled';

      return `
        <li>
          <button class="${cls}" ${isDisabled ? 'disabled' : ''} data-step="${num}">
            <div class="pw-step-num">
              ${isDone
                ? `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>`
                : num
              }
            </div>
            <span class="pw-step-label">${esc(t(key))}</span>
          </button>
        </li>
      `;
    }).join('');

    return `
      <nav class="pw-sidebar">
        <div class="pw-sidebar-title">${state.lang === 'vi' ? 'Tiến trình' : 'Progress'}</div>
        <ul class="pw-step-list">${items}</ul>
        <div class="pw-sidebar-footer">
          <div class="pw-sidebar-tech">PostgreSQL · Cycle ORM · PHP 8.2+</div>
        </div>
      </nav>
    `;
  }

  function renderStep() {
    switch (state.step) {
      case 1: return renderWelcome();
      case 2: return renderEnv();
      case 3: return renderDb();
      case 4: return renderSiteInfo();
      case 5: return renderInstall();
      case 6: return renderDone();
      default: return '';
    }
  }

  // ─── Step 1: Welcome ──────────────────────────────────────────────────────
  function renderWelcome() {
    return `
      <div class="pw-card">
        <div class="pw-card-header">
          <div class="pw-card-kicker">01 / 06</div>
          <h1 class="pw-card-title">${esc(t('welcomeTitle'))}</h1>
          <p class="pw-card-desc">${esc(t('welcomeDesc'))}</p>
        </div>
        <div class="pw-card-body">
          <div class="pw-feature-grid">
            <div class="pw-feature-item">
              <div class="pw-feature-icon">🗃️</div>
              <div>${esc(t('feat1'))}</div>
            </div>
            <div class="pw-feature-item">
              <div class="pw-feature-icon">📋</div>
              <div>${esc(t('feat2'))}</div>
            </div>
            <div class="pw-feature-item">
              <div class="pw-feature-icon">🔐</div>
              <div>${esc(t('feat3'))}</div>
            </div>
            <div class="pw-feature-item">
              <div class="pw-feature-icon">✅</div>
              <div>${esc(t('feat4'))}</div>
            </div>
          </div>
        </div>
        <div class="pw-card-footer">
          <div></div>
          <button class="pw-btn pw-btn-primary" id="btn-next-1">
            ${esc(t('next'))}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
          </button>
        </div>
      </div>
    `;
  }

  // ─── Step 2: Environment ──────────────────────────────────────────────────
  function renderEnv() {
    const env = state.envData;

    const checks = env ? [
      { label: 'PHP Version', val: env.php_version, ok: true },
      { label: 'PDO Available', val: env.pdo_available ? 'Yes' : 'No', ok: env.pdo_available },
      { label: 'PostgreSQL (pdo_pgsql)', val: env.has_pdo_pgsql ? 'Available' : 'Not found', ok: env.has_pdo_pgsql },
      { label: 'MySQL (pdo_mysql)', val: env.has_pdo_mysql ? 'Available' : 'Not found', ok: env.has_pdo_mysql },
      { label: 'OpenSSL', val: env.has_openssl ? 'Enabled' : 'Missing', ok: env.has_openssl },
      { label: 'cURL', val: env.has_curl ? 'Enabled' : 'Missing', ok: env.has_curl },
      { label: 'Mbstring', val: env.has_mbstring ? 'Enabled' : 'Missing', ok: env.has_mbstring },
      { label: 'Memory Limit', val: env.memory_limit, ok: true },
      { label: 'Max Execution Time', val: env.max_execution_time + 's', ok: true },
    ] : [];

    return `
      <div class="pw-card">
        <div class="pw-card-header">
          <div class="pw-card-kicker">02 / 06</div>
          <h2 class="pw-card-title">${esc(t('envTitle'))}</h2>
          <p class="pw-card-desc">${esc(t('envDesc'))}</p>
        </div>
        <div class="pw-card-body">
          ${!env ? `
            <div class="pw-callout pw-callout-info" id="env-loading">
              <span class="pw-spinner"></span>
              <span>${state.lang === 'vi' ? 'Đang tải thông tin môi trường...' : 'Loading environment data...'}</span>
            </div>
          ` : `
            <div class="pw-check-list">
              ${checks.map(c => `
                <div class="pw-check-item">
                  <div class="pw-check-info">
                    <span class="pw-check-label">${esc(c.label)}</span>
                    <span class="pw-check-val">${esc(c.val)}</span>
                  </div>
                  <span class="pw-badge ${c.ok ? 'pw-badge-ok' : 'pw-badge-warn'}">
                    ${c.ok ? '✓' : '⚠'}
                  </span>
                </div>
              `).join('')}
            </div>
          `}
        </div>
        <div class="pw-card-footer">
          <button class="pw-btn pw-btn-ghost" id="btn-back-2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            ${esc(t('back'))}
          </button>
          <button class="pw-btn pw-btn-primary" id="btn-next-2" ${!env ? 'disabled' : ''}>
            ${esc(t('next'))}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
          </button>
        </div>
      </div>
    `;
  }

  // ─── Step 3: Database ─────────────────────────────────────────────────────
  function renderDb() {
    return `
      <div class="pw-card">
        <div class="pw-card-header">
          <div class="pw-card-kicker">03 / 06</div>
          <h2 class="pw-card-title">${esc(t('dbTitle'))}</h2>
          <p class="pw-card-desc">${esc(t('dbDesc'))}</p>
        </div>
        <div class="pw-card-body">
          <div class="pw-quick-presets">
            <span class="pw-preset-label">${state.lang === 'vi' ? 'Nhanh:' : 'Quick:'}</span>
            <button class="pw-preset-btn" id="preset-pgsql-local">PostgreSQL Local</button>
            <button class="pw-preset-btn" id="preset-mysql-local">MySQL Local</button>
          </div>
          <div class="pw-form-grid">
            <div class="pw-form-row">
              <label class="pw-label" for="db-connection">${state.lang === 'vi' ? 'Loại kết nối' : 'Connection Type'}</label>
              <select class="pw-select" id="db-connection">
                <option value="pgsql" ${state.db.connection === 'pgsql' ? 'selected' : ''}>PostgreSQL</option>
                <option value="mysql" ${state.db.connection === 'mysql' ? 'selected' : ''}>MySQL / MariaDB</option>
                <option value="sqlite" ${state.db.connection === 'sqlite' ? 'selected' : ''}>SQLite</option>
              </select>
            </div>
            ${state.db.connection !== 'sqlite' ? `
              <div class="pw-form-row-2col">
                <div class="pw-form-row">
                  <label class="pw-label" for="db-host">${state.lang === 'vi' ? 'Máy chủ' : 'Host'}</label>
                  <input class="pw-input" id="db-host" type="text" value="${esc(state.db.host)}" placeholder="127.0.0.1">
                </div>
                <div class="pw-form-row">
                  <label class="pw-label" for="db-port">Port</label>
                  <input class="pw-input" id="db-port" type="text" value="${esc(state.db.port)}" placeholder="${state.db.connection === 'pgsql' ? '5432' : '3306'}">
                </div>
              </div>
              <div class="pw-form-row">
                <label class="pw-label" for="db-name">${state.lang === 'vi' ? 'Tên database' : 'Database Name'}</label>
                <input class="pw-input" id="db-name" type="text" value="${esc(state.db.name)}" placeholder="prestoworld">
              </div>
              <div class="pw-form-row-2col">
                <div class="pw-form-row">
                  <label class="pw-label" for="db-username">${state.lang === 'vi' ? 'Tên đăng nhập' : 'Username'}</label>
                  <input class="pw-input" id="db-username" type="text" value="${esc(state.db.username)}" placeholder="prestoworld">
                </div>
                <div class="pw-form-row">
                  <label class="pw-label" for="db-password">${state.lang === 'vi' ? 'Mật khẩu' : 'Password'}</label>
                  <input class="pw-input" id="db-password" type="password" value="${esc(state.db.password)}" placeholder="${state.lang === 'vi' ? 'Để trống nếu không có' : 'Leave empty if none'}">
                </div>
              </div>
            ` : `
              <div class="pw-form-row">
                <label class="pw-label" for="db-name">${state.lang === 'vi' ? 'Đường dẫn file SQLite' : 'SQLite File Path'}</label>
                <input class="pw-input" id="db-name" type="text" value="${esc(state.db.name)}" placeholder="/path/to/database.sqlite">
              </div>
            `}
            <div class="pw-form-row">
              <label class="pw-label" for="db-prefix">${state.lang === 'vi' ? 'Tiền tố bảng' : 'Table Prefix'}</label>
              <input class="pw-input pw-input-mono" id="db-prefix" type="text" value="${esc(state.db.prefix)}" placeholder="pw_">
            </div>
          </div>

          <div id="db-test-area" style="margin-top:1.5rem;">
            <button class="pw-btn pw-btn-secondary" id="btn-test-db">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.5a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.63h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 10.19a16 16 0 0 0 6 6l1.92-1.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
              ${esc(t('testDb'))}
            </button>
            <div id="db-test-result" style="margin-top:1rem;"></div>
          </div>
        </div>
        <div class="pw-card-footer">
          <button class="pw-btn pw-btn-ghost" id="btn-back-3">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            ${esc(t('back'))}
          </button>
          <button class="pw-btn pw-btn-primary" id="btn-next-3">
            ${esc(t('next'))}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
          </button>
        </div>
      </div>
    `;
  }

  // ─── Step 4: Site Info ────────────────────────────────────────────────────
  function renderSiteInfo() {
    const strength = passwordStrength(state.site.admin_password);
    return `
      <div class="pw-card">
        <div class="pw-card-header">
          <div class="pw-card-kicker">04 / 06</div>
          <h2 class="pw-card-title">${esc(t('siteTitle'))}</h2>
          <p class="pw-card-desc">${esc(t('siteDesc'))}</p>
        </div>
        <div class="pw-card-body">
          <div class="pw-form-grid">
            <div class="pw-form-row">
              <label class="pw-label" for="site-title">${state.lang === 'vi' ? 'Tên website' : 'Site Title'}</label>
              <input class="pw-input" id="site-title" type="text" value="${esc(state.site.title)}" placeholder="My PrestoWorld Site">
            </div>
            <div class="pw-form-row">
              <label class="pw-label" for="admin-username">${state.lang === 'vi' ? 'Tên đăng nhập quản trị' : 'Admin Username'}</label>
              <input class="pw-input" id="admin-username" type="text" value="${esc(state.site.admin_username)}" placeholder="admin">
            </div>
            <div class="pw-form-row">
              <label class="pw-label" for="admin-email">Email</label>
              <input class="pw-input" id="admin-email" type="email" value="${esc(state.site.admin_email)}" placeholder="admin@example.com">
            </div>
            <div class="pw-form-row">
              <label class="pw-label" for="admin-password">
                ${state.lang === 'vi' ? 'Mật khẩu quản trị' : 'Admin Password'}
                <button class="pw-btn-link" id="btn-gen-pass" type="button">
                  ${state.lang === 'vi' ? 'Tạo ngẫu nhiên' : 'Generate'}
                </button>
              </label>
              <div class="pw-input-with-toggle">
                <input class="pw-input" id="admin-password" type="${state.showAdminPass ? 'text' : 'password'}" value="${esc(state.site.admin_password)}">
                <button class="pw-eye-btn" id="btn-toggle-pass" type="button" title="Show/Hide">
                  ${state.showAdminPass
                    ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`
                    : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`
                  }
                </button>
              </div>
              <div class="pw-pass-strength">
                <div class="pw-pass-bar">
                  <div class="pw-pass-fill ${strength.cls}" style="width:${strength.pct}%"></div>
                </div>
                <span class="pw-pass-label">${esc(strength.label)}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="pw-card-footer">
          <button class="pw-btn pw-btn-ghost" id="btn-back-4">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            ${esc(t('back'))}
          </button>
          <button class="pw-btn pw-btn-primary" id="btn-next-4">
            ${esc(t('next'))}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
          </button>
        </div>
      </div>
    `;
  }

  function passwordStrength(p) {
    if (!p || p.length < 6) return { cls: 'very-weak', pct: 15, label: state.lang === 'vi' ? 'Rất yếu' : 'Very weak' };
    let score = 0;
    if (p.length >= 8) score++;
    if (p.length >= 12) score++;
    if (/[A-Z]/.test(p)) score++;
    if (/[0-9]/.test(p)) score++;
    if (/[^A-Za-z0-9]/.test(p)) score++;
    if (score <= 1) return { cls: 'weak', pct: 30, label: state.lang === 'vi' ? 'Yếu' : 'Weak' };
    if (score <= 3) return { cls: 'medium', pct: 60, label: state.lang === 'vi' ? 'Trung bình' : 'Medium' };
    return { cls: 'strong', pct: 100, label: state.lang === 'vi' ? 'Mạnh' : 'Strong' };
  }

  // ─── Step 5: Install ──────────────────────────────────────────────────────
  function renderInstall() {
    return `
      <div class="pw-card">
        <div class="pw-card-header">
          <div class="pw-card-kicker">05 / 06</div>
          <h2 class="pw-card-title">${esc(t('installTitle'))}</h2>
          <p class="pw-card-desc">${esc(t('installDesc'))}</p>
        </div>
        <div class="pw-card-body">
          <div class="pw-progress-section">
            <div class="pw-progress-header">
              <span id="install-status-text">${state.installing
                ? (state.lang === 'vi' ? 'Đang cài đặt...' : 'Installing...')
                : (state.lang === 'vi' ? 'Sẵn sàng cài đặt' : 'Ready to install')
              }</span>
              <span id="install-pct" class="pw-progress-pct">${state.installProgress}%</span>
            </div>
            <div class="pw-progress-track">
              <div class="pw-progress-fill" id="install-bar" style="width:${state.installProgress}%"></div>
            </div>
          </div>

          <div class="pw-install-summary">
            <div class="pw-summary-row">
              <span>${state.lang === 'vi' ? 'Database' : 'Database'}:</span>
              <code>${esc(state.db.connection)}://${esc(state.db.name)}</code>
            </div>
            <div class="pw-summary-row">
              <span>${state.lang === 'vi' ? 'Tiền tố bảng' : 'Table prefix'}:</span>
              <code>${esc(state.db.prefix)}</code>
            </div>
            <div class="pw-summary-row">
              <span>${state.lang === 'vi' ? 'Tên website' : 'Site title'}:</span>
              <code>${esc(state.site.title)}</code>
            </div>
            <div class="pw-summary-row">
              <span>${state.lang === 'vi' ? 'Admin' : 'Admin'}:</span>
              <code>${esc(state.site.admin_username)} &lt;${esc(state.site.admin_email)}&gt;</code>
            </div>
          </div>

          <div class="pw-terminal">
            <div class="pw-terminal-bar">
              <div class="pw-terminal-dots">
                <span class="pw-dot pw-dot-red"></span>
                <span class="pw-dot pw-dot-yellow"></span>
                <span class="pw-dot pw-dot-green"></span>
              </div>
              <span class="pw-terminal-title">prestoworld/install-runner</span>
            </div>
            <div class="pw-terminal-body" id="terminal-body">
              <div class="pw-log-line pw-log-info">[INIT] ${state.lang === 'vi' ? 'Sẵn sàng khởi động trình cài đặt PrestoWorld...' : 'Ready to start PrestoWorld installer...'}</div>
              <div class="pw-log-line">[DB] ${esc(state.db.connection)} → ${esc(state.db.name)}@${esc(state.db.host)}</div>
            </div>
          </div>
        </div>
        <div class="pw-card-footer">
          <button class="pw-btn pw-btn-ghost" id="btn-back-5" ${state.installing ? 'disabled' : ''}>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            ${esc(t('back'))}
          </button>
          <button class="pw-btn pw-btn-primary pw-btn-install" id="btn-run-install" ${state.installing ? 'disabled' : ''}>
            ${state.installing
              ? `<span class="pw-spinner-sm"></span> ${state.lang === 'vi' ? 'Đang cài đặt...' : 'Installing...'}`
              : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg> ${esc(t('install'))}`
            }
          </button>
        </div>
      </div>
    `;
  }

  // ─── Step 6: Done ─────────────────────────────────────────────────────────
  function renderDone() {
    const result = state.migrationResult || {};
    const created = result.created || [];
    const skipped = result.skipped || [];

    return `
      <div class="pw-card">
        <div class="pw-card-body pw-done-body">
          <div class="pw-success-circle">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          </div>
          <h2 class="pw-done-title">${esc(t('doneTitle'))}</h2>
          <p class="pw-done-desc">${esc(t('doneDesc'))}</p>

          <div class="pw-credentials-box">
            <div class="pw-cred-row">
              <span class="pw-cred-label">${esc(t('adminUrlLabel'))}</span>
              <a href="/dashboard" class="pw-cred-link">/dashboard</a>
            </div>
            <div class="pw-cred-row">
              <span class="pw-cred-label">${esc(t('usernameLabel'))}</span>
              <code class="pw-cred-code">${esc(state.site.admin_username)}</code>
            </div>
            <div class="pw-cred-row">
              <span class="pw-cred-label">${esc(t('emailLabel'))}</span>
              <code class="pw-cred-code">${esc(state.site.admin_email)}</code>
            </div>
          </div>

          ${created.length > 0 ? `
            <div class="pw-migration-result">
              <div class="pw-migration-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                ${esc(t('migrationsLabel'))} <strong>${created.length}</strong>
              </div>
              <ul class="pw-migration-list">
                ${created.map(m => `<li class="pw-migration-item pw-migration-ok">✓ ${esc(m)}</li>`).join('')}
                ${skipped.map(m => `<li class="pw-migration-item pw-migration-skip">↷ ${esc(m)}</li>`).join('')}
              </ul>
            </div>
          ` : ''}

          <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;margin-top:2rem;">
            <a href="/dashboard" class="pw-btn pw-btn-primary">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
              ${esc(t('goToAdmin'))}
            </a>
          </div>
        </div>
      </div>
    `;
  }

  // ─── Event Binding ────────────────────────────────────────────────────────
  function bindEvents() {
    // Header
    const langSel = document.getElementById('lang-select');
    if (langSel) langSel.addEventListener('change', e => { state.lang = e.target.value; render(); });

    const themeBtn = document.getElementById('theme-toggle');
    if (themeBtn) themeBtn.addEventListener('click', () => {
      state.theme = state.theme === 'light' ? 'dark' : 'light';
      render();
    });

    // Sidebar steps
    document.querySelectorAll('[data-step]').forEach(btn => {
      btn.addEventListener('click', () => {
        const n = parseInt(btn.dataset.step, 10);
        if (n <= state.maxStep) goToStep(n);
      });
    });

    // Step 1
    const btnNext1 = document.getElementById('btn-next-1');
    if (btnNext1) btnNext1.addEventListener('click', () => {
      goToStep(2);
      loadEnvData();
    });

    // Step 2
    const btnBack2 = document.getElementById('btn-back-2');
    if (btnBack2) btnBack2.addEventListener('click', () => goToStep(1));
    const btnNext2 = document.getElementById('btn-next-2');
    if (btnNext2) btnNext2.addEventListener('click', () => goToStep(3));

    // Step 3
    const btnBack3 = document.getElementById('btn-back-3');
    if (btnBack3) btnBack3.addEventListener('click', () => goToStep(2));

    const btnNext3 = document.getElementById('btn-next-3');
    if (btnNext3) btnNext3.addEventListener('click', () => {
      if (!state.db.tested) {
        toast(t('dbMustTest'), true);
        return;
      }
      goToStep(4);
    });

    // DB inputs
    const dbConn = document.getElementById('db-connection');
    if (dbConn) dbConn.addEventListener('change', e => {
      state.db.connection = e.target.value;
      state.db.tested = false;
      if (e.target.value === 'pgsql') state.db.port = '5432';
      if (e.target.value === 'mysql') state.db.port = '3306';
      render();
    });
    ['db-host', 'db-port', 'db-name', 'db-username', 'db-password', 'db-prefix'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.addEventListener('input', e => {
        const key = id.replace('db-', '').replace('-', '_');
        const map = { 'db-host': 'host', 'db-port': 'port', 'db-name': 'name', 'db-username': 'username', 'db-password': 'password', 'db-prefix': 'prefix' };
        state.db[map[id] || key] = e.target.value;
        state.db.tested = false;
      });
    });

    // Presets
    const presetPgsql = document.getElementById('preset-pgsql-local');
    if (presetPgsql) presetPgsql.addEventListener('click', () => {
      Object.assign(state.db, { connection: 'pgsql', host: '127.0.0.1', port: '5432', name: 'prestoworld', username: 'prestoworld', password: 'prestoworld', prefix: 'pw_', tested: false });
      render();
    });
    const presetMysql = document.getElementById('preset-mysql-local');
    if (presetMysql) presetMysql.addEventListener('click', () => {
      Object.assign(state.db, { connection: 'mysql', host: '127.0.0.1', port: '3306', name: 'prestoworld', username: 'root', password: '', prefix: 'pw_', tested: false });
      render();
    });

    const btnTestDb = document.getElementById('btn-test-db');
    if (btnTestDb) btnTestDb.addEventListener('click', runTestDb);

    // Step 4
    const btnBack4 = document.getElementById('btn-back-4');
    if (btnBack4) btnBack4.addEventListener('click', () => goToStep(3));
    const btnNext4 = document.getElementById('btn-next-4');
    if (btnNext4) btnNext4.addEventListener('click', () => {
      const title = document.getElementById('site-title')?.value?.trim();
      const user = document.getElementById('admin-username')?.value?.trim();
      const email = document.getElementById('admin-email')?.value?.trim();
      const pass = document.getElementById('admin-password')?.value;
      if (!title || !user || !email || !pass) {
        toast(state.lang === 'vi' ? 'Vui lòng điền đầy đủ thông tin' : 'Please fill in all fields', true);
        return;
      }
      state.site.title = title;
      state.site.admin_username = user;
      state.site.admin_email = email;
      state.site.admin_password = pass;
      goToStep(5);
    });

    ['site-title', 'admin-username', 'admin-email'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.addEventListener('input', e => {
        const map = { 'site-title': 'title', 'admin-username': 'admin_username', 'admin-email': 'admin_email' };
        state.site[map[id]] = e.target.value;
      });
    });

    const adminPass = document.getElementById('admin-password');
    if (adminPass) adminPass.addEventListener('input', e => {
      state.site.admin_password = e.target.value;
      // update strength bar without full re-render
      const strength = passwordStrength(e.target.value);
      const fill = document.querySelector('.pw-pass-fill');
      const label = document.querySelector('.pw-pass-label');
      if (fill) { fill.className = 'pw-pass-fill ' + strength.cls; fill.style.width = strength.pct + '%'; }
      if (label) label.textContent = strength.label;
    });

    const btnGenPass = document.getElementById('btn-gen-pass');
    if (btnGenPass) btnGenPass.addEventListener('click', () => {
      state.site.admin_password = generatePassword();
      state.showAdminPass = true;
      render();
      toast(state.lang === 'vi' ? 'Đã tạo mật khẩu mới!' : 'New password generated!');
    });

    const btnTogglePass = document.getElementById('btn-toggle-pass');
    if (btnTogglePass) btnTogglePass.addEventListener('click', () => {
      // Save current password first
      const currentPass = document.getElementById('admin-password')?.value;
      if (currentPass !== undefined) state.site.admin_password = currentPass;
      state.showAdminPass = !state.showAdminPass;
      render();
    });

    // Step 5
    const btnBack5 = document.getElementById('btn-back-5');
    if (btnBack5) btnBack5.addEventListener('click', () => goToStep(4));
    const btnRunInstall = document.getElementById('btn-run-install');
    if (btnRunInstall) btnRunInstall.addEventListener('click', runInstallation);
  }

  // ─── Load Env Data ────────────────────────────────────────────────────────
  async function loadEnvData() {
    if (state.envData) return;
    try {
      const { ok, data } = await apiFetch('/api/setup/env');
      if (ok) {
        state.envData = data;
        // Only re-render if still on step 2
        if (state.step === 2) render();
      }
    } catch (e) {
      console.error('Failed to load env data:', e);
    }
  }

  // ─── Test Database ────────────────────────────────────────────────────────
  async function runTestDb() {
    const btn = document.getElementById('btn-test-db');
    const resultDiv = document.getElementById('db-test-result');
    if (!resultDiv) return;

    // Capture current form values
    state.db.host = document.getElementById('db-host')?.value || state.db.host;
    state.db.port = document.getElementById('db-port')?.value || state.db.port;
    state.db.name = document.getElementById('db-name')?.value || state.db.name;
    state.db.username = document.getElementById('db-username')?.value || state.db.username;
    state.db.password = document.getElementById('db-password')?.value || state.db.password;

    if (btn) btn.disabled = true;
    resultDiv.innerHTML = `<div class="pw-callout pw-callout-info"><span class="pw-spinner"></span> ${state.lang === 'vi' ? 'Đang kết nối...' : 'Connecting...'}</div>`;

    try {
      const { ok, data } = await apiFetch('/api/setup/test-db', {
        method: 'POST',
        body: JSON.stringify(getDbPayload()),
      });

      if (ok && data.success) {
        state.db.tested = true;
        resultDiv.innerHTML = `
          <div class="pw-callout pw-callout-success">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <span>${esc(t('dbSuccess'))} — ${esc(String(data.tables_count || 0))} ${state.lang === 'vi' ? 'bảng hiện có' : 'existing tables'}</span>
          </div>`;
        toast(state.lang === 'vi' ? 'Kết nối database thành công!' : 'Database connection successful!');
      } else {
        state.db.tested = false;
        resultDiv.innerHTML = `
          <div class="pw-callout pw-callout-danger">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span>${esc(t('dbFail'))}: ${esc(data.error || 'Unknown error')}</span>
          </div>`;
        toast(t('dbFail'), true);
      }
    } catch (err) {
      state.db.tested = false;
      resultDiv.innerHTML = `<div class="pw-callout pw-callout-danger">${state.lang === 'vi' ? 'Lỗi mạng' : 'Network error'}: ${esc(err.message)}</div>`;
      toast(err.message, true);
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  // ─── Run Installation ─────────────────────────────────────────────────────
  async function runInstallation() {
    if (state.installing) return;
    state.installing = true;
    state.installProgress = 5;
    render();

    const terminal = document.getElementById('terminal-body');
    const bar = document.getElementById('install-bar');
    const pct = document.getElementById('install-pct');
    const statusText = document.getElementById('install-status-text');

    function log(msg, type = '') {
      if (!terminal) return;
      const line = document.createElement('div');
      line.className = 'pw-log-line' + (type ? ' pw-log-' + type : '');
      line.textContent = msg;
      terminal.appendChild(line);
      terminal.scrollTop = terminal.scrollHeight;
    }

    function setProgress(p, msg) {
      state.installProgress = p;
      if (bar) bar.style.width = p + '%';
      if (pct) pct.textContent = p + '%';
      if (statusText) statusText.textContent = msg;
    }

    log('[INIT] ' + (state.lang === 'vi' ? 'Khởi động tiến trình cài đặt PrestoWorld...' : 'Starting PrestoWorld installation...'));
    setProgress(10, state.lang === 'vi' ? 'Chuẩn bị kết nối database...' : 'Preparing database connection...');

    await sleep(400);
    log('[DB] ' + state.db.connection + '://' + state.db.name + '@' + state.db.host + ':' + state.db.port, 'info');
    setProgress(20, state.lang === 'vi' ? 'Đang gửi yêu cầu cài đặt...' : 'Sending installation request...');

    await sleep(300);
    log('[MIGRATION] ' + (state.lang === 'vi' ? 'Chuẩn bị chạy migrations...' : 'Preparing to run migrations...'), 'info');
    setProgress(35, state.lang === 'vi' ? 'Đang chạy database migrations...' : 'Running database migrations...');

    try {
      const { ok, data } = await apiFetch('/api/setup/install', {
        method: 'POST',
        body: JSON.stringify(getInstallPayload()),
      });

      if (ok && data.success) {
        setProgress(70, state.lang === 'vi' ? 'Migrations hoàn tất, đang khởi tạo dữ liệu...' : 'Migrations done, seeding initial data...');
        log('[MIGRATION] ' + (state.lang === 'vi' ? 'Tất cả migrations đã được thực thi và ghi lịch sử vào pw_migrations' : 'All migrations executed and recorded in pw_migrations'), 'success');

        const result = data.migrations || {};
        if (result.created && result.created.length > 0) {
          result.created.forEach(m => log('[MIGRATION] ✓ ' + m, 'success'));
        }
        if (result.skipped && result.skipped.length > 0) {
          result.skipped.forEach(m => log('[MIGRATION] ↷ ' + m + ' (already run)', ''));
        }

        await sleep(400);
        log('[AUTH] ' + (state.lang === 'vi' ? 'Tài khoản quản trị đã được tạo' : 'Admin account created'), 'success');
        setProgress(85, state.lang === 'vi' ? 'Đang ghi trạng thái installed...' : 'Writing installed state...');

        await sleep(300);
        log('[OPTIONS] presto_installed = 1 → pw_options', 'success');
        setProgress(100, state.lang === 'vi' ? 'Cài đặt hoàn tất!' : 'Installation complete!');

        await sleep(300);
        log('[SUCCESS] ' + (state.lang === 'vi' ? 'PrestoWorld đã được cài đặt thành công!' : 'PrestoWorld has been installed successfully!'), 'success');

        state.installing = false;
        state.installed = true;
        state.migrationResult = result;

        await sleep(1000);
        goToStep(6);
      } else {
        const errMsg = data.error || 'Unknown error';
        log('[ERROR] ' + errMsg, 'error');
        setProgress(0, state.lang === 'vi' ? 'Cài đặt thất bại' : 'Installation failed');
        state.installing = false;
        toast(errMsg, true);
        render(); // re-enable button
      }
    } catch (err) {
      log('[ERROR] ' + err.message, 'error');
      setProgress(0, state.lang === 'vi' ? 'Lỗi kết nối' : 'Connection error');
      state.installing = false;
      toast(err.message, true);
      render();
    }
  }

  function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  // ─── Check if already installed ──────────────────────────────────────────
  async function checkAlreadyInstalled() {
    try {
      const { ok, data } = await apiFetch('/api/setup/check-installed');
      if (ok && data.installed) {
        // Already installed — redirect to dashboard
        window.location.href = '/dashboard';
        return true;
      }
    } catch (e) {
      // Ignore, just show installer
    }
    return false;
  }

  // ─── Init ─────────────────────────────────────────────────────────────────
  async function init() {
    const alreadyInstalled = await checkAlreadyInstalled();
    if (alreadyInstalled) return;

    render();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expose for debugging
  window._pwInstaller = { state, render, goToStep };

})();
