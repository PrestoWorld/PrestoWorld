// Installation Wizard for PrestoWorld
// Stepper-based installation with AJAX backend

const state = {
    currentStep: 1,
    maxStepReached: 1,
    language: 'vi', // default
    env: {}, // environment info from backend
    db: {
        connection: 'pgsql',
        host: '127.0.0.1',
        port: '5432',
        name: 'prestoworld',
        username: 'prestoworld',
        password: 'prestoworld',
        prefix: 'pw_',
        tested: false
    },
    site: {
        title: '',
        tagline: '',
        adminEmail: '',
        adminUsername: '',
        adminPassword: ''
    },
    theme: '',
    themes: [], // list of available themes
    modules: [], // list of available modules
    selectedModules: [],
    installing: false,
    installProgress: 0,
    installLogs: [],
    installed: false
};

// DOM elements
const app = document.getElementById('app');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadStateFromUrl(); // optional: allow restoring state via URL params
    render();
    checkIfInstalled();
});

async function checkIfInstalled() {
    try {
        const response = await fetch('/api/setup/check-installed', {
            method: 'GET',
            credentials: 'same-origin'
        });
        if (response.ok) {
            const data = await response.json();
            if (data.installed) {
                showAlreadyInstalled();
                return;
            }
        }
    } catch (e) {
        // If we can't check, continue anyway
    }
    render();
}

function showAlreadyInstalled() {
    app.innerHTML = `
        <div class="header">
            <h1>PrestoWorld đã được cài đặt</h1>
            <p>Hệ thống PrestoWorld đã được cài đặt và sẵn sàng sử dụng.</p>
        </div>
        <div class="content">
            <p>Bạn có thể <a href="/login">đăng nhập vào quản trị</a> hoặc <a href="/">về trang chủ</a>.</p>
        </div>
    `;
}

function render() {
    // Render header
    let headerHTML = `
        <div class="header">
            <h1>PrestoWorld Installation Wizard</h1>
            <p>Cài đặt PrestoWorld một cách dễ dàng và nhanh chóng</p>
        </div>
    `;

    // Render steps
    const steps = [
        { id: 1, title: 'Ngôn ngữ', subtitle: 'Chọn ngôn ngữ cài đặt' },
        { id: 2, title: 'Môi trường', subtitle: 'Kiểm tra hệ thống' },
        { id: 3, title: 'Cơ sở dữ liệu', subtitle: 'Kết nối MySQL/PostgreSQL' },
        { id: 4, title: 'Thông tin trang', subtitle: 'Tài khoản quản trị viên' },
        { id: 5, title: 'Giao diện & Module', subtitle: 'Chọn giao diện và mở rộng' },
        { id: 6, title: 'Cài đặt', subtitle: 'Tiến hành cài đặt' },
        { id: 7, title: 'Hoàn tất', subtitle: 'Sẵn sàng sử dụng' }
    ];

    let stepsHTML = '<div class="steps">';
    steps.forEach(step => {
        const isActive = state.currentStep === step.id;
        const isCompleted = state.currentStep > step.id;
        let classes = 'step';
        if (isActive) classes += ' active';
        if (isCompleted) classes += ' completed';
        stepsHTML += `
            <div class="${classes}" data-step="${step.id}">
                <div class="step-number">${step.id}</div>
                <div>${step.title}</div>
                <div class="step-subtitle">${step.subtitle}</div>
            </div>
        `;
    });
    stepsHTML += '</div>';

    // Render content based on current step
    let contentHTML = '<div class="content">';
    switch (state.currentStep) {
        case 1:
            contentHTML += renderLanguageStep();
            break;
        case 2:
            contentHTML += renderEnvStep();
            break;
        case 3:
            contentHTML += renderDbStep();
            break;
        case 4:
            contentHTML += renderSiteStep();
            break;
        case 5:
            contentHTML += renderThemeModuleStep();
            break;
        case 6:
            contentHTML += renderInstallStep();
            break;
        case 7:
            contentHTML += renderFinishedStep();
            break;
        default:
            contentHTML += '<p>Invalid step</p>';
    }
    contentHTML += '</div>';

    // Render footer (navigation)
    let footerHTML = '<div class="button-group">';
    if (state.currentStep > 1) {
        footerHTML += `<button class="btn btn-secondary" id="btn-back">Quay lại</button>`;
    }
    if (state.currentStep < 7 && !state.installing) {
        const nextText = state.currentStep === 6 ? 'Bắt đầu Cài đặt' : 'Tiếp tục';
        footerHTML += `<button class="btn btn-primary" id="btn-next">${nextText}</button>`;
    }
    footerHTML += '</div>';

    app.innerHTML = headerHTML + stepsHTML + contentHTML + footerHTML;

    // Attach event listeners after render
    attachEventListeners();
}

