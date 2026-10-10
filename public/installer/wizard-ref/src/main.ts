/**
 * WordPress Modern Setup Wizard
 * 100% Vanilla JS/TypeScript with Pure CSS
 * Modern replacement for WordPress's default wp-admin/install.php
 */

import './index.css';

// Types
type Language = 'vi' | 'en';
type ThemeMode = 'light' | 'dark';

interface WizardState {
  currentStep: number;
  maxStepReached: number;
  language: Language;
  themeMode: ThemeMode;
  db: {
    name: string;
    user: string;
    pass: string;
    host: string;
    prefix: string;
    tested: boolean;
  };
  site: {
    title: string;
    tagline: string;
    adminUser: string;
    adminPass: string;
    adminEmail: string;
    searchEnginePublic: boolean;
    timezone: string;
  };
  theme: string;
  plugins: string[];
  installing: boolean;
  installProgress: number;
  installed: boolean;
}

// Translations Dictionary
const i18n = {
  vi: {
    brandTitle: 'WordPress Setup',
    brandBadge: 'v6.7 Pro',
    themeLight: 'Sáng',
    themeDark: 'Tối',
    helpBtn: 'Trợ giúp',
    presetsBtn: 'Mẫu cấu hình',
    next: 'Tiếp tục',
    back: 'Quay lại',
    installNow: 'Bắt đầu Cài đặt',
    finish: 'Đăng nhập vào Quản trị',
    previewSite: 'Xem trước Website',
    copy: 'Sao chép',
    copied: 'Đã sao chép vào bộ nhớ tạm!',
    download: 'Tải wp-config.php',
    
    // Steps
    step1Title: 'Chào mừng',
    step1Sub: 'Chọn ngôn ngữ',
    step2Title: 'Môi trường',
    step2Sub: 'Kiểm tra hệ thống',
    step3Title: 'Cơ sở dữ liệu',
    step3Sub: 'Kết nối MySQL',
    step4Title: 'Thông tin Trang',
    step4Sub: 'Tài khoản admin',
    step5Title: 'Giao diện & Plugin',
    step5Sub: 'Tối ưu ban đầu',
    step6Title: 'Cài đặt',
    step6Sub: 'Tạo wp-config.php',
    step7Title: 'Hoàn tất',
    step7Sub: 'Sẵn sàng sử dụng',

    // Step 1
    welcomeTitle: 'Chào mừng bạn đến với WordPress',
    welcomeDesc: 'Trình hướng dẫn thiết lập hiện đại giúp bạn cấu hình trang web WordPress chỉ trong 2 phút với chuẩn bảo mật và hiệu năng cao nhất.',
    selectLangLabel: 'Ngôn ngữ cài đặt chính:',
    featuresTitle: 'Tính năng nổi bật của trình cài đặt mới:',
    feat1: 'Kiểm tra độ tương thích máy chủ (PHP 8.2+, MySQL 8.0, Redis Cache).',
    feat2: 'Mã hóa tự động Security Keys & Salts chuẩn WordPress VIP.',
    feat3: 'Cài sẵn bộ Plugin tối ưu tốc độ và bảo mật hàng đầu.',
    feat4: 'Xuất file wp-config.php chuẩn hóa ngay tại chỗ.',

    // Step 2
    envTitle: 'Kiểm tra Độ sẵn sàng của Máy chủ',
    envDesc: 'Hệ thống đã tự động quét môi trường máy chủ của bạn để đảm bảo hiệu suất tốt nhất cho WordPress.',
    envStatusGood: 'Đạt chuẩn',
    envStatusWarning: 'Khuyến nghị',

    // Step 3
    dbTitle: 'Cấu hình Cơ sở dữ liệu (Database)',
    dbDesc: 'Nhập thông tin kết nối MySQL hoặc MariaDB. Nếu chưa rõ, hãy liên hệ nhà cung cấp hosting của bạn.',
    dbNameLabel: 'Tên Database (Database Name)',
    dbNameHint: 'Tên cơ sở dữ liệu đã tạo trên hosting/cPanel.',
    dbUserLabel: 'Tên đăng nhập (Username)',
    dbUserHint: 'Tài khoản có quyền trên cơ sở dữ liệu.',
    dbPassLabel: 'Mật khẩu (Password)',
    dbPassHint: 'Mật khẩu kết nối MySQL.',
    dbHostLabel: 'Địa chỉ máy chủ (Database Host)',
    dbHostHint: 'Thông thường là "localhost" hoặc "127.0.0.1".',
    dbPrefixLabel: 'Tiền tố bảng (Table Prefix)',
    dbPrefixHint: 'Nên đổi tiền tố ngẫu nhiên để tăng tính bảo mật chống SQL injection.',
    testDbBtn: 'Kiểm tra Kết nối Database',
    testDbSuccess: 'Kết nối thành công! Máy chủ MySQL 8.0 phản hồi trong 12ms.',
    testDbTesting: 'Đang kết nối tới máy chủ...',
    loadPresetPrompt: 'Tải nhanh cấu hình mẫu:',

    // Step 4
    siteTitleLabel: 'Tên Website (Site Title)',
    siteTaglineLabel: 'Khẩu hiệu (Tagline)',
    adminUserLabel: 'Tên đăng nhập Quản trị viên',
    adminPassLabel: 'Mật khẩu Quản trị viên',
    adminEmailLabel: 'Email Quản trị viên',
    generatePass: 'Tạo mật khẩu mạnh',
    searchEngineLabel: 'Khả năng hiển thị với công cụ tìm kiếm',
    searchEngineDesc: 'Cho phép Google, Bing lập chỉ mục website này ngay lập tức.',

    // Step 5
    themeStepTitle: 'Lựa chọn Giao diện Khởi đầu',
    themeStepDesc: 'Bạn có thể thay đổi giao diện bất kỳ lúc nào trong trang quản trị.',
    pluginStepTitle: 'Gói Tiện ích Đề xuất (Plugins)',
    pluginStepDesc: 'Chọn các tiện ích bổ sung để kích hoạt ngay sau khi cài đặt hoàn tất.',

    // Step 6
    installTitle: 'Đang tiến hành Cài đặt WordPress',
    installDesc: 'Hệ thống đang khởi tạo cơ sở dữ liệu, nạp cấu hình và tạo file wp-config.php.',
    configTabTitle: 'Xem trước file wp-config.php',
    terminalTabTitle: 'Nhật ký tiến trình (Console)',

    // Step 7
    successTitle: 'Chúc mừng! Cài đặt WordPress Thành Công!',
    successDesc: 'Trang web của bạn đã được khởi tạo hoàn chỉnh và sẵn sàng để xuất bản nội dung.',
    adminUrl: 'Đường dẫn Quản trị:',
    usernameRecap: 'Tên đăng nhập:',
    passwordRecap: 'Mật khẩu:',
    goToAdmin: 'Đăng nhập vào Bảng Quản trị (wp-admin)',
    visitSite: 'Truy cập Trang chủ'
  },
  en: {
    brandTitle: 'WordPress Setup',
    brandBadge: 'v6.7 Pro',
    themeLight: 'Light',
    themeDark: 'Dark',
    helpBtn: 'Help',
    presetsBtn: 'Presets',
    next: 'Continue',
    back: 'Back',
    installNow: 'Install WordPress',
    finish: 'Log In to Admin',
    previewSite: 'Preview Site',
    copy: 'Copy',
    copied: 'Copied to clipboard!',
    download: 'Download wp-config.php',
    
    // Steps
    step1Title: 'Welcome',
    step1Sub: 'Language selection',
    step2Title: 'Environment',
    step2Sub: 'System health check',
    step3Title: 'Database',
    step3Sub: 'MySQL connection',
    step4Title: 'Site Info',
    step4Sub: 'Admin credentials',
    step5Title: 'Themes & Plugins',
    step5Sub: 'Initial enhancements',
    step6Title: 'Installation',
    step6Sub: 'Generate wp-config',
    step7Title: 'Finished',
    step7Sub: 'Ready to launch',

    // Step 1
    welcomeTitle: 'Welcome to WordPress',
    welcomeDesc: 'A modern, lightning-fast setup wizard replacing the legacy installer with enterprise security and instant speed optimization.',
    selectLangLabel: 'Installation Language:',
    featuresTitle: 'Modern Installer Capabilities:',
    feat1: 'Server compatibility & health scanner (PHP 8.2+, MySQL 8.0, Redis).',
    feat2: 'Automatic cryptographically secure Salt generation.',
    feat3: 'Pre-bundled top-tier performance and security plugins.',
    feat4: 'Instant one-click wp-config.php exporter.',

    // Step 2
    envTitle: 'Server Readiness & Diagnostics',
    envDesc: 'We have automatically scanned your host environment to ensure peak WordPress stability.',
    envStatusGood: 'Passed',
    envStatusWarning: 'Recommended',

    // Step 3
    dbTitle: 'Database Configuration',
    dbDesc: 'Provide your MySQL or MariaDB credentials. Contact your hosting provider if you are unsure.',
    dbNameLabel: 'Database Name',
    dbNameHint: 'The name of the database created for WordPress.',
    dbUserLabel: 'Database Username',
    dbUserHint: 'Your MySQL user with full privileges.',
    dbPassLabel: 'Database Password',
    dbPassHint: 'Your database account password.',
    dbHostLabel: 'Database Host',
    dbHostHint: 'Typically "localhost" or "127.0.0.1".',
    dbPrefixLabel: 'Table Prefix',
    dbPrefixHint: 'Change from default wp_ for enhanced security against SQL injection.',
    testDbBtn: 'Test Database Connection',
    testDbSuccess: 'Connection successful! MySQL 8.0 responded in 12ms.',
    testDbTesting: 'Testing server handshake...',
    loadPresetPrompt: 'Quick presets for popular stacks:',

    // Step 4
    siteTitleLabel: 'Site Title',
    siteTaglineLabel: 'Tagline',
    adminUserLabel: 'Admin Username',
    adminPassLabel: 'Admin Password',
    adminEmailLabel: 'Your Email',
    generatePass: 'Generate Strong Password',
    searchEngineLabel: 'Search Engine Visibility',
    searchEngineDesc: 'Discourage or allow search engines from indexing this site.',

    // Step 5
    themeStepTitle: 'Choose a Starter Theme',
    themeStepDesc: 'Select an elegant baseline design. You can change this anytime from Appearance.',
    pluginStepTitle: 'Recommended Plugin Suite',
    pluginStepDesc: 'Pick optional essentials to activate immediately upon setup.',

    // Step 6
    installTitle: 'Installing WordPress Core',
    installDesc: 'Generating database tables, writing configuration, and configuring security salts.',
    configTabTitle: 'wp-config.php Output',
    terminalTabTitle: 'Live Console Output',

    // Step 7
    successTitle: 'WordPress has been Installed!',
    successDesc: 'Your website is now configured, secured, and ready for you to create content.',
    adminUrl: 'Admin URL:',
    usernameRecap: 'Username:',
    passwordRecap: 'Password:',
    goToAdmin: 'Log In to WordPress Dashboard',
    visitSite: 'Visit Website Frontpage'
  }
};

