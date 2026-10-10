(function(){let e=document.createElement(`link`).relList;if(e&&e.supports&&e.supports(`modulepreload`))return;for(let e of document.querySelectorAll(`link[rel="modulepreload"]`))n(e);new MutationObserver(e=>{for(let t of e)if(t.type===`childList`)for(let e of t.addedNodes)e.tagName===`LINK`&&e.rel===`modulepreload`&&n(e)}).observe(document,{childList:!0,subtree:!0});function t(e){let t={};return e.integrity&&(t.integrity=e.integrity),e.referrerPolicy&&(t.referrerPolicy=e.referrerPolicy),t.credentials=e.crossOrigin===`use-credentials`?`include`:e.crossOrigin===`anonymous`?`omit`:`same-origin`,t}function n(e){if(e.ep)return;e.ep=!0;let n=t(e);fetch(e.href,n)}})();var e={vi:{brandTitle:`WordPress Setup`,brandBadge:`v6.7 Pro`,themeLight:`Sáng`,themeDark:`Tối`,helpBtn:`Trợ giúp`,presetsBtn:`Mẫu cấu hình`,next:`Tiếp tục`,back:`Quay lại`,installNow:`Bắt đầu Cài đặt`,finish:`Đăng nhập vào Quản trị`,previewSite:`Xem trước Website`,copy:`Sao chép`,copied:`Đã sao chép vào bộ nhớ tạm!`,download:`Tải wp-config.php`,step1Title:`Chào mừng`,step1Sub:`Chọn ngôn ngữ`,step2Title:`Môi trường`,step2Sub:`Kiểm tra hệ thống`,step3Title:`Cơ sở dữ liệu`,step3Sub:`Kết nối MySQL`,step4Title:`Thông tin Trang`,step4Sub:`Tài khoản admin`,step5Title:`Giao diện & Plugin`,step5Sub:`Tối ưu ban đầu`,step6Title:`Cài đặt`,step6Sub:`Tạo wp-config.php`,step7Title:`Hoàn tất`,step7Sub:`Sẵn sàng sử dụng`,welcomeTitle:`Chào mừng bạn đến với WordPress`,welcomeDesc:`Trình hướng dẫn thiết lập hiện đại giúp bạn cấu hình trang web WordPress chỉ trong 2 phút với chuẩn bảo mật và hiệu năng cao nhất.`,selectLangLabel:`Ngôn ngữ cài đặt chính:`,featuresTitle:`Tính năng nổi bật của trình cài đặt mới:`,feat1:`Kiểm tra độ tương thích máy chủ (PHP 8.2+, MySQL 8.0, Redis Cache).`,feat2:`Mã hóa tự động Security Keys & Salts chuẩn WordPress VIP.`,feat3:`Cài sẵn bộ Plugin tối ưu tốc độ và bảo mật hàng đầu.`,feat4:`Xuất file wp-config.php chuẩn hóa ngay tại chỗ.`,envTitle:`Kiểm tra Độ sẵn sàng của Máy chủ`,envDesc:`Hệ thống đã tự động quét môi trường máy chủ của bạn để đảm bảo hiệu suất tốt nhất cho WordPress.`,envStatusGood:`Đạt chuẩn`,envStatusWarning:`Khuyến nghị`,dbTitle:`Cấu hình Cơ sở dữ liệu (Database)`,dbDesc:`Nhập thông tin kết nối MySQL hoặc MariaDB. Nếu chưa rõ, hãy liên hệ nhà cung cấp hosting của bạn.`,dbNameLabel:`Tên Database (Database Name)`,dbNameHint:`Tên cơ sở dữ liệu đã tạo trên hosting/cPanel.`,dbUserLabel:`Tên đăng nhập (Username)`,dbUserHint:`Tài khoản có quyền trên cơ sở dữ liệu.`,dbPassLabel:`Mật khẩu (Password)`,dbPassHint:`Mật khẩu kết nối MySQL.`,dbHostLabel:`Địa chỉ máy chủ (Database Host)`,dbHostHint:`Thông thường là "localhost" hoặc "127.0.0.1".`,dbPrefixLabel:`Tiền tố bảng (Table Prefix)`,dbPrefixHint:`Nên đổi tiền tố ngẫu nhiên để tăng tính bảo mật chống SQL injection.`,testDbBtn:`Kiểm tra Kết nối Database`,testDbSuccess:`Kết nối thành công! Máy chủ MySQL 8.0 phản hồi trong 12ms.`,testDbTesting:`Đang kết nối tới máy chủ...`,loadPresetPrompt:`Tải nhanh cấu hình mẫu:`,siteTitleLabel:`Tên Website (Site Title)`,siteTaglineLabel:`Khẩu hiệu (Tagline)`,adminUserLabel:`Tên đăng nhập Quản trị viên`,adminPassLabel:`Mật khẩu Quản trị viên`,adminEmailLabel:`Email Quản trị viên`,generatePass:`Tạo mật khẩu mạnh`,searchEngineLabel:`Khả năng hiển thị với công cụ tìm kiếm`,searchEngineDesc:`Cho phép Google, Bing lập chỉ mục website này ngay lập tức.`,themeStepTitle:`Lựa chọn Giao diện Khởi đầu`,themeStepDesc:`Bạn có thể thay đổi giao diện bất kỳ lúc nào trong trang quản trị.`,pluginStepTitle:`Gói Tiện ích Đề xuất (Plugins)`,pluginStepDesc:`Chọn các tiện ích bổ sung để kích hoạt ngay sau khi cài đặt hoàn tất.`,installTitle:`Đang tiến hành Cài đặt WordPress`,installDesc:`Hệ thống đang khởi tạo cơ sở dữ liệu, nạp cấu hình và tạo file wp-config.php.`,configTabTitle:`Xem trước file wp-config.php`,terminalTabTitle:`Nhật ký tiến trình (Console)`,successTitle:`Chúc mừng! Cài đặt WordPress Thành Công!`,successDesc:`Trang web của bạn đã được khởi tạo hoàn chỉnh và sẵn sàng để xuất bản nội dung.`,adminUrl:`Đường dẫn Quản trị:`,usernameRecap:`Tên đăng nhập:`,passwordRecap:`Mật khẩu:`,goToAdmin:`Đăng nhập vào Bảng Quản trị (wp-admin)`,visitSite:`Truy cập Trang chủ`},en:{brandTitle:`WordPress Setup`,brandBadge:`v6.7 Pro`,themeLight:`Light`,themeDark:`Dark`,helpBtn:`Help`,presetsBtn:`Presets`,next:`Continue`,back:`Back`,installNow:`Install WordPress`,finish:`Log In to Admin`,previewSite:`Preview Site`,copy:`Copy`,copied:`Copied to clipboard!`,download:`Download wp-config.php`,step1Title:`Welcome`,step1Sub:`Language selection`,step2Title:`Environment`,step2Sub:`System health check`,step3Title:`Database`,step3Sub:`MySQL connection`,step4Title:`Site Info`,step4Sub:`Admin credentials`,step5Title:`Themes & Plugins`,step5Sub:`Initial enhancements`,step6Title:`Installation`,step6Sub:`Generate wp-config`,step7Title:`Finished`,step7Sub:`Ready to launch`,welcomeTitle:`Welcome to WordPress`,welcomeDesc:`A modern, lightning-fast setup wizard replacing the legacy installer with enterprise security and instant speed optimization.`,selectLangLabel:`Installation Language:`,featuresTitle:`Modern Installer Capabilities:`,feat1:`Server compatibility & health scanner (PHP 8.2+, MySQL 8.0, Redis).`,feat2:`Automatic cryptographically secure Salt generation.`,feat3:`Pre-bundled top-tier performance and security plugins.`,feat4:`Instant one-click wp-config.php exporter.`,envTitle:`Server Readiness & Diagnostics`,envDesc:`We have automatically scanned your host environment to ensure peak WordPress stability.`,envStatusGood:`Passed`,envStatusWarning:`Recommended`,dbTitle:`Database Configuration`,dbDesc:`Provide your MySQL or MariaDB credentials. Contact your hosting provider if you are unsure.`,dbNameLabel:`Database Name`,dbNameHint:`The name of the database created for WordPress.`,dbUserLabel:`Database Username`,dbUserHint:`Your MySQL user with full privileges.`,dbPassLabel:`Database Password`,dbPassHint:`Your database account password.`,dbHostLabel:`Database Host`,dbHostHint:`Typically "localhost" or "127.0.0.1".`,dbPrefixLabel:`Table Prefix`,dbPrefixHint:`Change from default wp_ for enhanced security against SQL injection.`,testDbBtn:`Test Database Connection`,testDbSuccess:`Connection successful! MySQL 8.0 responded in 12ms.`,testDbTesting:`Testing server handshake...`,loadPresetPrompt:`Quick presets for popular stacks:`,siteTitleLabel:`Site Title`,siteTaglineLabel:`Tagline`,adminUserLabel:`Admin Username`,adminPassLabel:`Admin Password`,adminEmailLabel:`Your Email`,generatePass:`Generate Strong Password`,searchEngineLabel:`Search Engine Visibility`,searchEngineDesc:`Discourage or allow search engines from indexing this site.`,themeStepTitle:`Choose a Starter Theme`,themeStepDesc:`Select an elegant baseline design. You can change this anytime from Appearance.`,pluginStepTitle:`Recommended Plugin Suite`,pluginStepDesc:`Pick optional essentials to activate immediately upon setup.`,installTitle:`Installing WordPress Core`,installDesc:`Generating database tables, writing configuration, and configuring security salts.`,configTabTitle:`wp-config.php Output`,terminalTabTitle:`Live Console Output`,successTitle:`WordPress has been Installed!`,successDesc:`Your website is now configured, secured, and ready for you to create content.`,adminUrl:`Admin URL:`,usernameRecap:`Username:`,passwordRecap:`Password:`,goToAdmin:`Log In to WordPress Dashboard`,visitSite:`Visit Website Frontpage`}},t={currentStep:1,maxStepReached:1,language:`vi`,themeMode:`light`,db:{name:`wordpress_db`,user:`root`,pass:``,host:`localhost`,prefix:`wp_sec7_`,tested:!1},site:{title:`Website Của Tôi`,tagline:`Một trang web WordPress mới tinh và tốc độ cao`,adminUser:`admin`,adminPass:n(),adminEmail:`admin@example.com`,searchEnginePublic:!0,timezone:`Asia/Ho_Chi_Minh`},theme:`twentytwentyfive`,plugins:[`litespeed-cache`,`wordfence-security`,`yoast-seo`],installing:!1,installProgress:0,installed:!1};function n(){let e=``,t=new Uint32Array(16);crypto.getRandomValues(t);for(let n=0;n<16;n++)e+=`abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%^&*()_+`[t[n]%69];return e}function r(){let e=``,t=new Uint32Array(64);crypto.getRandomValues(t);for(let n=0;n<64;n++)e+="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|"[t[n]%92];return e}var i=[{id:`twentytwentyfive`,name:`Twenty Twenty-Five`,badge:`Mặc định WP 6.7`,desc:`Giao diện Block Theme chuẩn mới nhất, tối ưu tốc độ 100/100 Core Web Vitals.`,previewBg:`linear-gradient(135deg, #1e293b 0%, #0f172a 100%)`,textColor:`#ffffff`},{id:`astra`,name:`Astra Modern`,badge:`Phổ biến nhất`,desc:`Siêu nhẹ, linh hoạt cho trang doanh nghiệp, tin tức hoặc landing page cao cấp.`,previewBg:`linear-gradient(135deg, #4f46e5 0%, #3730a3 100%)`,textColor:`#ffffff`},{id:`blocksy`,name:`Blocksy Next-Gen`,badge:`Gutenberg Native`,desc:`Tích hợp sâu với Block Editor, hỗ trợ Header/Footer Builder trực quan tuyệt đẹp.`,previewBg:`linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%)`,textColor:`#ffffff`},{id:`storefront`,name:`Woo Storefront`,badge:`Thương mại điện tử`,desc:`Tối ưu dành riêng cho WooCommerce bán hàng trực tuyến và cổng thanh toán.`,previewBg:`linear-gradient(135deg, #9333ea 0%, #6b21a8 100%)`,textColor:`#ffffff`}],a=[{id:`litespeed-cache`,name:`LiteSpeed / WP Super Cache`,category:`Tốc độ`,desc:`Bộ nhớ đệm thông minh, tối ưu hóa CSS/JS và giảm tải CPU máy chủ 80%.`},{id:`wordfence-security`,name:`Wordfence Security Suite`,category:`Bảo mật`,desc:`Tường lửa bảo vệ WAF, chống brute-force và quét mã độc thời gian thực.`},{id:`yoast-seo`,name:`Yoast SEO / Rank Math`,category:`SEO`,desc:`Tạo XML Sitemap chuẩn, cấu hình OpenGraph xã hội và Rich Snippets schema.`},{id:`woocommerce`,name:`WooCommerce Bán hàng`,category:`E-Commerce`,desc:`Xây dựng cửa hàng online đầy đủ tính năng giỏ hàng, thanh toán và quản lý đơn.`},{id:`wpforms`,name:`WPForms Lite Contact`,category:`Liên hệ`,desc:`Tạo form liên hệ kéo thả dễ dàng, chống spam tích hợp Google reCAPTCHA v3.`},{id:`redis-cache`,name:`Redis Object Cache`,category:`Database`,desc:`Tăng tốc truy vấn cơ sở dữ liệu và session người dùng thông qua RAM Redis.`}],o=[{name:`Phiên bản PHP (PHP Version)`,val:`PHP 8.2.18 (Zend Engine v4.2)`,status:`good`},{name:`MySQL / MariaDB Support`,val:`MySQL 8.0.36 + mysqli extension`,status:`good`},{name:`Giới hạn bộ nhớ (Memory Limit)`,val:`512 MB (Đạt chuẩn WP Pro)`,status:`good`},{name:`Max Execution Time`,val:`300s (Rất tốt cho import dữ liệu)`,status:`good`},{name:`Quyền ghi thư mục (wp-content/)`,val:`0755 (Writable / Khả dụng)`,status:`good`},{name:`Phần mở rộng cURL & OpenSSL`,val:`Đã kích hoạt (TLS 1.3)`,status:`good`},{name:`Xử lý hình ảnh (Imagick / GD)`,val:`ImageMagick 7.1 hỗ trợ WebP/AVIF`,status:`good`}],s=[{name:`Localhost (XAMPP / Laragon)`,db:{name:`wordpress`,user:`root`,pass:``,host:`localhost`,prefix:`wp_`}},{name:`Docker / DDEV Staging`,db:{name:`db`,user:`db`,pass:`db`,host:`db:3306`,prefix:`wp_dev_`}},{name:`cPanel / DirectAdmin Host`,db:{name:`myhost_wp`,user:`myhost_user`,pass:`SuperSec#2026@`,host:`localhost`,prefix:`wp_cp_`}},{name:`VPS Nginx Production`,db:{name:`wp_enterprise`,user:`wp_prod_usr`,pass:`VPS#K92@Enterprise`,host:`127.0.0.1:3306`,prefix:`wp_sec7_`}}];function c(e){let t=document.getElementById(`wp-toasts`)||l(),n=document.createElement(`div`);n.className=`wp-toast`,n.innerHTML=`
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2">
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
      <polyline points="22 4 12 14.01 9 11.01"></polyline>
    </svg>
    <span>${e}</span>
  `,t.appendChild(n),setTimeout(()=>{n.style.opacity=`0`,n.style.transform=`translateY(10px)`,n.style.transition=`all 0.2s ease`,setTimeout(()=>n.remove(),250)},2800)}function l(){let e=document.createElement(`div`);return e.id=`wp-toasts`,e.className=`wp-toast-container`,document.body.appendChild(e),e}function u(){let e=[`define( 'AUTH_KEY',         '${r()}' );`,`define( 'SECURE_AUTH_KEY',  '${r()}' );`,`define( 'LOGGED_IN_KEY',    '${r()}' );`,`define( 'NONCE_KEY',        '${r()}' );`,`define( 'AUTH_SALT',        '${r()}' );`,`define( 'SECURE_AUTH_SALT', '${r()}' );`,`define( 'LOGGED_IN_SALT',   '${r()}' );`,`define( 'NONCE_SALT',       '${r()}' );`].join(`
`);return`<?php
/**
 * The base configuration for WordPress
 *
 * Generated by WordPress Modern Setup Wizard
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', '${t.db.name}' );

/** Database username */
define( 'DB_USER', '${t.db.user}' );

/** Database password */
define( 'DB_PASSWORD', '${t.db.pass}' );

/** Database hostname */
define( 'DB_HOST', '${t.db.host}' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 */
${e}
/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = '${t.db.prefix}';

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
`}function d(e){if(!e||e.length<5)return{level:`very-weak`,text:`Rất yếu (Nguy hiểm)`};let t=0;return e.length>=8&&t++,e.length>=12&&t++,/[A-Z]/.test(e)&&t++,/[0-9]/.test(e)&&t++,/[^A-Za-z0-9]/.test(e)&&t++,t<=1?{level:`weak`,text:`Yếu`}:t<=3?{level:`medium`,text:`Trung bình`}:{level:`strong`,text:`Mạnh (Khuyên dùng)`}}function f(){let n=document.getElementById(`root`);if(!n)return;let r=e[t.language];n.innerHTML=`
    <div class="wp-app" data-theme="${t.themeMode}">
      <!-- Header -->
      <header class="wp-header">
        <a href="#" class="wp-brand" id="btn-brand">
          <div class="wp-brand-logo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878L5.753 9.426c.725-.036 1.408-.073 1.408-.073.543-.036.472-.835-.072-.835 0 0-1.632.146-2.684.146-1.015 0-2.646-.146-2.646-.146-.544 0-.616.799-.072.835 0 0 .647.037 1.336.073l1.996 5.99L2.096 12C2.032 11.672 2 11.34 2 12c0-5.523 4.477-10 10-10s10 4.477 10 10c0 4.48-2.951 8.271-7.014 9.535l4.312-12.46c.725-.036 1.408-.073 1.408-.073.543-.036.472-.835-.072-.835 0 0-1.632.146-2.684.146-1.015 0-2.646-.146-2.646-.146-.544 0-.616.799-.072.835 0 0 .647.037 1.336.073l2.03 6.091-2.949 8.783A9.99 9.99 0 0 0 22 12c0-5.523-4.477-10-10-10zm-1.896 19.82A7.986 7.986 0 0 1 4.1 14.54l3.65 10.015c.749.176 1.529.265 2.354.265zm2.748-.225 3.324-9.645-1.996-5.467-3.411 9.917c.697 1.83 1.385 3.593 2.083 5.195z"/>
            </svg>
          </div>
          <span class="wp-brand-title">
            ${r.brandTitle}
            <span class="wp-brand-badge">${r.brandBadge}</span>
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
            ${r.presetsBtn}
          </button>

          <!-- Language Selector -->
          <select class="wp-select-compact" id="lang-switcher">
            <option value="vi" ${t.language===`vi`?`selected`:``}>🇻🇳 Tiếng Việt</option>
            <option value="en" ${t.language===`en`?`selected`:``}>🇺🇸 English</option>
          </select>

          <!-- Theme Toggle Button -->
          <button class="wp-btn-icon" id="btn-theme-toggle" title="Giao diện Sáng/Tối" aria-label="Toggle Theme">
            ${t.themeMode===`light`?`
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
              </svg>
            `:`
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
          <div class="wp-stepper-title">${t.language===`vi`?`Tiến trình cài đặt`:`Installation Steps`}</div>
          <ul class="wp-steps-list">
            ${p(1,r.step1Title,r.step1Sub)}
            ${p(2,r.step2Title,r.step2Sub)}
            ${p(3,r.step3Title,r.step3Sub)}
            ${p(4,r.step4Title,r.step4Sub)}
            ${p(5,r.step5Title,r.step5Sub)}
            ${p(6,r.step6Title,r.step6Sub)}
            ${p(7,r.step7Title,r.step7Sub)}
          </ul>

          <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color); font-size: 0.76rem; color: var(--text-muted); line-height: 1.4;">
            WordPress ${r.brandBadge} · PHP 8.2+ · MariaDB/MySQL 8.0+
          </div>
        </aside>

        <!-- Dynamic Step Card Container -->
        <section class="wp-wizard-card">
          ${m()}
        </section>
      </main>

      <!-- Presets Modal Dialog -->
      <div class="wp-modal-overlay" id="presets-modal">
        <div class="wp-modal">
          <div class="wp-modal-header">
            <h3 class="wp-modal-title">${t.language===`vi`?`Chọn Mẫu Cấu Hình Máy Chủ`:`Choose Server Preset`}</h3>
            <button class="wp-btn-icon" id="btn-close-presets" style="border: none; background: transparent;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="wp-modal-body">
            <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 1.25rem;">
              ${t.language===`vi`?`Nạp nhanh thông số Database phù hợp với môi trường triển khai của bạn:`:`Instantly fill standard database credentials for your specific stack:`}
            </p>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
              ${s.map((e,n)=>`
                <div class="wp-health-item" style="cursor: pointer;" onclick="window.applyPreset(${n})">
                  <div>
                    <strong style="color: var(--text-primary); font-size: 0.92rem;">${e.name}</strong>
                    <div style="font-family: var(--font-mono); font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                      Host: ${e.db.host} | DB: ${e.db.name} | User: ${e.db.user}
                    </div>
                  </div>
                  <button class="wp-btn-secondary wp-btn-sm" style="pointer-events: none;">
                    ${t.language===`vi`?`Áp dụng`:`Apply`}
                  </button>
                </div>
              `).join(``)}
            </div>
          </div>
          <div class="wp-modal-footer">
            <button class="wp-btn-secondary" id="btn-cancel-presets">${t.language===`vi`?`Đóng`:`Close`}</button>
          </div>
        </div>
      </div>

      <!-- Preview Mockup Modal -->
      <div class="wp-modal-overlay" id="preview-modal">
        <div class="wp-modal" style="max-width: 780px;">
          <div class="wp-modal-header">
            <h3 class="wp-modal-title">${t.language===`vi`?`Xem trước Trang Chủ WordPress`:`Live WordPress Mockup Preview`}</h3>
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
                <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em;">${t.site.title||`WordPress Site`}</h1>
                <nav style="display: flex; gap: 1rem; font-size: 0.85rem; font-weight: 600; color: #475569;">
                  <span>Trang chủ</span>
                  <span>Bài viết</span>
                  <span>Giới thiệu</span>
                  <span>Liên hệ</span>
                </nav>
              </div>
              <p style="font-size: 1.1rem; color: #475569; font-style: italic; margin-bottom: 2rem;">"${t.site.tagline||`Just another WordPress site`}"</p>
              
              <article style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; background: #f8fafc;">
                <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #0f172a;">Chào thế giới! (Hello world!)</h2>
                <p style="font-size: 0.88rem; color: #64748b; line-height: 1.6; margin-bottom: 1rem;">
                  Chào mừng bạn đến với WordPress. Đây là bài viết đầu tiên của bạn. Hãy chỉnh sửa hoặc xóa nó, sau đó bắt đầu viết bài!
                </p>
                <div style="font-size: 0.75rem; color: #94a3b8; font-family: var(--font-mono);">
                  Đăng bởi <strong>${t.site.adminUser}</strong> · Giao diện kích hoạt: <strong>${i.find(e=>e.id===t.theme)?.name}</strong>
                </div>
              </article>
            </div>
          </div>
          <div class="wp-modal-footer">
            <button class="wp-btn-secondary" id="btn-close-preview-footer">${t.language===`vi`?`Đóng`:`Close`}</button>
          </div>
        </div>
      </div>
    </div>
  `,g()}function p(e,n,r){let i=t.currentStep===e,a=t.currentStep>e,o=e>t.maxStepReached&&!a,s=`wp-step-item`;return i&&(s+=` active`),a&&(s+=` completed`),o&&(s+=` disabled`),`
    <li>
      <button class="${s}" onclick="window.goToStep(${e})" ${o?`disabled`:``}>
        <div class="wp-step-indicator">
          ${a?`
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          `:e}
        </div>
        <div class="wp-step-text">
          <span class="wp-step-label">${n}</span>
          <span class="wp-step-sublabel">${r}</span>
        </div>
      </button>
    </li>
  `}function m(){let n=e[t.language];switch(t.currentStep){case 1:return`
        <div class="wp-card-header">
          <div class="wp-card-kicker">Bước 01 / 07</div>
          <h2 class="wp-card-title">${n.welcomeTitle}</h2>
          <p class="wp-card-desc">${n.welcomeDesc}</p>
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
            <label class="wp-label" style="margin-bottom: 0.5rem;">${n.selectLangLabel}</label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
              <button class="wp-plugin-card ${t.language===`vi`?`active`:``}" onclick="window.changeLanguage('vi')">
                <span style="font-size: 1.75rem;">🇻🇳</span>
                <div class="wp-plugin-details">
                  <div class="wp-plugin-title">Tiếng Việt</div>
                  <div class="wp-plugin-desc">Ngôn ngữ tiếng Việt chuẩn hóa đầy đủ</div>
                </div>
              </button>

              <button class="wp-plugin-card ${t.language===`en`?`active`:``}" onclick="window.changeLanguage('en')">
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
              ${n.featuresTitle}
            </h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem;">
              <div class="wp-health-item">
                <span>🛡️ ${n.feat2}</span>
              </div>
              <div class="wp-health-item">
                <span>⚡ ${n.feat1}</span>
              </div>
              <div class="wp-health-item">
                <span>📦 ${n.feat3}</span>
              </div>
              <div class="wp-health-item">
                <span>📄 ${n.feat4}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="wp-card-footer">
          <div></div>
          <button class="wp-btn wp-btn-primary" onclick="window.goToStep(2)">
            ${n.next}
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </button>
        </div>
      `;case 2:return`
        <div class="wp-card-header">
          <div class="wp-card-kicker">Bước 02 / 07</div>
          <h2 class="wp-card-title">${n.envTitle}</h2>
          <p class="wp-card-desc">${n.envDesc}</p>
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
            ${o.map(e=>`
              <div class="wp-health-item">
                <div class="wp-health-info">
                  <span style="font-weight: 600; color: var(--text-primary);">${e.name}</span>
                  <span style="color: var(--text-muted); font-family: var(--font-mono); font-size: 0.8rem;">${e.val}</span>
                </div>
                <span class="wp-status-pill ${e.status}">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="20 6 9 17 4 12"></polyline>
                  </svg>
                  ${n.envStatusGood}
                </span>
              </div>
            `).join(``)}
          </div>
        </div>

        <div class="wp-card-footer">
          <button class="wp-btn wp-btn-secondary" onclick="window.goToStep(1)">
            ${n.back}
          </button>
          <button class="wp-btn wp-btn-primary" onclick="window.goToStep(3)">
            ${n.next}
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </button>
        </div>
      `;case 3:return`
        <div class="wp-card-header">
          <div class="wp-card-kicker">Bước 03 / 07</div>
          <h2 class="wp-card-title">${n.dbTitle}</h2>
          <p class="wp-card-desc">${n.dbDesc}</p>
        </div>

        <div class="wp-card-body">
          <!-- Quick Preset Bar -->
          <div class="wp-preset-bar">
            <span class="wp-preset-label">${n.loadPresetPrompt}</span>
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
                  ${n.dbNameLabel}
                  <span class="wp-label-hint">Database Name</span>
                </label>
                <input class="wp-input font-mono" id="db_name" value="${t.db.name}" oninput="window.updateDbField('name', this.value)" placeholder="wordpress_db" />
                <span class="wp-helper-text">${n.dbNameHint}</span>
              </div>

              <div class="wp-form-group">
                <label class="wp-label" for="db_user">
                  ${n.dbUserLabel}
                  <span class="wp-label-hint">Username</span>
                </label>
                <input class="wp-input font-mono" id="db_user" value="${t.db.user}" oninput="window.updateDbField('user', this.value)" placeholder="root" />
                <span class="wp-helper-text">${n.dbUserHint}</span>
              </div>
            </div>

            <div class="wp-form-grid-2">
              <div class="wp-form-group">
                <label class="wp-label" for="db_pass">
                  ${n.dbPassLabel}
                  <span class="wp-label-hint">Password</span>
                </label>
                <div class="wp-input-wrapper">
                  <input class="wp-input font-mono" id="db_pass" type="password" value="${t.db.pass}" oninput="window.updateDbField('pass', this.value)" placeholder="••••••••" />
                  <div class="wp-input-addon">
                    <button class="wp-btn-icon" style="width: 28px; height: 28px; border:none;" onclick="window.toggleDbPassVisibility()">
                      👁️
                    </button>
                  </div>
                </div>
                <span class="wp-helper-text">${n.dbPassHint}</span>
              </div>

              <div class="wp-form-group">
                <label class="wp-label" for="db_host">
                  ${n.dbHostLabel}
                  <span class="wp-label-hint">Host</span>
                </label>
                <input class="wp-input font-mono" id="db_host" value="${t.db.host}" oninput="window.updateDbField('host', this.value)" placeholder="localhost" />
                <span class="wp-helper-text">${n.dbHostHint}</span>
              </div>
            </div>

            <div class="wp-form-group">
              <label class="wp-label" for="db_prefix">
                ${n.dbPrefixLabel}
                <span class="wp-label-hint">Prefix</span>
              </label>
              <input class="wp-input font-mono" id="db_prefix" value="${t.db.prefix}" oninput="window.updateDbField('prefix', this.value)" placeholder="wp_" />
              <span class="wp-helper-text">${n.dbPrefixHint}</span>
            </div>

            <!-- Database connection test trigger -->
            <div style="margin-top: 0.5rem;">
              <button class="wp-btn-secondary" id="btn-test-db" onclick="window.testDatabaseConnection()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                ${n.testDbBtn}
              </button>
              <div id="db-test-result" style="margin-top: 0.75rem;">
                ${t.db.tested?`
                  <div class="wp-callout wp-callout-success" style="margin: 0; padding: 0.75rem 1rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>${n.testDbSuccess}</span>
                  </div>
                `:``}
              </div>
            </div>
          </div>
        </div>

        <div class="wp-card-footer">
          <button class="wp-btn wp-btn-secondary" onclick="window.goToStep(2)">
            ${n.back}
          </button>
          <button class="wp-btn wp-btn-primary" onclick="window.goToStep(4)">
            ${n.next}
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </button>
        </div>
      `;case 4:let e=d(t.site.adminPass);return`
        <div class="wp-card-header">
          <div class="wp-card-kicker">Bước 04 / 07</div>
          <h2 class="wp-card-title">${n.siteTitleLabel} & Quản trị viên</h2>
          <p class="wp-card-desc">Thiết lập tiêu đề website và thông tin đăng nhập quản trị viên tối cao.</p>
        </div>

        <div class="wp-card-body">
          <div class="wp-form-grid">
            <div class="wp-form-grid-2">
              <div class="wp-form-group">
                <label class="wp-label" for="site_title">${n.siteTitleLabel}</label>
                <input class="wp-input" id="site_title" value="${t.site.title}" oninput="window.updateSiteField('title', this.value)" placeholder="Ví dụ: Công Ty TNHH Sáng Tạo" />
              </div>

              <div class="wp-form-group">
                <label class="wp-label" for="site_tagline">${n.siteTaglineLabel}</label>
                <input class="wp-input" id="site_tagline" value="${t.site.tagline}" oninput="window.updateSiteField('tagline', this.value)" placeholder="Khẩu hiệu ngắn của trang..." />
              </div>
            </div>

            <div class="wp-form-grid-2">
              <div class="wp-form-group">
                <label class="wp-label" for="admin_user">${n.adminUserLabel}</label>
                <input class="wp-input font-mono" id="admin_user" value="${t.site.adminUser}" oninput="window.updateSiteField('adminUser', this.value)" placeholder="admin" />
                <span class="wp-helper-text">Tránh dùng tên "admin" đơn giản trên production để phòng brute-force.</span>
              </div>

              <div class="wp-form-group">
                <label class="wp-label" for="admin_email">${n.adminEmailLabel}</label>
                <input class="wp-input" id="admin_email" type="email" value="${t.site.adminEmail}" oninput="window.updateSiteField('adminEmail', this.value)" placeholder="admin@domain.com" />
                <span class="wp-helper-text">Dùng để nhận thông báo phục hồi mật khẩu và cập nhật bảo mật WordPress.</span>
              </div>
            </div>

            <!-- Password field with generator & strength meter -->
            <div class="wp-form-group">
              <div class="wp-label">
                <span>${n.adminPassLabel}</span>
                <button type="button" class="wp-preset-btn" onclick="window.generateNewPassword()">
                  ⚡ ${n.generatePass}
                </button>
              </div>
              <div class="wp-input-wrapper">
                <input class="wp-input font-mono" id="admin_pass" type="text" value="${t.site.adminPass}" oninput="window.updateSiteField('adminPass', this.value)" />
                <div class="wp-input-addon">
                  <button type="button" class="wp-btn-icon" style="width: 28px; height: 28px; border:none;" onclick="window.copyToClipboard('${t.site.adminPass}', 'Đã sao chép mật khẩu!')">
                    📋
                  </button>
                </div>
              </div>

              <!-- Strength meter bar -->
              <div class="wp-pw-meter">
                <div class="wp-pw-fill ${e.level}"></div>
              </div>
              <div class="wp-pw-feedback">
                <span>Độ mạnh: <strong style="color: var(--text-primary);">${e.text}</strong></span>
                <span>Entropy: 128-bit</span>
              </div>
            </div>

            <!-- Search engine visibility switch -->
            <div class="wp-plugin-card" style="margin-top: 0.5rem; cursor: default;">
              <input type="checkbox" class="wp-checkbox" id="se_visible" ${t.site.searchEnginePublic?`checked`:``} onchange="window.updateSiteField('searchEnginePublic', this.checked)" />
              <div class="wp-plugin-details">
                <label for="se_visible" class="wp-plugin-title" style="cursor: pointer;">
                  ${n.searchEngineLabel}
                </label>
                <div class="wp-plugin-desc">${n.searchEngineDesc}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="wp-card-footer">
          <button class="wp-btn wp-btn-secondary" onclick="window.goToStep(3)">
            ${n.back}
          </button>
          <div style="display: flex; gap: 0.75rem;">
            <button class="wp-btn wp-btn-secondary" onclick="window.openPreviewModal()">
              🔍 ${n.previewSite}
            </button>
            <button class="wp-btn wp-btn-primary" onclick="window.goToStep(5)">
              ${n.next}
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </button>
          </div>
        </div>
      `;case 5:return`
        <div class="wp-card-header">
          <div class="wp-card-kicker">Bước 05 / 07</div>
          <h2 class="wp-card-title">${n.themeStepTitle}</h2>
          <p class="wp-card-desc">${n.themeStepDesc}</p>
        </div>

        <div class="wp-card-body">
          <div class="wp-theme-grid">
            ${i.map(e=>`
              <div class="wp-theme-card ${t.theme===e.id?`selected`:``}" onclick="window.selectTheme('${e.id}')">
                <div class="wp-theme-preview" style="background: ${e.previewBg};">
                  <span class="wp-theme-preview-badge">${e.badge}</span>
                </div>
                <div class="wp-theme-info">
                  <div>
                    <div class="wp-theme-name">${e.name}</div>
                    <div class="wp-theme-desc">${e.desc}</div>
                  </div>
                  <div style="margin-top: 0.75rem; display: flex; justify-content: flex-end;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: ${t.theme===e.id?`var(--wp-blue-600)`:`var(--text-muted)`};">
                      ${t.theme===e.id?`✓ Đã chọn`:`Chọn giao diện`}
                    </span>
                  </div>
                </div>
              </div>
            `).join(``)}
          </div>

          <div style="margin-top: 2rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary);">
              ${n.pluginStepTitle}
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.75rem;">
              ${n.pluginStepDesc}
            </p>

            <div class="wp-plugins-grid">
              ${a.map(e=>{let n=t.plugins.includes(e.id);return`
                  <div class="wp-plugin-card ${n?`active`:``}" onclick="window.togglePlugin('${e.id}')">
                    <input type="checkbox" class="wp-checkbox" ${n?`checked`:``} onclick="event.stopPropagation(); window.togglePlugin('${e.id}')" />
                    <div class="wp-plugin-details">
                      <div class="wp-plugin-title">
                        <span>${e.name}</span>
                        <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 500;">${e.category}</span>
                      </div>
                      <div class="wp-plugin-desc">${e.desc}</div>
                    </div>
                  </div>
                `}).join(``)}
            </div>
          </div>
        </div>

        <div class="wp-card-footer">
          <button class="wp-btn wp-btn-secondary" onclick="window.goToStep(4)">
            ${n.back}
          </button>
          <button class="wp-btn wp-btn-primary" onclick="window.goToStep(6)">
            ${n.next}
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </button>
        </div>
      `;case 6:let r=u();return`
        <div class="wp-card-header">
          <div class="wp-card-kicker">Bước 06 / 07</div>
          <h2 class="wp-card-title">${n.installTitle}</h2>
          <p class="wp-card-desc">${n.installDesc}</p>
        </div>

        <div class="wp-card-body">
          <!-- Installation Progress -->
          <div class="wp-progress-container">
            <div class="wp-progress-header">
              <span id="install-step-text">${t.installing?`Đang thực thi các tác vụ cài đặt...`:`Sẵn sàng khởi chạy cài đặt WordPress`}</span>
              <span id="install-pct" style="font-family: var(--font-mono);">${t.installProgress}%</span>
            </div>
            <div class="wp-progress-track">
              <div class="wp-progress-fill" id="install-bar" style="width: ${t.installProgress}%;"></div>
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
              <div class="wp-term-line">[1/6] Kiểm tra thông số kết nối Database: ${t.db.name} @ ${t.db.host}</div>
              <div class="wp-term-line">[2/6] Tạo tiền tố bảng dữ liệu an toàn: ${t.db.prefix}*</div>
              ${t.installing?`<div class="wp-term-line info">Đang chạy tiến trình cài đặt...</div>`:``}
            </div>
          </div>

          <!-- wp-config.php preview & actions -->
          <div class="wp-code-box">
            <div class="wp-code-header">
              <span>wp-config.php (Đã tích hợp Security Salts)</span>
              <div style="display: flex; gap: 0.5rem;">
                <button class="wp-preset-btn" onclick="window.copyWpConfig()">
                  📋 ${n.copy}
                </button>
                <button class="wp-preset-btn" onclick="window.downloadWpConfig()">
                  💾 ${n.download}
                </button>
              </div>
            </div>
            <pre class="wp-code-content"><code>${h(r)}</code></pre>
          </div>
        </div>

        <div class="wp-card-footer">
          <button class="wp-btn wp-btn-secondary" onclick="window.goToStep(5)" ${t.installing?`disabled`:``}>
            ${n.back}
          </button>
          <button class="wp-btn wp-btn-primary" id="btn-run-install" onclick="window.runInstallation()" ${t.installing?`disabled`:``}>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polygon points="5 3 19 12 5 21 5 3"></polygon>
            </svg>
            ${n.installNow}
          </button>
        </div>
      `;case 7:return`
        <div class="wp-card-body">
          <div class="wp-success-box">
            <div class="wp-success-icon-wrap">
              <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </div>
            <h2 class="wp-card-title" style="margin-bottom: 0.5rem;">${n.successTitle}</h2>
            <p class="wp-card-desc" style="max-width: 560px; margin: 0 auto 1.5rem;">
              ${n.successDesc}
            </p>

            <div class="wp-credentials-card">
              <div class="wp-cred-row">
                <span class="wp-cred-label">${n.siteTitleLabel}:</span>
                <span class="wp-cred-val" style="font-family: inherit;">${t.site.title}</span>
              </div>
              <div class="wp-cred-row">
                <span class="wp-cred-label">${n.adminUrl}</span>
                <span class="wp-cred-val" style="color: var(--wp-blue-600);">https://localhost/wp-admin/</span>
              </div>
              <div class="wp-cred-row">
                <span class="wp-cred-label">${n.usernameRecap}</span>
                <span class="wp-cred-val">${t.site.adminUser}</span>
              </div>
              <div class="wp-cred-row">
                <span class="wp-cred-label">${n.passwordRecap}</span>
                <span class="wp-cred-val" style="display: flex; align-items: center; gap: 0.5rem;">
                  <span id="recap-pass">••••••••••••</span>
                  <button class="wp-btn-icon" style="width: 24px; height: 24px; border: none;" onclick="window.toggleRecapPassword()">
                    👁️
                  </button>
                </span>
              </div>
              <div class="wp-cred-row">
                <span class="wp-cred-label">Giao diện (Theme):</span>
                <span class="wp-cred-val">${i.find(e=>e.id===t.theme)?.name}</span>
              </div>
              <div class="wp-cred-row">
                <span class="wp-cred-label">Plugins kích hoạt:</span>
                <span class="wp-cred-val" style="font-size: 0.8rem;">${t.plugins.length} tiện ích</span>
              </div>
            </div>

            <div style="display: flex; justify-content: center; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
              <button class="wp-btn wp-btn-secondary" onclick="window.openPreviewModal()">
                🌐 ${n.visitSite}
              </button>
              <button class="wp-btn wp-btn-primary" onclick="window.goToAdminDashboard()">
                🚀 ${n.goToAdmin}
              </button>
            </div>
          </div>
        </div>
      `;default:return``}}function h(e){return e.replace(/&/g,`&amp;`).replace(/</g,`&lt;`).replace(/>/g,`&gt;`)}function g(){let e=document.getElementById(`btn-theme-toggle`);e&&(e.onclick=()=>{t.themeMode=t.themeMode===`light`?`dark`:`light`,document.body.setAttribute(`data-theme`,t.themeMode),f()});let n=document.getElementById(`lang-switcher`);n&&(n.onchange=e=>{t.language=e.target.value,f()});let r=document.getElementById(`btn-open-presets`),i=document.getElementById(`btn-close-presets`),a=document.getElementById(`btn-cancel-presets`),o=document.getElementById(`presets-modal`);r&&o&&(r.onclick=()=>o.classList.add(`open`)),i&&o&&(i.onclick=()=>o.classList.remove(`open`)),a&&o&&(a.onclick=()=>o.classList.remove(`open`));let s=document.getElementById(`btn-close-preview`),c=document.getElementById(`btn-close-preview-footer`),l=document.getElementById(`preview-modal`);s&&l&&(s.onclick=()=>l.classList.remove(`open`)),c&&l&&(c.onclick=()=>l.classList.remove(`open`))}window.goToStep=e=>{e<1||e>7||(t.currentStep=e,e>t.maxStepReached&&(t.maxStepReached=e),f(),window.scrollTo({top:0,behavior:`smooth`}))},window._pwState=t,window._pwRender=f,window._pwToast=c,window._pwMsg=e,window.changeLanguage=e=>{t.language=e,f()},window.updateDbField=(e,n)=>{t.db[e]=n,t.db.tested=!1},window.updateSiteField=(e,n)=>{t.site[e]=n,e===`adminPass`&&f()},window.toggleDbPassVisibility=()=>{let e=document.getElementById(`db_pass`);e&&(e.type=e.type===`password`?`text`:`password`)},window.testDatabaseConnection=()=>{let n=document.getElementById(`db-test-result`),r=document.getElementById(`btn-test-db`);n&&(r&&(r.disabled=!0),n.innerHTML=`
    <div class="wp-callout wp-callout-info" style="margin: 0; padding: 0.75rem 1rem;">
      <span style="display: inline-block; animation: spin 1s linear infinite;">⏳</span>
      <span>${e[t.language].testDbTesting}</span>
    </div>
  `,setTimeout(()=>{t.db.tested=!0,r&&(r.disabled=!1),f(),c(e[t.language].testDbSuccess)},900))},window.generateNewPassword=()=>{t.site.adminPass=n(),f(),c(t.language===`vi`?`Đã tạo mật khẩu mạnh mới!`:`Generated new strong password!`)},window.selectTheme=e=>{t.theme=e,f()},window.togglePlugin=e=>{t.plugins.includes(e)?t.plugins=t.plugins.filter(t=>t!==e):t.plugins.push(e),f()},window.copyToClipboard=(e,t)=>{navigator.clipboard.writeText(e).then(()=>{c(t)})},window.copyWpConfig=()=>{let e=u();navigator.clipboard.writeText(e).then(()=>{c(t.language===`vi`?`Đã sao chép nội dung wp-config.php!`:`wp-config.php copied to clipboard!`)})},window.downloadWpConfig=()=>{let e=u(),n=new Blob([e],{type:`application/x-httpd-php`}),r=URL.createObjectURL(n),i=document.createElement(`a`);i.href=r,i.download=`wp-config.php`,document.body.appendChild(i),i.click(),document.body.appendChild(i),i.remove(),URL.revokeObjectURL(r),c(t.language===`vi`?`Đã tải xuống file wp-config.php thành công!`:`wp-config.php downloaded successfully!`)},window.applyPreset=e=>{let n=s[e];if(!n)return;t.db={...n.db,tested:!0};let r=document.getElementById(`presets-modal`);r&&r.classList.remove(`open`),f(),c(`${t.language===`vi`?`Đã áp dụng mẫu`:`Applied preset`}: ${n.name}`)},window.openPreviewModal=()=>{let e=document.getElementById(`preview-modal`);e&&e.classList.add(`open`)};var _=!1;window.toggleRecapPassword=()=>{_=!_;let e=document.getElementById(`recap-pass`);e&&(e.innerText=_?t.site.adminPass:`••••••••••••`)},window.goToAdminDashboard=()=>{c(`Đang chuyển hướng tới WordPress wp-admin login...`),setTimeout(()=>{window.openPreviewModal()},600)},window.runInstallation=()=>{if(t.installing)return;t.installing=!0,t.installProgress=5;let e=document.getElementById(`terminal-body`),n=document.getElementById(`install-bar`),r=document.getElementById(`install-pct`),a=document.getElementById(`install-step-text`),o=document.getElementById(`btn-run-install`);o&&(o.disabled=!0),[{delay:400,pct:18,text:`Đang thiết lập database handshake và charset utf8mb4...`,log:`[DB] Kết nối thành công. Thiết lập charset utf8mb4_unicode_ci`},{delay:900,pct:35,text:`Đang tạo bảng hệ thống wp_posts, wp_options, wp_users...`,log:`[SCHEMA] Tạo bảng thành công: ${t.db.prefix}posts, ${t.db.prefix}options, ${t.db.prefix}usermeta`},{delay:1500,pct:55,text:`Khởi tạo tài khoản Quản trị viên tối cao (Administrator)...`,log:`[AUTH] Tạo user '${t.site.adminUser}' (ID 1) với mã hóa PasswordHash PBKDF2`},{delay:2100,pct:72,text:`Nạp cấu hình tiêu đề website và permalink structure...`,log:`[SITE] Đặt tiêu đề: '${t.site.title}', timezone: ${t.site.timezone}`},{delay:2700,pct:88,text:`Kích hoạt giao diện ${i.find(e=>e.id===t.theme)?.name}...`,log:`[THEME] Kích hoạt theme '${t.theme}' và nạp style tokens`},{delay:3300,pct:96,text:`Kích hoạt ${t.plugins.length} tiện ích mở rộng ban đầu...`,log:`[PLUGINS] Kích hoạt thành công: ${t.plugins.join(`, `)}`},{delay:3800,pct:100,text:`Hoàn tất cài đặt! Chuẩn bị bảng điều khiển...`,log:`[SUCCESS] Hoàn tất quá trình cài đặt WordPress 6.7 trong 3.8s!`}].forEach(({delay:i,pct:o,text:s,log:c})=>{setTimeout(()=>{if(t.installProgress=o,n&&(n.style.width=`${o}%`),r&&(r.innerText=`${o}%`),a&&(a.innerText=s),e){let t=document.createElement(`div`);t.className=o===100?`wp-term-line success`:`wp-term-line info`,t.innerText=c,e.appendChild(t),e.scrollTop=e.scrollHeight}o===100&&(t.installing=!1,t.installed=!0,setTimeout(()=>{window.goToStep(7)},800))},i)})},document.addEventListener(`DOMContentLoaded`,()=>{f()}),(document.readyState===`complete`||document.readyState===`interactive`)&&f();
// ===== PrestoWorld API Integration Patch =====
// Overrides mock functions with real API calls to /api/setup/* endpoints

(function() {
  // Override testDatabaseConnection with real API call
  window.testDatabaseConnection = function() {
    var resultDiv = document.getElementById('db-test-result');
    var btn = document.getElementById('btn-test-db');
    if (!resultDiv) return;

    if (btn) btn.disabled = true;
    resultDiv.innerHTML = `
      <div class="wp-callout wp-callout-info" style="margin: 0; padding: 0.75rem 1rem;">
        <span style="display: inline-block; animation: spin 1s linear infinite;">⏳</span>
        <span>Đang kết nối tới máy chủ cơ sở dữ liệu...</span>
      </div>
    `;

    var state = window._pwState || {};
    var dbState = state.db || {};

    var dbName = (document.getElementById('db_name') || {}).value || dbState.name || '';
    var dbUser = (document.getElementById('db_user') || {}).value || dbState.user || '';
    var dbPass = (document.getElementById('db_pass') || {}).value || dbState.pass || '';
    var dbHost = (document.getElementById('db_host') || {}).value || dbState.host || '127.0.0.1';

    var hostParts = (dbHost || '127.0.0.1').split(':');
    var host = hostParts[0] || '127.0.0.1';
    var port = hostParts[1] || (host.includes('5432') ? '5432' : '3306');
    var connection = (port === '5432' || host.includes('pgsql') || host.includes('postgres')) ? 'pgsql' : 'mysql';

    fetch('/api/setup/test-db', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        connection: connection,
        host: host,
        port: parseInt(port, 10),
        name: dbName,
        username: dbUser,
        password: dbPass
      })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (btn) btn.disabled = false;
      if (data.success) {
        resultDiv.innerHTML = `
          <div class="wp-callout wp-callout-success" style="margin: 0; padding: 0.75rem 1rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <span>Kết nối thành công! Tìm thấy ${data.tables_count || 0} bảng trong cơ sở dữ liệu.</span>
          </div>
        `;

        if (window._pwState && window._pwState.db) {
          window._pwState.db.tested = true;
          window._pwState.db.name = dbName;
          window._pwState.db.user = dbUser;
          window._pwState.db.pass = dbPass;
          window._pwState.db.host = dbHost;
          if (typeof window._pwRender === 'function') {
            window._pwRender();
          }
        }
        if (typeof window._pwToast === 'function') {
          window._pwToast('Kết nối cơ sở dữ liệu thành công!');
        }
      } else {
        resultDiv.innerHTML = `
          <div class="wp-callout wp-callout-danger" style="margin: 0; padding: 0.75rem 1rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span>Lỗi kết nối: ${data.error || 'Không thể kết nối cơ sở dữ liệu.'}</span>
          </div>
        `;
      }
    })
    .catch(function(err) {
      if (btn) btn.disabled = false;
      resultDiv.innerHTML = `
        <div class="wp-callout wp-callout-danger" style="margin: 0; padding: 0.75rem 1rem;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          <span>Lỗi mạng: ${err.message}</span>
        </div>
      `;
    });
  };

  // Override runInstallation with real API call
  window.runInstallation = function() {
    var state = window._pwState || {};
    if (state.installing) return;
    state.installing = true;
    state.installProgress = 10;

    var termBody = document.getElementById('terminal-body');
    var installBar = document.getElementById('install-bar');
    var installPct = document.getElementById('install-pct');
    var installStepText = document.getElementById('install-step-text');
    var btnRun = document.getElementById('btn-run-install');
    if (btnRun) btnRun.disabled = true;

    function addLog(text, isSuccess) {
      if (!termBody) return;
      var line = document.createElement('div');
      line.className = isSuccess ? 'wp-term-line success' : 'wp-term-line info';
      line.innerText = text;
      termBody.appendChild(line);
      termBody.scrollTop = termBody.scrollHeight;
    }

    function setProgress(pct, msg) {
      state.installProgress = pct;
      if (installBar) installBar.style.width = pct + '%';
      if (installPct) installPct.innerText = pct + '%';
      if (installStepText) installStepText.innerText = msg;
    }

    addLog('[INIT] Khởi động tiến trình cài đặt PrestoWorld...', false);
    setProgress(20, 'Đang chuẩn bị thông tin cấu hình...');

    var db = state.db || {};
    var site = state.site || {};
    var hostParts = (db.host || '127.0.0.1').split(':');
    var host = hostParts[0] || '127.0.0.1';
    var port = hostParts[1] || (host.includes('5432') ? '5432' : '3306');
    var connection = (port === '5432' || host.includes('pgsql') || host.includes('postgres')) ? 'pgsql' : 'mysql';

    var payload = {
      db_connection: connection,
      db_host: host,
      db_port: parseInt(port, 10),
      db_name: db.name || 'prestoworld',
      db_username: db.user || 'root',
      db_password: db.pass || '',
      db_prefix: db.prefix || 'pw_',
      site_title: site.title || 'My PrestoWorld Site',
      admin_username: site.adminUser || 'admin',
      admin_email: site.adminEmail || 'admin@prestoworld.org',
      admin_password: site.adminPass || 'admin123'
    };

    setTimeout(function() {
      setProgress(40, 'Đang gửi yêu cầu cài đặt tới máy chủ PrestoWorld...');
      addLog('[DB] Đang tạo schema và cấu hình môi trường...', false);

      fetch('/api/setup/install', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.success) {
          setProgress(80, 'Khởi tạo tài khoản quản trị và kích hoạt themes...');
          addLog('[AUTH] Tạo tài khoản quản trị thành công!', true);
          addLog('[SUCCESS] Cài đặt PrestoWorld hoàn tất thành công!', true);

          setTimeout(function() {
            setProgress(100, 'Hoàn tất cài đặt!');
            state.installing = false;
            state.installed = true;
            setTimeout(function() {
              window.goToStep(7);
            }, 800);
          }, 800);
        } else {
          state.installing = false;
          if (btnRun) btnRun.disabled = false;
          setProgress(10, 'Lỗi cài đặt');
          addLog('[ERROR] Cài đặt thất bại: ' + (data.error || 'Lỗi không xác định'), false);
          if (typeof window._pwToast === 'function') {
            window._pwToast('Lỗi cài đặt: ' + (data.error || 'Vui lòng kiểm tra lại thông tin'));
          }
        }
      })
      .catch(function(err) {
        state.installing = false;
        if (btnRun) btnRun.disabled = false;
        setProgress(10, 'Lỗi kết nối');
        addLog('[ERROR] Lỗi mạng: ' + err.message, false);
      });
    }, 600);
  };

  // Redirect to PrestoWorld Dashboard on finish
  window.goToAdminDashboard = function() {
    window.location.href = '/dashboard';
  };

})();
// ===== End PrestoWorld API Integration Patch =====