function renderLanguageStep() {
    return `
        <div class="step-title">Chọn ngôn ngữ cài đặt</div>
        <p>Vui lòng chọn ngôn ngữ mà bạn muốn sử dụng trong quá trình cài đặt và giao diện quản trị.</p>
        <div class="form-group">
            <label for="language-select">Ngôn ngữ:</label>
            <select id="language-select">
                <option value="vi" ${state.language === 'vi' ? 'selected' : ''}>Tiếng Việt (Vietnamese)</option>
                <option value="en" ${state.language === 'en' ? 'selected' : ''}>English (United States)</option>
            </select>
        </div>
        <div class="alert-info">
            <strong>Lưu ý:</strong> Ngôn ngữ chỉ ảnh hưởng đến giao diện cài đặt và một số thông báo trong hệ thống. Nội dung website vẫn có thể được viết bằng bất kỳ ngôn ngữ nào.
        </div>
    `;
}

async function renderEnvStep() {
    // Fetch environment info if not already loaded
    if (Object.keys(state.env).length === 0) {
        try {
            const response = await fetch('/api/setup/env', {
                method: 'GET',
                credentials: 'same-origin'
            });
            if (response.ok) {
                state.env = await response.json();
            } else {
                state.env = { error: 'Không thể tải thông tin môi trường' };
            }
        } catch (e) {
            state.env = { error: 'Không thể kết nối tới máy chủ' };
        }
    }

    let envHTML = `
        <div class="step-title">Kiểm tra Môi trường Máy chủ</div>
        <p>Hệ thống sẽ tự động kiểm tra các yêu cầu tối thiểu để chạy PrestoWorld.</p>
    `;

    if (state.env.error) {
        envHTML += `<div class="message error">${state.env.error}</div>`;
    } else {
        envHTML += `
            <div class="alert-success">
                <strong>Máy chủ đáp ứng các yêu cầu tối thiểu:</strong> PHP ${state.env.php_version || 'unknown'}, ${state.env.db_driver || 'Database'} extension available.
            </div>
            <div class="form-group">
                <label>Chi tiết môi trường:</label>
                <pre class="env-details">${JSON.stringify(state.env, null, 2)}</pre>
            </div>
        `;
    }

    return envHTML;
}

function renderDbStep() {
    return `
        <div class="step-title">Cấu hình Cơ sở dữ liệu</div>
        <p>Nhập thông tin kết nối đến cơ sở dữ liệu MySQL, MariaDB hoặc PostgreSQL.</p>
        
        <div class="form-group">
            <label for="db-connection">Loại kết nối:</label>
            <select id="db-connection">
                <option value="pgsql" ${state.db.connection === 'pgsql' ? 'selected' : ''}>PostgreSQL</option>
                <option value="mysql" ${state.db.connection === 'mysql' ? 'selected' : ''}>MySQL / MariaDB</option>
                <option value="sqlite" ${state.db.connection === 'sqlite' ? 'selected' : ''}>SQLite (development only)</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="db-host">Địa chỉ máy chủ (Host):</label>
            <input type="text" id="db-host" value="${state.db.host}" placeholder="localhost">
            <div class="hint">Ví dụ: localhost, 127.0.0.1, hoặc tên miền</div>
        </div>
        
        <div class="form-group">
            <label for="db-port">Cổng (Port):</label>
            <input type="number" id="db-port" value="${state.db.port}" placeholder="${state.db.connection === 'pgsql' ? '5432' : '3306'}">
            <div class="hint">Cổng mặc định PostgreSQL: 5432, MySQL: 3306</div>
        </div>
        
        <div class="form-group">
            <label for="db-name">Tên cơ sở dữ liệu (Database Name):</label>
            <input type="text" id="db-name" value="${state.db.name}" placeholder="prestoworld">
            <div class="hint">Tên cơ sở dữ liệu đã được tạo trên máy chủ của bạn</div>
        </div>
        
        <div class="form-group">
            <label for="db-username">Tên người dùng (Username):</label>
            <input type="text" id="db-username" value="${state.db.username}" placeholder="prestoworld">
            <div class="hint">Tài khoản có quyền đầy đủ trên cơ sở dữ liệu này</div>
        </div>
        
        <div class="form-group">
            <label for="db-password">Mật khẩu (Password):</label>
            <div class="input-wrapper">
                <input type="password" id="db-password" value="${state.db.password}" placeholder="••••••••">
                <button type="button" class="btn-icon" id="toggle-db-password">
                    👁️
                </button>
            </div>
            <div class="hint">Mật khẩu để kết nối đến cơ sở dữ liệu</div>
        </div>
        
        <div class="form-group">
            <label for="db-prefix">Tiền tố bảng (Table Prefix) [optional]:</label>
            <input type="text" id="db-prefix" value="${state.db.prefix}" placeholder="pw_">
            <div class="hint">Tiền tố để tránh xung đột nếu chia sẻ cơ sở dữ liệu với các ứng dụng khác</div>
        </div>
        
        <div class="form-group">
            <button class="btn btn-secondary" id="btn-test-db">Kiểm tra Kết nối</button>
            <div id="db-test-result" class="mt-10"></div>
        </div>
    `;
}