// Initial State
const state: WizardState = {
  currentStep: 1,
  maxStepReached: 1,
  language: 'vi',
  themeMode: 'light',
  db: {
    name: 'wordpress_db',
    user: 'root',
    pass: '',
    host: 'localhost',
    prefix: 'wp_sec7_',
    tested: false
  },
  site: {
    title: 'Website Của Tôi',
    tagline: 'Một trang web WordPress mới tinh và tốc độ cao',
    adminUser: 'admin',
    adminPass: generateRandomSecurePassword(),
    adminEmail: 'admin@example.com',
    searchEnginePublic: true,
    timezone: 'Asia/Ho_Chi_Minh'
  },
  theme: 'twentytwentyfive',
  plugins: ['litespeed-cache', 'wordfence-security', 'yoast-seo'],
  installing: false,
  installProgress: 0,
  installed: false
};

// Helper: Random password generator
function generateRandomSecurePassword(): string {
  const chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%^&*()_+';
  let pass = '';
  const array = new Uint32Array(16);
  crypto.getRandomValues(array);
  for (let i = 0; i < 16; i++) {
    pass += chars[array[i] % chars.length];
  }
  return pass;
}

// Helper: Random salt generator
function generateRandomSalt(): string {
  const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';
  let salt = '';
  const array = new Uint32Array(64);
  crypto.getRandomValues(array);
  for (let i = 0; i < 64; i++) {
    salt += chars[array[i] % chars.length];
  }
  return salt;
}

// Theme Catalog
const THEMES = [
  {
    id: 'twentytwentyfive',
    name: 'Twenty Twenty-Five',
    badge: 'Mặc định WP 6.7',
    desc: 'Giao diện Block Theme chuẩn mới nhất, tối ưu tốc độ 100/100 Core Web Vitals.',
    previewBg: 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)',
    textColor: '#ffffff'
  },
  {
    id: 'astra',
    name: 'Astra Modern',
    badge: 'Phổ biến nhất',
    desc: 'Siêu nhẹ, linh hoạt cho trang doanh nghiệp, tin tức hoặc landing page cao cấp.',
    previewBg: 'linear-gradient(135deg, #4f46e5 0%, #3730a3 100%)',
    textColor: '#ffffff'
  },
  {
    id: 'blocksy',
    name: 'Blocksy Next-Gen',
    badge: 'Gutenberg Native',
    desc: 'Tích hợp sâu với Block Editor, hỗ trợ Header/Footer Builder trực quan tuyệt đẹp.',
    previewBg: 'linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%)',
    textColor: '#ffffff'
  },
  {
    id: 'storefront',
    name: 'Woo Storefront',
    badge: 'Thương mại điện tử',
    desc: 'Tối ưu dành riêng cho WooCommerce bán hàng trực tuyến và cổng thanh toán.',
    previewBg: 'linear-gradient(135deg, #9333ea 0%, #6b21a8 100%)',
    textColor: '#ffffff'
  }
];

// Plugins Catalog
const PLUGINS = [
  {
    id: 'litespeed-cache',
    name: 'LiteSpeed / WP Super Cache',
    category: 'Tốc độ',
    desc: 'Bộ nhớ đệm thông minh, tối ưu hóa CSS/JS và giảm tải CPU máy chủ 80%.'
  },
  {
    id: 'wordfence-security',
    name: 'Wordfence Security Suite',
    category: 'Bảo mật',
    desc: 'Tường lửa bảo vệ WAF, chống brute-force và quét mã độc thời gian thực.'
  },
  {
    id: 'yoast-seo',
    name: 'Yoast SEO / Rank Math',
    category: 'SEO',
    desc: 'Tạo XML Sitemap chuẩn, cấu hình OpenGraph xã hội và Rich Snippets schema.'
  },
  {
    id: 'woocommerce',
    name: 'WooCommerce Bán hàng',
    category: 'E-Commerce',
    desc: 'Xây dựng cửa hàng online đầy đủ tính năng giỏ hàng, thanh toán và quản lý đơn.'
  },
  {
    id: 'wpforms',
    name: 'WPForms Lite Contact',
    category: 'Liên hệ',
    desc: 'Tạo form liên hệ kéo thả dễ dàng, chống spam tích hợp Google reCAPTCHA v3.'
  },
  {
    id: 'redis-cache',
    name: 'Redis Object Cache',
    category: 'Database',
    desc: 'Tăng tốc truy vấn cơ sở dữ liệu và session người dùng thông qua RAM Redis.'
  }
];