function renderSiteStep() {
    return `
        <div class="step-title">Thông Tin Website và Quản Trị Viên</div>
        <p>Thiết lập tiêu đề website và tạo tài khoản quản trị viên đầu tiên.</p>
        
        <div class="form-group">
            <label for="site-title">Tiêu đề Website:</label>
            <input type="text" id="site-title" value="${state.site.title}" placeholder="Ví dụ: Công Ty TNHH Sáng Tạo">
        </div>
        
        <div class="form-group">
            <label for="site-tagline">Slogan Website (optional):</label>
            <input type="text" id="site-tagline" value="${state.site.tagline}" placeholder="Khẩu hiệu ngắn của trang...">
        </div>
        
        <div class="form-group">
            <label for="admin-email">Email Quản Trị Viên:</label>
            <input type="email" id="admin-email" value="${state.site.adminEmail}" placeholder="admin@example.com">
        </div>
        
        <div class="form-group">
            <label for="admin-username">Tên Đăng Nhập Quản Trị Viên:</label>
            <input type="text" id="admin-username" value="${state.site.adminUsername}" placeholder="admin">
            <div class="hint">Tránh dùng tên "admin" đơn giản trên môi trường production để tăng bảo mật</div>
        </div>
        
        <div class="form-group">
            <label for="admin-password">Mật khẩu Quản Trị Viên:</label>
            <div class="input-wrapper">
                <input type="password" id="admin-password" value="${state.site.adminPassword}" placeholder="••••••••">
                <button type="button" class="btn-icon" id="toggle-admin-password">
                    👁️
                </button>
            </div>
            <div class="hint">Mật khẩu mạnh bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt</div>
            <div class="pw-meter">
                <div class="pw-meter-fill" id="pw-meter-fill"></div>
            </div>
            <div class="pw-feedback" id="pw-feedback">
                <span>Độ mạnh: <strong id="pw-strength-text">Nhập mật khẩu để kiểm tra</strong></span>
                <span>Entropy: <strong id="pw-entropy">-</strong></span>
            </div>
        </div>
    `;
}

async function renderThemeModuleStep() {
    // Fetch themes and modules if not already loaded
    if (state.themes.length === 0) {
        try {
            const response = await fetch('/api/setup/themes', {
                method: 'GET',
                credentials: 'same-origin'
            });
            if (response.ok) {
                state.themes = await response.json();
            } else {
                state.themes = [];
            }
        } catch (e) {
            state.themes = [];
            console.error('Failed to load themes', e);
        }
    }

    if (state.modules.length === 0) {
        try {
            const response = await fetch('/api/setup/modules', {
                method: 'GET',
                credentials: 'same-origin'
            });
            if (response.ok) {
                state.modules = await response.json();
            } else {
                state.modules = [];
            }
        } catch (e) {
            state.modules = [];
            console.error('Failed to load modules', e);
        }
    }

    let themeHTML = '';
    if (state.themes.length > 0) {
        themeHTML = `
            <div class="step-title">Chọn Giao Diện (Theme)</div>
            <p>Chọn giao diện mặc định cho website của bạn.</p>
            <div class="theme-grid">
                ${state.themes.map(theme => `
                    <div class="theme-card ${state.theme === theme.id ? 'selected' : ''}" data-theme-id="${theme.id}">
                        ${theme.screenshot ? `<img src="${theme.screenshot}" alt="${theme.name}">` : '<div class="theme-placeholder">[No Preview]</div>'}
                        <h3>${theme.name}</h3>
                        <p>${theme.description || ''}</p>
                    </div>
                `).join('')}
            </div>
        `;
    } else {
        themeHTML = `<p>Không tìm thấy giao diện nào. Vui lòng kiểm tra thư mục content/themes.</p>`;
    }

    let moduleHTML = '';
    if (state.modules.length > 0) {
        moduleHTML = `
            <div class="step-title" style="margin-top: 30px;">Chọn Mở Rộng (Modules)</div>
            <p>Chọn các module bạn muốn kích hoạt podczas cài đặt. Một số module là bắt buộc và sẽ được tự động chọn.</p>
            <div class="module-grid">
                ${state.modules.map(module => `
                    <div class="module-card">
                        <div class="module-card-header">
                            <h4>${module.name}</h4>
                            <div class="toggle ${state.selectedModules.includes(module.id) ? 'active' : ''}" data-module-id="${module.id}"></div>
                        </div>
                        <p>${module.description || ''}</p>
                    </div>
                `).join('')}
            </div>
        `;
    } else {
        moduleHTML = `<p style="margin-top: 30px;">Không tìm thấy module nào. Vui lòng kiểm tra thư mục modules.</p>`;
    }

    return themeHTML + moduleHTML;
}

function renderInstallStep() {
    return `
        <div class="step-title">Cài Đặt PrestoWorld</div>
        <p>Hệ thống sẽ tự động cấu hình cơ sở dữ liệu, tạo bảng và thiết lập tài khoản quản trị viên.</p>
        
        ${state.installing ? `
            <div class="progress-container">
                <div class="progress-header">
                    <span id="install-step-text">Đang chuẩn bị...</span>
                    <span id="install-pct">0%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" id="install-bar" style="width: 0%;"></div>
                </div>
            </div>
            
            <div class="terminal-box">
                <div class="terminal-header">
                    <div class="terminal-dots">
                        <span class="terminal-dot red"></span>
                        <span class="terminal-dot yellow"></span>
                        <span class="terminal-dot green"></span>
                    </div>
                    <div class="terminal-title">Quá Trình Cài Đặt</div>
                </div>
                <div class="terminal-body" id="terminal-body">
                    <div class="term-line info">[INIT] Bắt đầu quá trình cài đặt PrestoWorld...</div>
                </div>
            </div>
        ` : `
            <button class="btn btn-primary" id="btn-start-install">Bắt Đầu Cài Đặt</button>
        `}
    `;
}

function renderFinishedStep() {
    return `
        <div class="step-title">Cài Đặt Thành Công!</div>
        <p>PrestoWorld đã được cài đặt thành công và sẵn sàng sử dụng.</p>
        
        <div class="success-box">
            <div class="success-icon">✓</div>
            <h2>Chúc mừng!</h2>
            <p>Website của bạn đã sẵn sàng. Bạn có thể bắt đầu sử dụng PrestoWorld ngay bây giờ.</p>
        </div>
        
        <div class="credentials-card">
            <div class="cred-row">
                <span class="cred-label">Tiêu đề Website:</span>
                <span class="cred-val">${state.site.title}</span>
            </div>
            <div class="cred-row">
                <span class="cred-label">URL Trang Chủ:</span>
                <span class="cred-val"><a href="/" target="_blank">/</a></span>
            </div>
            <div class="cred-row">
                <span class="cred-label">URL Đăng Nhập Quản Trị:</span>
                <span class="cred-val"><a href="/login" target="_blank">/login</a></span>
            </div>
            <div class="cred-row">
                <span class="cred-label">Tên Đăng Nhập:</span>
                <span class="cred-val">${state.site.adminUsername}</span>
            </div>
            <div class="cred-row">
                <span class="cred-label">Email Quản Trị:</span>
                <span class="cred-val">${state.site.adminEmail}</span>
            </div>
            <div class="cred-row">
                <span class="cred-label">Giao Disẹn Mặc Định:</span>
                <span class="cred-val">${state.themes.find(t => t.id === state.theme)?.name || state.theme}</span>
            </div>
            <div class="cred-row">
                <span class="cred-label">Modules Kích Hoạt:</span>
                <span class="cred-val">${state.selectedModules.length} modules</span>
            </div>
        </div>
        
        <div class="button-group" style="justify-content: center;">
            <button class="btn btn-secondary" id="btn-to-dashboard">Vào Quản Trị</button>
            <button class="btn btn-primary" id="btn-to-site">Trang Chủ Website</button>
        </div>
    `;
}