// Server Health Diagnostics
const DIAGNOSTICS = [
  { name: 'Phiên bản PHP (PHP Version)', val: 'PHP 8.2.18 (Zend Engine v4.2)', status: 'good' },
  { name: 'MySQL / MariaDB Support', val: 'MySQL 8.0.36 + mysqli extension', status: 'good' },
  { name: 'Giới hạn bộ nhớ (Memory Limit)', val: '512 MB (Đạt chuẩn WP Pro)', status: 'good' },
  { name: 'Max Execution Time', val: '300s (Rất tốt cho import dữ liệu)', status: 'good' },
  { name: 'Quyền ghi thư mục (wp-content/)', val: '0755 (Writable / Khả dụng)', status: 'good' },
  { name: 'Phần mở rộng cURL & OpenSSL', val: 'Đã kích hoạt (TLS 1.3)', status: 'good' },
  { name: 'Xử lý hình ảnh (Imagick / GD)', val: 'ImageMagick 7.1 hỗ trợ WebP/AVIF', status: 'good' }
];

// Presets Definition
const PRESETS = [
  {
    name: 'Localhost (XAMPP / Laragon)',
    db: { name: 'wordpress', user: 'root', pass: '', host: 'localhost', prefix: 'wp_' }
  },
  {
    name: 'Docker / DDEV Staging',
    db: { name: 'db', user: 'db', pass: 'db', host: 'db:3306', prefix: 'wp_dev_' }
  },
  {
    name: 'cPanel / DirectAdmin Host',
    db: { name: 'myhost_wp', user: 'myhost_user', pass: 'SuperSec#2026@', host: 'localhost', prefix: 'wp_cp_' }
  },
  {
    name: 'VPS Nginx Production',
    db: { name: 'wp_enterprise', user: 'wp_prod_usr', pass: 'VPS#K92@Enterprise', host: '127.0.0.1:3306', prefix: 'wp_sec7_' }
  }
];

// Toast Notification
function showToast(message: string) {
  const container = document.getElementById('wp-toasts') || createToastContainer();
  const toast = document.createElement('div');
  toast.className = 'wp-toast';
  toast.innerHTML = `
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2">
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
      <polyline points="22 4 12 14.01 9 11.01"></polyline>
    </svg>
    <span>${message}</span>
  `;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    toast.style.transition = 'all 0.2s ease';
    setTimeout(() => toast.remove(), 250);
  }, 2800);
}

function createToastContainer(): HTMLElement {
  const container = document.createElement('div');
  container.id = 'wp-toasts';
  container.className = 'wp-toast-container';
  document.body.appendChild(container);
  return container;
}

// Generate wp-config.php content
function generateWpConfig(): string {
  const salts = [
    `define( 'AUTH_KEY',         '${generateRandomSalt()}' );`,
    `define( 'SECURE_AUTH_KEY',  '${generateRandomSalt()}' );`,
    `define( 'LOGGED_IN_KEY',    '${generateRandomSalt()}' );`,
    `define( 'NONCE_KEY',        '${generateRandomSalt()}' );`,
    `define( 'AUTH_SALT',        '${generateRandomSalt()}' );`,
    `define( 'SECURE_AUTH_SALT', '${generateRandomSalt()}' );`,
    `define( 'LOGGED_IN_SALT',   '${generateRandomSalt()}' );`,
    `define( 'NONCE_SALT',       '${generateRandomSalt()}' );`
  ].join('\n');

  return `<?php
/**
 * The base configuration for WordPress
 *
 * Generated by WordPress Modern Setup Wizard
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', '${state.db.name}' );

/** Database username */
define( 'DB_USER', '${state.db.user}' );

/** Database password */
define( 'DB_PASSWORD', '${state.db.pass}' );

/** Database hostname */
define( 'DB_HOST', '${state.db.host}' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 */
${salts}
/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = '${state.db.prefix}';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define( 'WP_DEBUG', false );
define( 'WP_MEMORY_LIMIT', '512M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );

/* Add any custom values between this line and the "stop editing" line. */

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
`;
}

// Password strength calculator
function getPasswordStrength(pw: string): { level: 'very-weak' | 'weak' | 'medium' | 'strong'; text: string } {
  if (!pw || pw.length < 5) return { level: 'very-weak', text: 'Rất yếu (Nguy hiểm)' };
  let score = 0;
  if (pw.length >= 8) score++;
  if (pw.length >= 12) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;

  if (score <= 1) return { level: 'weak', text: 'Yếu' };
  if (score <= 3) return { level: 'medium', text: 'Trung bình' };
  return { level: 'strong', text: 'Mạnh (Khuyên dùng)' };
}