function attachEventListeners() {
    // Language selection
    const languageSelect = document.getElementById('language-select');
    if (languageSelect) {
        languageSelect.addEventListener('change', (e) => {
            state.language = e.target.value;
            // No need to re-render immediately; will happen on navigation
        });
    }

    // DB connection type change (adjust port placeholder)
    const dbConnectionSelect = document.getElementById('db-connection');
    if (dbConnectionSelect) {
        dbConnectionSelect.addEventListener('change', (e) => {
            state.db.connection = e.target.value;
            // Update port placeholder
            const portInput = document.getElementById('db-port');
            if (portInput) {
                portInput.placeholder = state.db.connection === 'pgsql' ? '5432' : '3306';
            }
        });
    }

    // Toggle password visibility
    const toggleDbPassword = document.getElementById('toggle-db-password');
    if (toggleDbPassword) {
        toggleDbPassword.addEventListener('click', () => {
            const passwordInput = document.getElementById('db-password');
            if (passwordInput) {
                passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
            }
        });
    }

    const toggleAdminPassword = document.getElementById('toggle-admin-password');
    if (toggleAdminPassword) {
        toggleAdminPassword.addEventListener('click', () => {
            const passwordInput = document.getElementById('admin-password');
            if (passwordInput) {
                passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
            }
        });
    }

    // Test DB connection
    const btnTestDb = document.getElementById('btn-test-db');
    if (btnTestDb) {
        btnTestDb.addEventListener('click', async () => {
            btnTestDb.disabled = true;
            const resultDiv = document.getElementById('db-test-result');
            resultDiv.innerHTML = '<div class="message info"><span class="spinner"></span> Đang kiểm tra kết nối...</div>';
            
            const dbData = {
                connection: document.getElementById('db-connection').value,
                host: document.getElementById('db-host').value,
                port: document.getElementById('db-port').value,
                name: document.getElementById('db-name').value,
                username: document.getElementById('db-username').value,
                password: document.getElementById('db-password').value
            };

            try {
                const response = await fetch('/api/setup/test-db', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dbData),
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        resultDiv.innerHTML = `<div class="message success">✓ Kết nối thành công!</div>`;
                        state.db.tested = true;
                        // Update state with tested values
                        state.db.connection = dbData.connection;
                        state.db.host = dbData.host;
                        state.db.port = dbData.port;
                        state.db.name = dbData.name;
                        state.db.username = dbData.username;
                        state.db.password = dbData.password;
                    } else {
                        resultDiv.innerHTML = `<div class="message error">✗ Kết nối thất bại: ${result.error || 'Lỗi không xác định'}</div>`;
                        state.db.tested = false;
                    }
                } else {
                    resultDiv.innerHTML = `<div class="message error">✗ Lỗi máy chủ: ${response.status}</div>`;
                    state.db.tested = false;
                }
            } catch (e) {
                resultDiv.innerHTML = `<div class="message error">✗ Lỗi kết nối: ${e.message}</div>`;
                state.db.tested = false;
            } finally {
                btnTestDb.disabled = false;
            }
        });
    }

    // Password strength meter
    const adminPasswordInput = document.getElementById('admin-password');
    const pwMeterFill = document.getElementById('pw-meter-fill');
    const pwStrengthText = document.getElementById('pw-strength-text');
    const pwEntropy = document.getElementById('pw-entropy');
    if (adminPasswordInput && pwMeterFill && pwStrengthText && pwEntropy) {
        adminPasswordInput.addEventListener('input', () => {
            const password = adminPasswordInput.value;
            const strength = calculatePasswordStrength(password);
            pwMeterFill.style.width = `${strength.percent}%`;
            pwMeterFill.className = `pw-meter-fill ${strength.level}`;
            pwStrengthText.textContent = strength.text;
            pwEntropy.textContent = strength.entropy;
        });
    }

    // Theme selection
    const themeCards = document.querySelectorAll('.theme-card');
    themeCards.forEach(card => {
        card.addEventListener('click', () => {
            const themeId = card.getAttribute('data-theme-id');
            // Deselect all
            document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('selected'));
            // Select clicked
            card.classList.add('selected');
            state.theme = themeId;
        });
    });

    // Module toggle
    const moduleToggles = document.querySelectorAll('.toggle');
    moduleToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const moduleId = toggle.getAttribute('data-module-id');
            if (state.selectedModules.includes(moduleId)) {
                state.selectedModules = state.selectedModules.filter(id => id !== moduleId);
                toggle.classList.remove('active');
            } else {
                state.selectedModules.push(moduleId);
                toggle.classList.add('active');
            }
        });
    });

    // Navigation buttons
    const btnBack = document.getElementById('btn-back');
    if (btnBack) {
        btnBack.addEventListener('click', () => {
            if (state.currentStep > 1) {
                state.currentStep--;
                render();
            }
        });
    }

    const btnNext = document.getElementById('btn-next');
    if (btnNext) {
        btnNext.addEventListener('click', () => {
            if (state.currentStep < 7) {
                // Validate before moving to next step
                if (state.currentStep === 3 && !state.db.tested) {
                    alert('Vui lòng kiểm tra kết nối cơ sở dữ liệu trước khi tiếp tục.');
                    return;
                }
                // Additional validation for site step (step 4) - ensure required fields filled
                if (state.currentStep === 4) {
                    const title = document.getElementById('site-title')?.value.trim();
                    const email = document.getElementById('admin-email')?.value.trim();
                    const username = document.getElementById('admin-username')?.value.trim();
                    const password = document.getElementById('admin-password')?.value.trim();
                    if (!title || !email || !username || !password) {
                        alert('Vui lòng điền đầy đủ thông tin bắt buộc: tiêu đề website, email, tên đăng nhập và mật khẩu.');
                        return;
                    }
                    // Update state from form
                    state.site.title = title;
                    state.site.tagline = document.getElementById('site-tagline')?.value.trim() || '';
                    state.site.adminEmail = email;
                    state.site.adminUsername = username;
                    state.site.adminPassword = password;
                }
                state.currentStep++;
                render();
            }
        });
    }

    // Start installation
    const btnStartInstall = document.getElementById('btn-start-install');
    if (btnStartInstall) {
        btnStartInstall.addEventListener('click', async () => {
            // Gather all data from forms (should already be in state, but ensure)
            state.db.connection = document.getElementById('db-connection').value;
            state.db.host = document.getElementById('db-host').value;
            state.db.port = document.getElementById('db-port').value;
            state.db.name = document.getElementById('db-name').value;
            state.db.username = document.getElementById('db-username').value;
            state.db.password = document.getElementById('db-password').value;
            state.db.prefix = document.getElementById('db-prefix').value || 'pw_';
            
            state.site.title = document.getElementById('site-title').value.trim();
            state.site.tagline = document.getElementById('site-tagline').value.trim() || '';
            state.site.adminEmail = document.getElementById('admin-email').value.trim();
            state.site.adminUsername = document.getElementById('admin-username').value.trim();
            state.site.adminPassword = document.getElementById('admin-password').value;

            // Start installation process
            state.installing = true;
            state.installProgress = 0;
            state.installLogs = [];
            render(); // Show installing UI

            try {
                // Prepare data to send
                const installData = {
                    db_connection: state.db.connection,
                    db_host: state.db.host,
                    db_port: parseInt(state.db.port),
                    db_name: state.db.name,
                    db_username: state.db.username,
                    db_password: state.db.password,
                    db_prefix: state.db.prefix,
                    site_title: state.site.title,
                    site_tagline: state.site.tagline,
                    admin_email: state.site.adminEmail,
                    admin_username: state.site.adminUsername,
                    admin_password: state.site.adminPassword,
                    theme: state.theme,
                    modules: state.selectedModules
                };

                // Call install API
                const response = await fetch('/api/setup/install', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(installData),
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        // Simulate progress updates (since we don't have real-time backend updates)
                        await simulateInstallProgress();
                        state.installed = true;
                        state.installing = false;
                        state.currentStep = 7;
                        render();
                    } else {
                        throw new Error(result.error || 'Installation failed');
                    }
                } else {
                    const errorData = await response.json();
                    throw new Error(errorData.error || `HTTP ${response.status}`);
                }
            } catch (error) {
                state.installing = false;
                state.installError = error.message;
                render(); // Show error state
            }
        });
    }

    // Buttons on finished step
    const btnToDashboard = document.getElementById('btn-to-dashboard');
    if (btnToDashboard) {
        btnToDashboard.addEventListener('click', () => {
            window.location.href = '/login';
        });
    }

    const btnToSite = document.getElementById('btn-to-site');
    if (btnToSite) {
        btnToSite.addEventListener('click', () => {
            window.location.href = '/';
        });
    }
}