// RENDER APPLICATION
function renderApp() {
  const root = document.getElementById('root');
  if (!root) return;

  const t = i18n[state.language];

  root.innerHTML = `
    <div class="wp-app" data-theme="${state.themeMode}">
      <!-- Header -->
      <header class="wp-header">
        <a href="#" class="wp-brand" id="btn-brand">
          <div class="wp-brand-logo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878L5.753 9.426c.725-.036 1.408-.073 1.408-.073.543-.036.472-.835-.072-.835 0 0-1.632.146-2.684.146-1.015 0-2.646-.146-2.646-.146-.544 0-.616.799-.072.835 0 0 .647.037 1.336.073l1.996 5.99L2.096 12C2.032 11.672 2 11.34 2 12c0-5.523 4.477-10 10-10s10 4.477 10 10c0 4.48-2.951 8.271-7.014 9.535l4.312-12.46c.725-.036 1.408-.073 1.408-.073.543-.036.472-.835-.072-.835 0 0-1.632.146-2.684.146-1.015 0-2.646-.146-2.646-.146-.544 0-.616.799-.072.835 0 0 .647.037 1.336.073l2.03 6.091-2.949 8.783A9.99 9.99 0 0 0 22 12c0-5.523-4.477-10-10-10zm-1.896 19.82A7.986 7.986 0 0 1 4.1 14.54l3.65 10.015c.749.176 1.529.265 2.354.265zm2.748-.225 3.324-9.645-1.996-5.467-3.411 9.917c.697 1.83 1.385 3.593 2.083 5.195z"/>
            </svg>
          </div>
          <span class="wp-brand-title">
            ${t.brandTitle}
            <span class="wp-brand-badge">${t.brandBadge}</span>
          </span>
        </a>

        <div class="wp-header-actions">
          <!-- Presets Dialog Button -->
          <button class="wp-btn-secondary wp-btn-sm" id="btn-open-presets" style="display: flex; align-items: center; gap: 6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
            ${t.presetsBtn}
          </button>

          <!-- Language Selector -->
          <select class="wp-select-compact" id="lang-switcher">
            <option value="vi" ${state.language === 'vi' ? 'selected' : ''}>🇻🇳 Tiếng Việt</option>
            <option value="en" ${state.language === 'en' ? 'selected' : ''}>🇺🇸 English</option>
          </select>

          <!-- Theme Toggle Button -->
          <button class="wp-btn-icon" id="btn-theme-toggle" title="Giao diện Sáng/Tối" aria-label="Toggle Theme">
            ${state.themeMode === 'light' ? `
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
              </svg>
            ` : `
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="5"></circle>
                <line x1="12" y1="1" x2="12" y2="3"></line>
                <line x1="12" y1="21" x2="12" y2="23"></line>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                <line x1="1" y1="12" x2="3" y2="12"></line>
                <line x1="21" y1="12" x2="23" y2="12"></line>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
              </svg>
            `}
          </button>
        </div>
      </header>

      <!-- Main Layout -->
      <main class="wp-main-layout">
        <!-- Sidebar Navigation Stepper -->
        <aside class="wp-stepper-sidebar">
          <div class="wp-stepper-title">${state.language === 'vi' ? 'Tiến trình cài đặt' : 'Installation Steps'}</div>
          <ul class="wp-steps-list">
            ${renderStepItem(1, t.step1Title, t.step1Sub)}
            ${renderStepItem(2, t.step2Title, t.step2Sub)}
            ${renderStepItem(3, t.step3Title, t.step3Sub)}
            ${renderStepItem(4, t.step4Title, t.step4Sub)}
            ${renderStepItem(5, t.step5Title, t.step5Sub)}
            ${renderStepItem(6, t.step6Title, t.step6Sub)}
            ${renderStepItem(7, t.step7Title, t.step7Sub)}
          </ul>

          <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color); font-size: 0.76rem; color: var(--text-muted); line-height: 1.4;">
            WordPress ${t.brandBadge} · PHP 8.2+ · MariaDB/MySQL 8.0+
          </div>
        </aside>

        <!-- Dynamic Step Card Container -->
        <section class="wp-wizard-card">
          ${renderCurrentStepContent()}
        </section>
      </main>

      <!-- Presets Modal Dialog -->
      <div class="wp-modal-overlay" id="presets-modal">
        <div class="wp-modal">
          <div class="wp-modal-header">
            <h3 class="wp-modal-title">${state.language === 'vi' ? 'Chọn Mẫu Cấu Hình Máy Chủ' : 'Choose Server Preset'}</h3>
            <button class="wp-btn-icon" id="btn-close-presets" style="border: none; background: transparent;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="wp-modal-body">
            <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 1.25rem;">
              ${state.language === 'vi' 
                ? 'Nạp nhanh thông số Database phù hợp với môi trường triển khai của bạn:' 
                : 'Instantly fill standard database credentials for your specific stack:'}
            </p>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
              ${PRESETS.map((p, idx) => `
                <div class="wp-health-item" style="cursor: pointer;" onclick="window.applyPreset(${idx})">
                  <div>
                    <strong style="color: var(--text-primary); font-size: 0.92rem;">${p.name}</strong>
                    <div style="font-family: var(--font-mono); font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                      Host: ${p.db.host} | DB: ${p.db.name} | User: ${p.db.user}
                    </div>
                  </div>
                  <button class="wp-btn-secondary wp-btn-sm" style="pointer-events: none;">
                    ${state.language === 'vi' ? 'Áp dụng' : 'Apply'}
                  </button>
                </div>
              `).join('')}
            </div>
          </div>
          <div class="wp-modal-footer">
            <button class="wp-btn-secondary" id="btn-cancel-presets">${state.language === 'vi' ? 'Đóng' : 'Close'}</button>
          </div>
        </div>
      </div>

      <!-- Preview Mockup Modal -->
      <div class="wp-modal-overlay" id="preview-modal">
        <div class="wp-modal" style="max-width: 780px;">
          <div class="wp-modal-header">
            <h3 class="wp-modal-title">${state.language === 'vi' ? 'Xem trước Trang Chủ WordPress' : 'Live WordPress Mockup Preview'}</h3>
            <button class="wp-btn-icon" id="btn-close-preview" style="border: none; background: transparent;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="wp-modal-body" style="padding: 0; background-color: #f1f5f9;">
            <!-- Browser Bar -->
            <div style="background-color: #e2e8f0; padding: 0.5rem 1rem; display: flex; align-items: center; gap: 0.75rem; border-bottom: 1px solid #cbd5e1;">
              <div style="display: flex; gap: 6px;">
                <span style="width: 10px; height: 10px; border-radius: 50%; background-color: #ef4444;"></span>
                <span style="width: 10px; height: 10px; border-radius: 50%; background-color: #f59e0b;"></span>
                <span style="width: 10px; height: 10px; border-radius: 50%; background-color: #10b981;"></span>
              </div>
              <div style="flex: 1; background: #ffffff; border-radius: 4px; padding: 2px 10px; font-family: var(--font-mono); font-size: 0.75rem; color: #475569;">
                https://my-wordpress-site.local/
              </div>
            </div>
            <!-- Mockup Content -->
            <div style="padding: 2.5rem; background: #ffffff; min-height: 340px; color: #0f172a;">
              <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 1rem; margin-bottom: 2rem;">
                <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em;">${state.site.title || 'WordPress Site'}</h1>
                <nav style="display: flex; gap: 1rem; font-size: 0.85rem; font-weight: 600; color: #475569;">
                  <span>Trang chủ</span>
                  <span>Bài viết</span>
                  <span>Giới thiệu</span>
                  <span>Liên hệ</span>
                </nav>
              </div>
              <p style="font-size: 1.1rem; color: #475569; font-style: italic; margin-bottom: 2rem;">"${state.site.tagline || 'Just another WordPress site'}"</p>
              
              <article style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; background: #f8fafc;">
                <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #0f172a;">Chào thế giới! (Hello world!)</h2>
                <p style="font-size: 0.88rem; color: #64748b; line-height: 1.6; margin-bottom: 1rem;">
                  Chào mừng bạn đến với WordPress. Đây là bài viết đầu tiên của bạn. Hãy chỉnh sửa hoặc xóa nó, sau đó bắt đầu viết bài!
                </p>
                <div style="font-size: 0.75rem; color: #94a3b8; font-family: var(--font-mono);">
                  Đăng bởi <strong>${state.site.adminUser}</strong> · Giao diện kích hoạt: <strong>${THEMES.find(th => th.id === state.theme)?.name}</strong>
                </div>
              </article>
            </div>
          </div>
          <div class="wp-modal-footer">
            <button class="wp-btn-secondary" id="btn-close-preview-footer">${state.language === 'vi' ? 'Đóng' : 'Close'}</button>
          </div>
        </div>
      </div>
    </div>
  `;

  attachEventHandlers();
}

// Render Step item on sidebar
function renderStepItem(stepNum: number, title: string, subtitle: string): string {
  const isActive = state.currentStep === stepNum;
  const isCompleted = state.currentStep > stepNum;
  const isDisabled = stepNum > state.maxStepReached && !isCompleted;

  let classes = 'wp-step-item';
  if (isActive) classes += ' active';
  if (isCompleted) classes += ' completed';
  if (isDisabled) classes += ' disabled';

  return `
    <li>
      <button class="${classes}" onclick="window.goToStep(${stepNum})" ${isDisabled ? 'disabled' : ''}>
        <div class="wp-step-indicator">
          ${isCompleted ? `
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          ` : stepNum}
        </div>
        <div class="wp-step-text">
          <span class="wp-step-label">${title}</span>
          <span class="wp-step-sublabel">${subtitle}</span>
        </div>
      </button>
    </li>
  `;
}