// Simulate installation progress (since we don't have real-time updates from backend)
async function simulateInstallProgress() {
    const steps = [
        { percent: 10, text: 'Đang cấu hình kết nối cơ sở dữ liệu...', log: '[DB] Kết nối thành công' },
        { percent: 20, text: 'Đang tạo bảng hệ thống...', log: '[SCHEMA] Creating core tables' },
        { percent: 30, text: 'Đang đồng bộ schema các module...', log: '[MODULES] Syncing module schemas' },
        { percent: 40, text: 'Đang tạo tài khoản quản trị viên...', log: '[AUTH] Creating admin user' },
        { percent: 50, text: 'Đang cấu hình giao diện mặc định...', log: '[THEME] Activating theme' },
        { percent: 60, text: 'Đang kích hoạt modules đã chọn...', log: `[PLUGINS] Activating ${state.selectedModules.length} modules` },
        { percent: 80, text: 'Đang дoạn dọn dẹp và tối ưu...', log: '[CLEANUP] Finalizing installation' },
        { percent: 100, text: 'Cài đặt hoàn tất!', log: '[SUCCESS] Installation completed successfully' }
    ];

    const terminal = document.getElementById('terminal-body');
    const progressText = document.getElementById('install-step-text');
    const progressPct = document.getElementById('install-pct');
    const progressBar = document.getElementById('install-bar');

    for (const step of steps) {
        // Update UI
        state.installProgress = step.percent;
        if (progressBar) progressBar.style.width = `${step.percent}%`;
        if (progressPct) progressPct.textContent = `${step.percent}%`;
        if (progressText) progressText.textContent = step.text;

        // Add log entry
        if (terminal) {
            const logLine = document.createElement('div');
            logLine.className = 'term-line info';
            logLine.textContent = step.log;
            terminal.appendChild(logLine);
            terminal.scrollTop = terminal.scrollHeight;
        }

        // Wait a bit
        await new Promise(resolve => setTimeout(resolve, 800));
    }
}