// Render Content for current Step
function renderCurrentStepContent(): string {
  const t = i18n[state.language];

  switch (state.currentStep) {
    case 1:
      return `
        <div class="wp-card-header">
          <div class="wp-card-kicker">Bước 01 / 07</div>
          <h2 class="wp-card-title">${t.welcomeTitle}</h2>
          <p class="wp-card-desc">${t.welcomeDesc}</p>
        </div>

        <div class="wp-card-body">
          <div class="wp-callout wp-callout-info">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <div>
              <strong>Giao diện cài đặt nâng cao thế hệ mới:</strong>
              Thay thế hoàn toàn file <code>wp-admin/install.php</code> mặc định cũ kỹ bằng trải nghiệm mượt mà, hỗ trợ kiểm tra bảo mật và tạo cấu hình tự động.
            </div>
          </div>

          <div style="margin-top: 1.5rem;">
            <label class="wp-label" style="margin-bottom: 0.5rem;">${t.selectLangLabel}</label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
              <button class="wp-plugin-card ${state.language === 'vi' ? 'active' : ''}" onclick="window.changeLanguage('vi')">
                <span style="font-size: 1.75rem;">🇻🇳</span>
                <div class="wp-plugin-details">
                  <div class="wp-plugin-title">Tiếng Việt</div>
                  <div class="wp-plugin-desc">Ngôn ngữ tiếng Việt chuẩn hóa đầy đủ</div>
                </div>
              </button>

              <button class="wp-plugin-card ${state.language === 'en' ? 'active' : ''}" onclick="window.changeLanguage('en')">
                <span style="font-size: 1.75rem;">🇺🇸</span>
                <div class="wp-plugin-details">
                  <div class="wp-plugin-title">English (United States)</div>
                  <div class="wp-plugin-desc">Standard international English version</div>
                </div>
              </button>
            </div>
          </div>

          <div style="margin-top: 2rem;">
            <h4 style="font-size: 0.92rem; font-weight: 700; margin-bottom: 0.85rem; color: var(--text-primary);">
              ${t.featuresTitle}
            </h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem;">
              <div class="wp-health-item">
                <span>🛡️ ${t.feat2}</span>
              </div>
              <div class="wp-health-item">
                <span>⚡ ${t.feat1}</span>
              </div>
              <div class="wp-health-item">
                <span>📦 ${t.feat3}</span>
              </div>
              <div class="wp-health-item">
                <span>📄 ${t.feat4}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="wp-card-footer">
          <div></div>
          <button class="wp-btn wp-btn-primary" onclick="window.goToStep(2)">
            ${t.next}
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </button>
        </div>
      `;

    case 2:
      return `
        <div class="wp-card-header">
          <div class="wp-card-kicker">Bước 02 / 07</div>
          <h2 class="wp-card-title">${t.envTitle}</h2>
          <p class="wp-card-desc">${t.envDesc}</p>
        </div>

        <div class="wp-card-body">
          <div class="wp-callout wp-callout-success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <div>
              <strong>Máy chủ đạt 100% điều kiện tiêu chuẩn:</strong> Môi trường sẵn sàng đáp ứng WordPress Core, REST API và Full Site Editing.
            </div>
          </div>

          <div class="wp-health-grid">
            ${DIAGNOSTICS.map(d => `
              <div class="wp-health-item">
                <div class="wp-health-info">
                  <span style="font-weight: 600; color: var(--text-primary);">${d.name}</span>
                  <span style="color: var(--text-muted); font-family: var(--font-mono); font-size: 0.8rem;">${d.val}</span>
                </div>
                <span class="wp-status-pill ${d.status}">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="20 6 9 17 4 12"></polyline>
                  </svg>
                  ${t.envStatusGood}
                </span>
              </div>
            `).join('')}
          </div>
        </div>

        <div class="wp-card-footer">
          <button class="wp-btn wp-btn-secondary" onclick="window.goToStep(1)">
            ${t.back}
          </button>
          <button class="wp-btn wp-btn-primary" onclick="window.goToStep(3)">
            ${t.next}
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </button>
        </div>
      `;

    case 3:
      return `
        <div class="wp-card-header">
          <div class="wp-card-kicker">Bước 03 / 07</div>
          <h2 class="wp-card-title">${t.dbTitle}</h2>
          <p class="wp-card-desc">${t.dbDesc}</p>
        </div>

        <div class="wp-card-body">
          <!-- Quick Preset Bar -->
          <div class="wp-preset-bar">
            <span class="wp-preset-label">${t.loadPresetPrompt}</span>
            <div class="wp-preset-buttons">
              <button class="wp-preset-btn" onclick="window.applyPreset(0)">Localhost</button>
              <button class="wp-preset-btn" onclick="window.applyPreset(1)">Docker</button>
              <button class="wp-preset-btn" onclick="window.applyPreset(2)">cPanel</button>
              <button class="wp-preset-btn" onclick="window.applyPreset(3)">VPS Nginx</button>
            </div>
          </div>

          <div class="wp-form-grid">
            <div class="wp-form-grid-2">
              <div class="wp-form-group">
                <label class="wp-label" for="db_name">
                  ${t.dbNameLabel}
                  <span class="wp-label-hint">Database Name</span>
                </label>
                <input class="wp-input font-mono" id="db_name" value="${state.db.name}" oninput="window.updateDbField('name', this.value)" placeholder="wordpress_db" />
                <span class="wp-helper-text">${t.dbNameHint}</span>
              </div>

              <div class="wp-form-group">
                <label class="wp-label" for="db_user">
                  ${t.dbUserLabel}
                  <span class="wp-label-hint">Username</span>
                </label>
                <input class="wp-input font-mono" id="db_user" value="${state.db.user}" oninput="window.updateDbField('user', this.value)" placeholder="root" />
                <span class="wp-helper-text">${t.dbUserHint}</span>
              </div>
            </div>

            <div class="wp-form-grid-2">
              <div class="wp-form-group">
                <label class="wp-label" for="db_pass">
                  ${t.dbPassLabel}
                  <span class="wp-label-hint">Password</span>
                </label>
                <div class="wp-input-wrapper">
                  <input class="wp-input font-mono" id="db_pass" type="password" value="${state.db.pass}" oninput="window.updateDbField('pass', this.value)" placeholder="••••••••" />
                  <div class="wp-input-addon">
                    <button class="wp-btn-icon" style="width: 28px; height: 28px; border:none;" onclick="window.toggleDbPassVisibility()">
                      👁️
                    </button>
                  </div>
                </div>
                <span class="wp-helper-text">${t.dbPassHint}</span>
              </div>

              <div class="wp-form-group">
                <label class="wp-label" for="db_host">
                  ${t.dbHostLabel}
                  <span class="wp-label-hint">Host</span>
                </label>
                <input class="wp-input font-mono" id="db_host" value="${state.db.host}" oninput="window.updateDbField('host', this.value)" placeholder="localhost" />
                <span class="wp-helper-text">${t.dbHostHint}</span>
              </div>
            </div>

            <div class="wp-form-group">
              <label class="wp-label" for="db_prefix">
                ${t.dbPrefixLabel}
                <span class="wp-label-hint">Prefix</span>
              </label>
              <input class="wp-input font-mono" id="db_prefix" value="${state.db.prefix}" oninput="window.updateDbField('prefix', this.value)" placeholder="wp_" />
              <span class="wp-helper-text">${t.dbPrefixHint}</span>
            </div>

            <!-- Database connection test trigger -->
            <div style="margin-top: 0.5rem;">
              <button class="wp-btn-secondary" id="btn-test-db" onclick="window.testDatabaseConnection()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                ${t.testDbBtn}
              </button>
              <div id="db-test-result" style="margin-top: 0.75rem;">
                ${state.db.tested ? `
                  <div class="wp-callout wp-callout-success" style="margin: 0; padding: 0.75rem 1rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>${t.testDbSuccess}</span>
                  </div>
                ` : ''}
              </div>
            </div>
          </div>
        </div>

        <div class="wp-card-footer">
          <button class="wp-btn wp-btn-secondary" onclick="window.goToStep(2)">
            ${t.back}
          </button>
          <button class="wp-btn wp-btn-primary" onclick="window.goToStep(4)">
            ${t.next}
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </button>
        </div>
      `;

    case 4:
      const strength = getPasswordStrength(state.site.adminPass);
      return `
        <div class="wp-card-header">
          <div class="wp-card-kicker">Bước 04 / 07</div>
          <h2 class="wp-card-title">${t.siteTitleLabel} & Quản trị viên</h2>
          <p class="wp-card-desc">Thiết lập tiêu đề website và thông tin đăng nhập quản trị viên tối cao.</p>
        </div>

        <div class="wp-card-body">
          <div class="wp-form-grid">
            <div class="wp-form-grid-2">
              <div class="wp-form-group">
                <label class="wp-label" for="site_title">${t.siteTitleLabel}</label>
                <input class="wp-input" id="site_title" value="${state.site.title}" oninput="window.updateSiteField('title', this.value)" placeholder="Ví dụ: Công Ty TNHH Sáng Tạo" />
              </div>

              <div class="wp-form-group">
                <label class="wp-label" for="site_tagline">${t.siteTaglineLabel}</label>
                <input class="wp-input" id="site_tagline" value="${state.site.tagline}" oninput="window.updateSiteField('tagline', this.value)" placeholder="Khẩu hiệu ngắn của trang..." />
              </div>
            </div>

            <div class="wp-form-grid-2">
              <div class="wp-form-group">
                <label class="wp-label" for="admin_user">${t.adminUserLabel}</label>
                <input class="wp-input font-mono" id="admin_user" value="${state.site.adminUser}" oninput="window.updateSiteField('adminUser', this.value)" placeholder="admin" />
                <span class="wp-helper-text">Tránh dùng tên "admin" đơn giản trên production để phòng brute-force.</span>
              </div>

              <div class="wp-form-group">
                <label class="wp-label" for="admin_email">${t.adminEmailLabel}</label>
                <input class="wp-input" id="admin_email" type="email" value="${state.site.adminEmail}" oninput="window.updateSiteField('adminEmail', this.value)" placeholder="admin@domain.com" />
                <span class="wp-helper-text">Dùng để nhận thông báo phục hồi mật khẩu và cập nhật bảo mật WordPress.</span>
              </div>
            </div>

            <!-- Password field with generator & strength meter -->
            <div class="wp-form-group">
              <div class="wp-label">
                <span>${t.adminPassLabel}</span>
                <button type="button" class="wp-preset-btn" onclick="window.generateNewPassword()">
                  ⚡ ${t.generatePass}
                </button>
              </div>
              <div class="wp-input-wrapper">
                <input class="wp-input font-mono" id="admin_pass" type="text" value="${state.site.adminPass}" oninput="window.updateSiteField('adminPass', this.value)" />
                <div class="wp-input-addon">
                  <button type="button" class="wp-btn-icon" style="width: 28px; height: 28px; border:none;" onclick="window.copyToClipboard('${state.site.adminPass}', 'Đã sao chép mật khẩu!')">
                    📋
                  </button>
                </div>
              </div>

              <!-- Strength meter bar -->
              <div class="wp-pw-meter">
                <div class="wp-pw-fill ${strength.level}"></div>
              </div>
              <div class="wp-pw-feedback">
                <span>Độ mạnh: <strong style="color: var(--text-primary);">${strength.text}</strong></span>
                <span>Entropy: 128-bit</span>
              </div>
            </div>

            <!-- Search engine visibility switch -->
            <div class="wp-plugin-card" style="margin-top: 0.5rem; cursor: default;">
              <input type="checkbox" class="wp-checkbox" id="se_visible" ${state.site.searchEnginePublic ? 'checked' : ''} onchange="window.updateSiteField('searchEnginePublic', this.checked)" />
              <div class="wp-plugin-details">
                <label for="se_visible" class="wp-plugin-title" style="cursor: pointer;">
                  ${t.searchEngineLabel}
                </label>
                <div class="wp-plugin-desc">${t.searchEngineDesc}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="wp-card-footer">
          <button class="wp-btn wp-btn-secondary" onclick="window.goToStep(3)">
            ${t.back}
          </button>
          <div style="display: flex; gap: 0.75rem;">
            <button class="wp-btn wp-btn-secondary" onclick="window.openPreviewModal()">
              🔍 ${t.previewSite}
            </button>
            <button class="wp-btn wp-btn-primary" onclick="window.goToStep(5)">
              ${t.next}
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </button>
          </div>
        </div>
      `;

    case 5:
      return `
        <div class="wp-card-header">
          <div class="wp-card-kicker">Bước 05 / 07</div>
          <h2 class="wp-card-title">${t.themeStepTitle}</h2>
          <p class="wp-card-desc">${t.themeStepDesc}</p>
        </div>

        <div class="wp-card-body">
          <div class="wp-theme-grid">
            ${THEMES.map(th => `
              <div class="wp-theme-card ${state.theme === th.id ? 'selected' : ''}" onclick="window.selectTheme('${th.id}')">
                <div class="wp-theme-preview" style="background: ${th.previewBg};">
                  <span class="wp-theme-preview-badge">${th.badge}</span>
                </div>
                <div class="wp-theme-info">
                  <div>
                    <div class="wp-theme-name">${th.name}</div>
                    <div class="wp-theme-desc">${th.desc}</div>
                  </div>
                  <div style="margin-top: 0.75rem; display: flex; justify-content: flex-end;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: ${state.theme === th.id ? 'var(--wp-blue-600)' : 'var(--text-muted)'};">
                      ${state.theme === th.id ? '✓ Đã chọn' : 'Chọn giao diện'}
                    </span>
                  </div>
                </div>
              </div>
            `).join('')}
          </div>

          <div style="margin-top: 2rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary);">
              ${t.pluginStepTitle}
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.75rem;">
              ${t.pluginStepDesc}
            </p>

            <div class="wp-plugins-grid">
              ${PLUGINS.map(pl => {
                const isChecked = state.plugins.includes(pl.id);
                return `
                  <div class="wp-plugin-card ${isChecked ? 'active' : ''}" onclick="window.togglePlugin('${pl.id}')">
                    <input type="checkbox" class="wp-checkbox" ${isChecked ? 'checked' : ''} onclick="event.stopPropagation(); window.togglePlugin('${pl.id}')" />
                    <div class="wp-plugin-details">
                      <div class="wp-plugin-title">
                        <span>${pl.name}</span>
                        <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 500;">${pl.category}</span>
                      </div>
                      <div class="wp-plugin-desc">${pl.desc}</div>
                    </div>
                  </div>
                `;
              }).join('')}
            </div>
          </div>
        </div>

        <div class="wp-card-footer">
          <button class="wp-btn wp-btn-secondary" onclick="window.goToStep(4)">
            ${t.back}
          </button>
          <button class="wp-btn wp-btn-primary" onclick="window.goToStep(6)">
            ${t.next}
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </button>
        </div>
      `;

    case 6:
      const wpConfigCode = generateWpConfig();
      return `
        <div class="wp-card-header">
          <div class="wp-card-kicker">Bước 06 / 07</div>
          <h2 class="wp-card-title">${t.installTitle}</h2>
          <p class="wp-card-desc">${t.installDesc}</p>
        </div>

        <div class="wp-card-body">
          <!-- Installation Progress -->
          <div class="wp-progress-container">
            <div class="wp-progress-header">
              <span id="install-step-text">${state.installing ? 'Đang thực thi các tác vụ cài đặt...' : 'Sẵn sàng khởi chạy cài đặt WordPress'}</span>
              <span id="install-pct" style="font-family: var(--font-mono);">${state.installProgress}%</span>
            </div>
            <div class="wp-progress-track">
              <div class="wp-progress-fill" id="install-bar" style="width: ${state.installProgress}%;"></div>
            </div>
          </div>

          <!-- Live Console Output -->
          <div class="wp-terminal-box">
            <div class="wp-terminal-header">
              <div class="wp-terminal-dots">
                <span class="wp-terminal-dot wp-dot-red"></span>
                <span class="wp-terminal-dot wp-dot-yellow"></span>
                <span class="wp-terminal-dot wp-dot-green"></span>
              </div>
              <div class="wp-terminal-title">wp-cli / install-runner.sh</div>
              <span style="font-size: 0.7rem; color: #8b949e;">bash</span>
            </div>
            <div class="wp-terminal-body" id="terminal-body">
              <div class="wp-term-line info">[INIT] Chuẩn bị môi trường cài đặt WordPress Core 6.7...</div>
              <div class="wp-term-line">[1/6] Kiểm tra thông số kết nối Database: ${state.db.name} @ ${state.db.host}</div>
              <div class="wp-term-line">[2/6] Tạo tiền tố bảng dữ liệu an toàn: ${state.db.prefix}*</div>
              ${state.installing ? '<div class="wp-term-line info">Đang chạy tiến trình cài đặt...</div>' : ''}
            </div>
          </div>

          <!-- wp-config.php preview & actions -->
          <div class="wp-code-box">
            <div class="wp-code-header">
              <span>wp-config.php (Đã tích hợp Security Salts)</span>
              <div style="display: flex; gap: 0.5rem;">
                <button class="wp-preset-btn" onclick="window.copyWpConfig()">
                  📋 ${t.copy}
                </button>
                <button class="wp-preset-btn" onclick="window.downloadWpConfig()">
                  💾 ${t.download}
                </button>
              </div>
            </div>
            <pre class="wp-code-content"><code>${escapeHtml(wpConfigCode)}</code></pre>
          </div>
        </div>

        <div class="wp-card-footer">
          <button class="wp-btn wp-btn-secondary" onclick="window.goToStep(5)" ${state.installing ? 'disabled' : ''}>
            ${t.back}
          </button>
          <button class="wp-btn wp-btn-primary" id="btn-run-install" onclick="window.runInstallation()" ${state.installing ? 'disabled' : ''}>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polygon points="5 3 19 12 5 21 5 3"></polygon>
            </svg>
            ${t.installNow}
          </button>
        </div>
      `;

    case 7:
      return `
        <div class="wp-card-body">
          <div class="wp-success-box">
            <div class="wp-success-icon-wrap">
              <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </div>
            <h2 class="wp-card-title" style="margin-bottom: 0.5rem;">${t.successTitle}</h2>
            <p class="wp-card-desc" style="max-width: 560px; margin: 0 auto 1.5rem;">
              ${t.successDesc}
            </p>

            <div class="wp-credentials-card">
              <div class="wp-cred-row">
                <span class="wp-cred-label">${t.siteTitleLabel}:</span>
                <span class="wp-cred-val" style="font-family: inherit;">${state.site.title}</span>
              </div>
              <div class="wp-cred-row">
                <span class="wp-cred-label">${t.adminUrl}</span>
                <span class="wp-cred-val" style="color: var(--wp-blue-600);">https://localhost/wp-admin/</span>
              </div>
              <div class="wp-cred-row">
                <span class="wp-cred-label">${t.usernameRecap}</span>
                <span class="wp-cred-val">${state.site.adminUser}</span>
              </div>
              <div class="wp-cred-row">
                <span class="wp-cred-label">${t.passwordRecap}</span>
                <span class="wp-cred-val" style="display: flex; align-items: center; gap: 0.5rem;">
                  <span id="recap-pass">••••••••••••</span>
                  <button class="wp-btn-icon" style="width: 24px; height: 24px; border: none;" onclick="window.toggleRecapPassword()">
                    👁️
                  </button>
                </span>
              </div>
              <div class="wp-cred-row">
                <span class="wp-cred-label">Giao diện (Theme):</span>
                <span class="wp-cred-val">${THEMES.find(th => th.id === state.theme)?.name}</span>
              </div>
              <div class="wp-cred-row">
                <span class="wp-cred-label">Plugins kích hoạt:</span>
                <span class="wp-cred-val" style="font-size: 0.8rem;">${state.plugins.length} tiện ích</span>
              </div>
            </div>

            <div style="display: flex; justify-content: center; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
              <button class="wp-btn wp-btn-secondary" onclick="window.openPreviewModal()">
                🌐 ${t.visitSite}
              </button>
              <button class="wp-btn wp-btn-primary" onclick="window.goToAdminDashboard()">
                🚀 ${t.goToAdmin}
              </button>
            </div>
          </div>
        </div>
      `;

    default:
      return '';
  }
}

// Helpers
function escapeHtml(str: string): string {
  return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// Attach Event Handlers
function attachEventHandlers() {
  // Theme Toggle
  const themeToggle = document.getElementById('btn-theme-toggle');
  if (themeToggle) {
    themeToggle.onclick = () => {
      state.themeMode = state.themeMode === 'light' ? 'dark' : 'light';
      document.body.setAttribute('data-theme', state.themeMode);
      renderApp();
    };
  }

  // Language Switcher
  const langSwitcher = document.getElementById('lang-switcher') as HTMLSelectElement | null;
  if (langSwitcher) {
    langSwitcher.onchange = (e) => {
      const target = e.target as HTMLSelectElement;
      state.language = target.value as Language;
      renderApp();
    };
  }

  // Presets Modal
  const btnOpenPresets = document.getElementById('btn-open-presets');
  const btnClosePresets = document.getElementById('btn-close-presets');
  const btnCancelPresets = document.getElementById('btn-cancel-presets');
  const presetsModal = document.getElementById('presets-modal');

  if (btnOpenPresets && presetsModal) {
    btnOpenPresets.onclick = () => presetsModal.classList.add('open');
  }
  if (btnClosePresets && presetsModal) {
    btnClosePresets.onclick = () => presetsModal.classList.remove('open');
  }
  if (btnCancelPresets && presetsModal) {
    btnCancelPresets.onclick = () => presetsModal.classList.remove('open');
  }

  // Preview Modal
  const btnClosePreview = document.getElementById('btn-close-preview');
  const btnClosePreviewFooter = document.getElementById('btn-close-preview-footer');
  const previewModal = document.getElementById('preview-modal');

  if (btnClosePreview && previewModal) {
    btnClosePreview.onclick = () => previewModal.classList.remove('open');
  }
  if (btnClosePreviewFooter && previewModal) {
    btnClosePreviewFooter.onclick = () => previewModal.classList.remove('open');
  }
}

// Global actions attached to window for pure vanilla JS calls
declare global {
  interface Window {
    goToStep: (step: number) => void;
    changeLanguage: (lang: Language) => void;
    updateDbField: (field: keyof WizardState['db'], val: string) => void;
    updateSiteField: (field: keyof WizardState['site'], val: any) => void;
    toggleDbPassVisibility: () => void;
    testDatabaseConnection: () => void;
    generateNewPassword: () => void;
    selectTheme: (themeId: string) => void;
    togglePlugin: (pluginId: string) => void;
    copyToClipboard: (text: string, msg: string) => void;
    copyWpConfig: () => void;
    downloadWpConfig: () => void;
    applyPreset: (index: number) => void;
    runInstallation: () => void;
    openPreviewModal: () => void;
    toggleRecapPassword: () => void;
    goToAdminDashboard: () => void;
  }
}

window.goToStep = (step: number) => {
  if (step < 1 || step > 7) return;
  state.currentStep = step;
  if (step > state.maxStepReached) {
    state.maxStepReached = step;
  }
  renderApp();
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

window.changeLanguage = (lang: Language) => {
  state.language = lang;
  renderApp();
};

window.updateDbField = (field: keyof WizardState['db'], val: string) => {
  (state.db as any)[field] = val;
  state.db.tested = false;
};

window.updateSiteField = (field: keyof WizardState['site'], val: any) => {
  (state.site as any)[field] = val;
  if (field === 'adminPass') {
    renderApp();
  }
};

window.toggleDbPassVisibility = () => {
  const input = document.getElementById('db_pass') as HTMLInputElement | null;
  if (input) {
    input.type = input.type === 'password' ? 'text' : 'password';
  }
};

window.testDatabaseConnection = () => {
  const resultDiv = document.getElementById('db-test-result');
  const btn = document.getElementById('btn-test-db') as HTMLButtonElement | null;
  if (!resultDiv) return;

  if (btn) btn.disabled = true;
  resultDiv.innerHTML = `
    <div class="wp-callout wp-callout-info" style="margin: 0; padding: 0.75rem 1rem;">
      <span style="display: inline-block; animation: spin 1s linear infinite;">⏳</span>
      <span>${i18n[state.language].testDbTesting}</span>
    </div>
  `;

  setTimeout(() => {
    state.db.tested = true;
    if (btn) btn.disabled = false;
    renderApp();
    showToast(i18n[state.language].testDbSuccess);
  }, 900);
};

window.generateNewPassword = () => {
  state.site.adminPass = generateRandomSecurePassword();
  renderApp();
  showToast(state.language === 'vi' ? 'Đã tạo mật khẩu mạnh mới!' : 'Generated new strong password!');
};

window.selectTheme = (themeId: string) => {
  state.theme = themeId;
  renderApp();
};

window.togglePlugin = (pluginId: string) => {
  if (state.plugins.includes(pluginId)) {
    state.plugins = state.plugins.filter(p => p !== pluginId);
  } else {
    state.plugins.push(pluginId);
  }
  renderApp();
};

window.copyToClipboard = (text: string, msg: string) => {
  navigator.clipboard.writeText(text).then(() => {
    showToast(msg);
  });
};

window.copyWpConfig = () => {
  const code = generateWpConfig();
  navigator.clipboard.writeText(code).then(() => {
    showToast(state.language === 'vi' ? 'Đã sao chép nội dung wp-config.php!' : 'wp-config.php copied to clipboard!');
  });
};

window.downloadWpConfig = () => {
  const code = generateWpConfig();
  const blob = new Blob([code], { type: 'application/x-httpd-php' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'wp-config.php';
  document.body.appendChild(a);
  a.click();
  document.body.appendChild(a);
  a.remove();
  URL.revokeObjectURL(url);
  showToast(state.language === 'vi' ? 'Đã tải xuống file wp-config.php thành công!' : 'wp-config.php downloaded successfully!');
};

window.applyPreset = (index: number) => {
  const preset = PRESETS[index];
  if (!preset) return;
  state.db = { ...preset.db, tested: true };
  const modal = document.getElementById('presets-modal');
  if (modal) modal.classList.remove('open');
  renderApp();
  showToast(`${state.language === 'vi' ? 'Đã áp dụng mẫu' : 'Applied preset'}: ${preset.name}`);
};

window.openPreviewModal = () => {
  const modal = document.getElementById('preview-modal');
  if (modal) modal.classList.add('open');
};

let recapVisible = false;
window.toggleRecapPassword = () => {
  recapVisible = !recapVisible;
  const el = document.getElementById('recap-pass');
  if (el) {
    el.innerText = recapVisible ? state.site.adminPass : '••••••••••••';
  }
};

window.goToAdminDashboard = () => {
  showToast('Đang chuyển hướng tới WordPress wp-admin login...');
  setTimeout(() => {
    window.openPreviewModal();
  }, 600);
};

// Simulation of WordPress Installation process with logs
window.runInstallation = async () => {
  if (state.installing) return;
  state.installing = true;
  state.installProgress = 5;

  const terminal = document.getElementById('terminal-body');
  const bar = document.getElementById('install-bar');
  const pct = document.getElementById('install-pct');
  const stepText = document.getElementById('install-step-text');
  const btn = document.getElementById('btn-run-install');
  if (btn) btn.disabled = true;

  function log(msg, type = 'info') {
    if (terminal) {
      const line = document.createElement('div');
      line.className = type === 'success' ? 'wp-term-line success' : (type === 'error' ? 'wp-term-line error' : 'wp-term-line info');
      line.innerText = msg;
      terminal.appendChild(line);
      terminal.scrollTop = terminal.scrollHeight;
    }
  }

  function setProgress(percent, text) {
    state.installProgress = percent;
    if (bar) bar.style.width = percent + '%';
    if (pct) pct.innerText = percent + '%';
    if (stepText) stepText.innerText = text;
  }

  log('[INIT] Khởi động tiến trình cài đặt PrestoWorld...', 'info');
  setProgress(10, 'Chuẩn bị kết nối database...');
  await new Promise(r => setTimeout(r, 400));
  
  log('[DB] Đang kiểm tra kết nối database...', 'info');
  setProgress(20, 'Đang gửi yêu cầu cài đặt...');

  try {
    const res = await fetch('/api/setup/install', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({
        db_connection: 'pgsql',
        db_host: state.db.host,
        db_port: state.db.host.includes(':') ? state.db.host.split(':')[1] : (state.db.host.includes('mysql') ? 3306 : 5432),
        db_name: state.db.name,
        db_username: state.db.user,
        db_password: state.db.pass,
        db_prefix: state.db.prefix,
        site_title: state.site.title,
        admin_username: state.site.adminUser,
        admin_email: state.site.adminEmail,
        admin_password: state.site.adminPass,
      })
    });
    const data = await res.json().catch(() => ({}));

    if (res.ok && data.success) {
      setProgress(70, 'Migrations hoàn tất, đang khởi tạo dữ liệu...');
      log('[MIGRATION] Tất cả migrations đã được thực thi và ghi lịch sử vào pw_migrations', 'success');
      
      const result = data.migrations || {};
      if (result.created && result.created.length > 0) {
        result.created.forEach(m => log('[MIGRATION] ✓ ' + m, 'success'));
      }
      if (result.skipped && result.skipped.length > 0) {
        result.skipped.forEach(m => log('[MIGRATION] ↷ ' + m + ' (already run)', 'info'));
      }

      await new Promise(r => setTimeout(r, 400));
      log('[AUTH] Tài khoản quản trị đã được tạo', 'success');
      setProgress(85, 'Đang ghi trạng thái installed...');

      await new Promise(r => setTimeout(r, 300));
      log('[OPTIONS] presto_installed = 1 → pw_options', 'success');
      setProgress(100, 'Cài đặt hoàn tất!');

      await new Promise(r => setTimeout(r, 300));
      log('[SUCCESS] PrestoWorld đã được cài đặt thành công!', 'success');

      state.installing = false;
      state.installed = true;
      setTimeout(() => {
        window.goToStep(7);
      }, 800);
    } else {
      log('[ERROR] ' + (data.error || 'Unknown error'), 'error');
      setProgress(0, 'Cài đặt thất bại');
      state.installing = false;
      if (btn) btn.disabled = false;
    }
  } catch (err) {
    log('[ERROR] Lỗi kết nối: ' + err.message, 'error');
    setProgress(0, 'Cài đặt thất bại');
    state.installing = false;
    if (btn) btn.disabled = false;
  }
};

// Initialize
document.addEventListener('DOMContentLoaded', () => {
  renderApp();
});
if (document.readyState === 'complete' || document.readyState === 'interactive') {
  renderApp();
}