// Password strength calculation
function calculatePasswordStrength(password) {
    if (password.length === 0) {
        return { percent: 0, level: '', text: 'Nhập mật khẩu để kiểm tra', entropy: '-' };
    }

    let score = 0;

    // Length
    if (password.length >= 8) score += 1;
    if (password.length >= 12) score += 1;
    if (password.length >= 16) score += 1;

    // Character variety
    if (/[a-z]/.test(password)) score += 1;
    if (/[A-Z]/.test(password)) score += 1;
    if (/[0-9]/.test(password)) score += 1;
    if (/[^A-Za-z0-9]/.test(password)) score += 1;

    // Convert to percentage (0-100)
    const percent = Math.min((score / 8) * 100, 100);

    let level = '';
    let text = '';
    if (percent < 20) {
        level = 'weak';
        text = 'Yếu';
    } else if (percent < 40) {
        level = 'weak';
        text = 'Vẫn yếu';
    } else if (percent < 60) {
        level = 'medium';
        text = 'Trung bình';
    } else if (percent < 80) {
        level = 'strong';
        text = 'Mạnh';
    } else {
        level = 'strong';
        text = 'Rất mạnh';
    }

    // Rough entropy estimation (simplified)
    const entropy = Math.floor(password.length * Math.log2(95)); // assuming 95 printable ASCII chars

    return { percent, level, text, entropy: entropy.toString() };
}

// Utility: parse query string for state restoration (optional)
function loadStateFromUrl() {
    // Could implement if needed, but skip for simplicity
}

// Polyfill for Object.fromEntries if needed (not required in modern browsers)
// Optional: add more utility functions as needed

// Export state for debugging (optional)
window.__installationState = state;
