// public/installer/wizard-ref/src/main.ts
var i18n = {
  vi: {
    brandTitle: "PrestoWorld Setup",
    brandBadge: "v6.7 Pro",
    themeLight: "S\xE1ng",
    themeDark: "T\u1ED1i",
    helpBtn: "Tr\u1EE3 gi\xFAp",
    presetsBtn: "M\u1EABu c\u1EA5u h\xECnh",
    next: "Ti\u1EBFp t\u1EE5c",
    back: "Quay l\u1EA1i",
    installNow: "B\u1EAFt \u0111\u1EA7u C\xE0i \u0111\u1EB7t",
    finish: "\u0110\u0103ng nh\u1EADp v\xE0o Qu\u1EA3n tr\u1ECB",
    previewSite: "Xem tr\u01B0\u1EDBc Website",
    copy: "Sao ch\xE9p",
    copied: "\u0110\xE3 sao ch\xE9p v\xE0o b\u1ED9 nh\u1EDB t\u1EA1m!",
    download: "T\u1EA3i config.php",
    // Steps
    step1Title: "Ch\xE0o m\u1EEBng",
    step1Sub: "Ch\u1ECDn ng\xF4n ng\u1EEF",
    step2Title: "M\xF4i tr\u01B0\u1EDDng",
    step2Sub: "Ki\u1EC3m tra h\u1EC7 th\u1ED1ng",
    step3Title: "C\u01A1 s\u1EDF d\u1EEF li\u1EC7u",
    step3Sub: "K\u1EBFt n\u1ED1i MySQL",
    step4Title: "Th\xF4ng tin Trang",
    step4Sub: "T\xE0i kho\u1EA3n admin",
    step5Title: "Giao di\u1EC7n & Plugin",
    step5Sub: "T\u1ED1i \u01B0u ban \u0111\u1EA7u",
    step6Title: "C\xE0i \u0111\u1EB7t",
    step6Sub: "T\u1EA1o config.php",
    step7Title: "Ho\xE0n t\u1EA5t",
    step7Sub: "S\u1EB5n s\xE0ng s\u1EED d\u1EE5ng",
    // Step 1
    welcomeTitle: "Ch\xE0o m\u1EEBng b\u1EA1n \u0111\u1EBFn v\u1EDBi PrestoWorld",
    welcomeDesc: "Tr\xECnh h\u01B0\u1EDBng d\u1EABn thi\u1EBFt l\u1EADp hi\u1EC7n \u0111\u1EA1i gi\xFAp b\u1EA1n c\u1EA5u h\xECnh trang web PrestoWorld ch\u1EC9 trong 2 ph\xFAt v\u1EDBi chu\u1EA9n b\u1EA3o m\u1EADt v\xE0 hi\u1EC7u n\u0103ng cao nh\u1EA5t.",
    selectLangLabel: "Ng\xF4n ng\u1EEF c\xE0i \u0111\u1EB7t ch\xEDnh:",
    featuresTitle: "T\xEDnh n\u0103ng n\u1ED5i b\u1EADt c\u1EE7a tr\xECnh c\xE0i \u0111\u1EB7t m\u1EDBi:",
    feat1: "Ki\u1EC3m tra \u0111\u1ED9 t\u01B0\u01A1ng th\xEDch m\xE1y ch\u1EE7 (PHP 8.2+, MySQL 8.0, Redis Cache).",
    feat2: "M\xE3 h\xF3a t\u1EF1 \u0111\u1ED9ng Security Keys & Salts chu\u1EA9n PrestoWorld VIP.",
    feat3: "C\xE0i s\u1EB5n b\u1ED9 Plugin t\u1ED1i \u01B0u t\u1ED1c \u0111\u1ED9 v\xE0 b\u1EA3o m\u1EADt h\xE0ng \u0111\u1EA7u.",
    feat4: "Xu\u1EA5t file config.php chu\u1EA9n h\xF3a ngay t\u1EA1i ch\u1ED7.",
    // Step 2
    envTitle: "Ki\u1EC3m tra \u0110\u1ED9 s\u1EB5n s\xE0ng c\u1EE7a M\xE1y ch\u1EE7",
    envDesc: "H\u1EC7 th\u1ED1ng \u0111\xE3 t\u1EF1 \u0111\u1ED9ng qu\xE9t m\xF4i tr\u01B0\u1EDDng m\xE1y ch\u1EE7 c\u1EE7a b\u1EA1n \u0111\u1EC3 \u0111\u1EA3m b\u1EA3o hi\u1EC7u su\u1EA5t t\u1ED1t nh\u1EA5t cho PrestoWorld.",
    envStatusGood: "\u0110\u1EA1t chu\u1EA9n",
    envStatusWarning: "Khuy\u1EBFn ngh\u1ECB",
    // Step 3
    dbTitle: "C\u1EA5u h\xECnh C\u01A1 s\u1EDF d\u1EEF li\u1EC7u (Database)",
    dbDesc: "Nh\u1EADp th\xF4ng tin k\u1EBFt n\u1ED1i MySQL ho\u1EB7c MariaDB. N\u1EBFu ch\u01B0a r\xF5, h\xE3y li\xEAn h\u1EC7 nh\xE0 cung c\u1EA5p hosting c\u1EE7a b\u1EA1n.",
    dbNameLabel: "T\xEAn Database (Database Name)",
    dbNameHint: "T\xEAn c\u01A1 s\u1EDF d\u1EEF li\u1EC7u \u0111\xE3 t\u1EA1o tr\xEAn hosting/cPanel.",
    dbUserLabel: "T\xEAn \u0111\u0103ng nh\u1EADp (Username)",
    dbUserHint: "T\xE0i kho\u1EA3n c\xF3 quy\u1EC1n tr\xEAn c\u01A1 s\u1EDF d\u1EEF li\u1EC7u.",
    dbPassLabel: "M\u1EADt kh\u1EA9u (Password)",
    dbPassHint: "M\u1EADt kh\u1EA9u k\u1EBFt n\u1ED1i MySQL.",
    dbHostLabel: "\u0110\u1ECBa ch\u1EC9 m\xE1y ch\u1EE7 (Database Host)",
    dbHostHint: 'Th\xF4ng th\u01B0\u1EDDng l\xE0 "localhost" ho\u1EB7c "127.0.0.1".',
    dbPrefixLabel: "Ti\u1EC1n t\u1ED1 b\u1EA3ng (Table Prefix)",
    dbPrefixHint: "N\xEAn \u0111\u1ED5i ti\u1EC1n t\u1ED1 ng\u1EABu nhi\xEAn \u0111\u1EC3 t\u0103ng t\xEDnh b\u1EA3o m\u1EADt ch\u1ED1ng SQL injection.",
    testDbBtn: "Ki\u1EC3m tra K\u1EBFt n\u1ED1i Database",
    testDbSuccess: "K\u1EBFt n\u1ED1i th\xE0nh c\xF4ng! M\xE1y ch\u1EE7 MySQL 8.0 ph\u1EA3n h\u1ED3i trong 12ms.",
    testDbTesting: "\u0110ang k\u1EBFt n\u1ED1i t\u1EDBi m\xE1y ch\u1EE7...",
    loadPresetPrompt: "T\u1EA3i nhanh c\u1EA5u h\xECnh m\u1EABu:",
    // Step 4
    siteTitleLabel: "T\xEAn Website (Site Title)",
    siteTaglineLabel: "Kh\u1EA9u hi\u1EC7u (Tagline)",
    adminUserLabel: "T\xEAn \u0111\u0103ng nh\u1EADp Qu\u1EA3n tr\u1ECB vi\xEAn",
    adminPassLabel: "M\u1EADt kh\u1EA9u Qu\u1EA3n tr\u1ECB vi\xEAn",
    adminEmailLabel: "Email Qu\u1EA3n tr\u1ECB vi\xEAn",
    generatePass: "T\u1EA1o m\u1EADt kh\u1EA9u m\u1EA1nh",
    searchEngineLabel: "Kh\u1EA3 n\u0103ng hi\u1EC3n th\u1ECB v\u1EDBi c\xF4ng c\u1EE5 t\xECm ki\u1EBFm",
    searchEngineDesc: "Cho ph\xE9p Google, Bing l\u1EADp ch\u1EC9 m\u1EE5c website n\xE0y ngay l\u1EADp t\u1EE9c.",
    // Step 5
    themeStepTitle: "L\u1EF1a ch\u1ECDn Giao di\u1EC7n Kh\u1EDFi \u0111\u1EA7u",
    themeStepDesc: "B\u1EA1n c\xF3 th\u1EC3 thay \u0111\u1ED5i giao di\u1EC7n b\u1EA5t k\u1EF3 l\xFAc n\xE0o trong trang qu\u1EA3n tr\u1ECB.",
    pluginStepTitle: "G\xF3i Ti\u1EC7n \xEDch \u0110\u1EC1 xu\u1EA5t (Plugins)",
    pluginStepDesc: "Ch\u1ECDn c\xE1c ti\u1EC7n \xEDch b\u1ED5 sung \u0111\u1EC3 k\xEDch ho\u1EA1t ngay sau khi c\xE0i \u0111\u1EB7t ho\xE0n t\u1EA5t.",
    // Step 6
    installTitle: "\u0110ang ti\u1EBFn h\xE0nh C\xE0i \u0111\u1EB7t PrestoWorld",
    installDesc: "H\u1EC7 th\u1ED1ng \u0111ang kh\u1EDFi t\u1EA1o c\u01A1 s\u1EDF d\u1EEF li\u1EC7u, n\u1EA1p c\u1EA5u h\xECnh v\xE0 t\u1EA1o file config.php.",
    configTabTitle: "Xem tr\u01B0\u1EDBc file config.php",
    terminalTabTitle: "Nh\u1EADt k\xFD ti\u1EBFn tr\xECnh (Console)",
    // Step 7
    successTitle: "Ch\xFAc m\u1EEBng! C\xE0i \u0111\u1EB7t PrestoWorld Th\xE0nh C\xF4ng!",
    successDesc: "Trang web c\u1EE7a b\u1EA1n \u0111\xE3 \u0111\u01B0\u1EE3c kh\u1EDFi t\u1EA1o ho\xE0n ch\u1EC9nh v\xE0 s\u1EB5n s\xE0ng \u0111\u1EC3 xu\u1EA5t b\u1EA3n n\u1ED9i dung.",
    adminUrl: "\u0110\u01B0\u1EDDng d\u1EABn Qu\u1EA3n tr\u1ECB:",
    usernameRecap: "T\xEAn \u0111\u0103ng nh\u1EADp:",
    passwordRecap: "M\u1EADt kh\u1EA9u:",
    goToAdmin: "\u0110\u0103ng nh\u1EADp v\xE0o B\u1EA3ng Qu\u1EA3n tr\u1ECB (wp-admin)",
    visitSite: "Truy c\u1EADp Trang ch\u1EE7"
  },
  en: {
    brandTitle: "PrestoWorld Setup",
    brandBadge: "v6.7 Pro",
    themeLight: "Light",
    themeDark: "Dark",
    helpBtn: "Help",
    presetsBtn: "Presets",
    next: "Continue",
    back: "Back",
    installNow: "Install PrestoWorld",
    finish: "Log In to Admin",
    previewSite: "Preview Site",
    copy: "Copy",
    copied: "Copied to clipboard!",
    download: "Download config.php",
    // Steps
    step1Title: "Welcome",
    step1Sub: "Language selection",
    step2Title: "Environment",
    step2Sub: "System health check",
    step3Title: "Database",
    step3Sub: "MySQL connection",
    step4Title: "Site Info",
    step4Sub: "Admin credentials",
    step5Title: "Themes & Plugins",
    step5Sub: "Initial enhancements",
    step6Title: "Installation",
    step6Sub: "Generate wp-config",
    step7Title: "Finished",
    step7Sub: "Ready to launch",
    // Step 1
    welcomeTitle: "Welcome to PrestoWorld",
    welcomeDesc: "A modern, lightning-fast setup wizard replacing the legacy installer with enterprise security and instant speed optimization.",
    selectLangLabel: "Installation Language:",
    featuresTitle: "Modern Installer Capabilities:",
    feat1: "Server compatibility & health scanner (PHP 8.2+, MySQL 8.0, Redis).",
    feat2: "Automatic cryptographically secure Salt generation.",
    feat3: "Pre-bundled top-tier performance and security plugins.",
    feat4: "Instant one-click config.php exporter.",
    // Step 2
    envTitle: "Server Readiness & Diagnostics",
    envDesc: "We have automatically scanned your host environment to ensure peak PrestoWorld stability.",
    envStatusGood: "Passed",
    envStatusWarning: "Recommended",
    // Step 3
    dbTitle: "Database Configuration",
    dbDesc: "Provide your MySQL or MariaDB credentials. Contact your hosting provider if you are unsure.",
    dbNameLabel: "Database Name",
    dbNameHint: "The name of the database created for PrestoWorld.",
    dbUserLabel: "Database Username",
    dbUserHint: "Your MySQL user with full privileges.",
    dbPassLabel: "Database Password",
    dbPassHint: "Your database account password.",
    dbHostLabel: "Database Host",
    dbHostHint: 'Typically "localhost" or "127.0.0.1".',
    dbPrefixLabel: "Table Prefix",
    dbPrefixHint: "Change from default pw_ for enhanced security against SQL injection.",
    testDbBtn: "Test Database Connection",
    testDbSuccess: "Connection successful! MySQL 8.0 responded in 12ms.",
    testDbTesting: "Testing server handshake...",
    loadPresetPrompt: "Quick presets for popular stacks:",
    // Step 4
    siteTitleLabel: "Site Title",
    siteTaglineLabel: "Tagline",
    adminUserLabel: "Admin Username",
    adminPassLabel: "Admin Password",
    adminEmailLabel: "Your Email",
    generatePass: "Generate Strong Password",
    searchEngineLabel: "Search Engine Visibility",
    searchEngineDesc: "Discourage or allow search engines from indexing this site.",
    // Step 5
    themeStepTitle: "Choose a Starter Theme",
    themeStepDesc: "Select an elegant baseline design. You can change this anytime from Appearance.",
    pluginStepTitle: "Recommended Plugin Suite",
    pluginStepDesc: "Pick optional essentials to activate immediately upon setup.",
    // Step 6
    installTitle: "Installing PrestoWorld Core",
    installDesc: "Generating database tables, writing configuration, and configuring security salts.",
    configTabTitle: "config.php Output",
    terminalTabTitle: "Live Console Output",
    // Step 7
    successTitle: "PrestoWorld has been Installed!",
    successDesc: "Your website is now configured, secured, and ready for you to create content.",
    adminUrl: "Admin URL:",
    usernameRecap: "Username:",
    passwordRecap: "Password:",
    goToAdmin: "Log In to PrestoWorld Dashboard",
    visitSite: "Visit Website Frontpage"
  }
};
var state = {
  currentStep: 1,
  maxStepReached: 1,
  language: "vi",
  themeMode: "light",
  db: {
    name: "wordpress_db",
    user: "root",
    pass: "",
    host: "localhost",
    prefix: "pw_sec7_",
    tested: false
  },
  site: {
    title: "Website C\u1EE7a T\xF4i",
    tagline: "M\u1ED9t trang web PrestoWorld m\u1EDBi tinh v\xE0 t\u1ED1c \u0111\u1ED9 cao",
    adminUser: "admin",
    adminPass: generateRandomSecurePassword(),
    adminEmail: "admin@example.com",
    searchEnginePublic: true,
    timezone: "Asia/Ho_Chi_Minh"
  },
  theme: "twentytwentyfive",
  plugins: ["litespeed-cache", "wordfence-security", "yoast-seo"],
  installing: false,
  installProgress: 0,
  installed: false
};
function generateRandomSecurePassword() {
  const chars = "abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%^&*()_+";
  let pass = "";
  const array = new Uint32Array(16);
  crypto.getRandomValues(array);
  for (let i = 0; i < 16; i++) {
    pass += chars[array[i] % chars.length];
  }
  return pass;
}
function generateRandomSalt() {
  const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|";
  let salt = "";
  const array = new Uint32Array(64);
  crypto.getRandomValues(array);
  for (let i = 0; i < 64; i++) {
    salt += chars[array[i] % chars.length];
  }
  return salt;
}
var THEMES = [
  {
    id: "twentytwentyfive",
    name: "Twenty Twenty-Five",
    badge: "M\u1EB7c \u0111\u1ECBnh WP 6.7",
    desc: "Giao di\u1EC7n Block Theme chu\u1EA9n m\u1EDBi nh\u1EA5t, t\u1ED1i \u01B0u t\u1ED1c \u0111\u1ED9 100/100 Core Web Vitals.",
    previewBg: "linear-gradient(135deg, #1e293b 0%, #0f172a 100%)",
    textColor: "#ffffff"
  },
  {
    id: "astra",
    name: "Astra Modern",
    badge: "Ph\u1ED5 bi\u1EBFn nh\u1EA5t",
    desc: "Si\xEAu nh\u1EB9, linh ho\u1EA1t cho trang doanh nghi\u1EC7p, tin t\u1EE9c ho\u1EB7c landing page cao c\u1EA5p.",
    previewBg: "linear-gradient(135deg, #4f46e5 0%, #3730a3 100%)",
    textColor: "#ffffff"
  },
  {
    id: "blocksy",
    name: "Blocksy Next-Gen",
    badge: "Gutenberg Native",
    desc: "T\xEDch h\u1EE3p s\xE2u v\u1EDBi Block Editor, h\u1ED7 tr\u1EE3 Header/Footer Builder tr\u1EF1c quan tuy\u1EC7t \u0111\u1EB9p.",
    previewBg: "linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%)",
    textColor: "#ffffff"
  },
  {
    id: "storefront",
    name: "Woo Storefront",
    badge: "Th\u01B0\u01A1ng m\u1EA1i \u0111i\u1EC7n t\u1EED",
    desc: "T\u1ED1i \u01B0u d\xE0nh ri\xEAng cho WooCommerce b\xE1n h\xE0ng tr\u1EF1c tuy\u1EBFn v\xE0 c\u1ED5ng thanh to\xE1n.",
    previewBg: "linear-gradient(135deg, #9333ea 0%, #6b21a8 100%)",
    textColor: "#ffffff"
  }
];
var PLUGINS = [
  {
    id: "litespeed-cache",
    name: "LiteSpeed / WP Super Cache",
    category: "T\u1ED1c \u0111\u1ED9",
    desc: "B\u1ED9 nh\u1EDB \u0111\u1EC7m th\xF4ng minh, t\u1ED1i \u01B0u h\xF3a CSS/JS v\xE0 gi\u1EA3m t\u1EA3i CPU m\xE1y ch\u1EE7 80%."
  },
  {
    id: "wordfence-security",
    name: "Wordfence Security Suite",
    category: "B\u1EA3o m\u1EADt",
    desc: "T\u01B0\u1EDDng l\u1EEDa b\u1EA3o v\u1EC7 WAF, ch\u1ED1ng brute-force v\xE0 qu\xE9t m\xE3 \u0111\u1ED9c th\u1EDDi gian th\u1EF1c."
  },
  {
    id: "yoast-seo",
    name: "Yoast SEO / Rank Math",
    category: "SEO",
    desc: "T\u1EA1o XML Sitemap chu\u1EA9n, c\u1EA5u h\xECnh OpenGraph x\xE3 h\u1ED9i v\xE0 Rich Snippets schema."
  },
  {
    id: "woocommerce",
    name: "WooCommerce B\xE1n h\xE0ng",
    category: "E-Commerce",
    desc: "X\xE2y d\u1EF1ng c\u1EEDa h\xE0ng online \u0111\u1EA7y \u0111\u1EE7 t\xEDnh n\u0103ng gi\u1ECF h\xE0ng, thanh to\xE1n v\xE0 qu\u1EA3n l\xFD \u0111\u01A1n."
  },
  {
    id: "wpforms",
    name: "WPForms Lite Contact",
    category: "Li\xEAn h\u1EC7",
    desc: "T\u1EA1o form li\xEAn h\u1EC7 k\xE9o th\u1EA3 d\u1EC5 d\xE0ng, ch\u1ED1ng spam t\xEDch h\u1EE3p Google reCAPTCHA v3."
  },
  {
    id: "redis-cache",
    name: "Redis Object Cache",
    category: "Database",
    desc: "T\u0103ng t\u1ED1c truy v\u1EA5n c\u01A1 s\u1EDF d\u1EEF li\u1EC7u v\xE0 session ng\u01B0\u1EDDi d\xF9ng th\xF4ng qua RAM Redis."
  }
];
var DIAGNOSTICS = [
  { name: "Phi\xEAn b\u1EA3n PHP (PHP Version)", val: "PHP 8.2.18 (Zend Engine v4.2)", status: "good" },
  { name: "MySQL / MariaDB Support", val: "MySQL 8.0.36 + mysqli extension", status: "good" },
  { name: "Gi\u1EDBi h\u1EA1n b\u1ED9 nh\u1EDB (Memory Limit)", val: "512 MB (\u0110\u1EA1t chu\u1EA9n WP Pro)", status: "good" },
  { name: "Max Execution Time", val: "300s (R\u1EA5t t\u1ED1t cho import d\u1EEF li\u1EC7u)", status: "good" },
  { name: "Quy\u1EC1n ghi th\u01B0 m\u1EE5c (wp-content/)", val: "0755 (Writable / Kh\u1EA3 d\u1EE5ng)", status: "good" },
  { name: "Ph\u1EA7n m\u1EDF r\u1ED9ng cURL & OpenSSL", val: "\u0110\xE3 k\xEDch ho\u1EA1t (TLS 1.3)", status: "good" },
  { name: "X\u1EED l\xFD h\xECnh \u1EA3nh (Imagick / GD)", val: "ImageMagick 7.1 h\u1ED7 tr\u1EE3 WebP/AVIF", status: "good" }
];
var PRESETS = [
  {
    name: "Localhost (XAMPP / Laragon)",
    db: { name: "wordpress", user: "root", pass: "", host: "localhost", prefix: "pw_" }
  },
  {
    name: "Docker / DDEV Staging",
    db: { name: "db", user: "db", pass: "db", host: "db:3306", prefix: "pw_dev_" }
  },
  {
    name: "cPanel / DirectAdmin Host",
    db: { name: "myhost_wp", user: "myhost_user", pass: "SuperSec#2026@", host: "localhost", prefix: "pw_cp_" }
  },
  {
    name: "VPS Nginx Production",
    db: { name: "pw_enterprise", user: "pw_prod_usr", pass: "VPS#K92@Enterprise", host: "127.0.0.1:3306", prefix: "pw_sec7_" }
  }
];
function showToast(message) {
  const container = document.getElementById("wp-toasts") || createToastContainer();
  const toast = document.createElement("div");
  toast.className = "wp-toast";
  toast.innerHTML = `
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2">
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
      <polyline points="22 4 12 14.01 9 11.01"></polyline>
    </svg>
    <span>${message}</span>
  `;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = "0";
    toast.style.transform = "translateY(10px)";
    toast.style.transition = "all 0.2s ease";
    setTimeout(() => toast.remove(), 250);
  }, 2800);
}
function createToastContainer() {
  const container = document.createElement("div");
  container.id = "wp-toasts";
  container.className = "wp-toast-container";
  document.body.appendChild(container);
  return container;
}
function generateWpConfig() {
  const salts = [
    `define( 'AUTH_KEY',         '${generateRandomSalt()}' );`,
    `define( 'SECURE_AUTH_KEY',  '${generateRandomSalt()}' );`,
    `define( 'LOGGED_IN_KEY',    '${generateRandomSalt()}' );`,
    `define( 'NONCE_KEY',        '${generateRandomSalt()}' );`,
    `define( 'AUTH_SALT',        '${generateRandomSalt()}' );`,
    `define( 'SECURE_AUTH_SALT', '${generateRandomSalt()}' );`,
    `define( 'LOGGED_IN_SALT',   '${generateRandomSalt()}' );`,
    `define( 'NONCE_SALT',       '${generateRandomSalt()}' );`
  ].join("\n");
  return `<?php
/**
 * The base configuration for PrestoWorld
 *
 * Generated by PrestoWorld Modern Setup Wizard
 * @package PrestoWorld
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for PrestoWorld */
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
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ PrestoWorld.org secret-key service}.
 */
${salts}
/**#@-*/

/**
 * PrestoWorld database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = '${state.db.prefix}';

/**
 * For developers: PrestoWorld debugging mode.
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

/** Absolute path to the PrestoWorld directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up PrestoWorld vars and included files. */
require_once ABSPATH . 'wp-settings.php';
`;
}
function getPasswordStrength(pw) {
  if (!pw || pw.length < 5) return { level: "very-weak", text: "R\u1EA5t y\u1EBFu (Nguy hi\u1EC3m)" };
  let score = 0;
  if (pw.length >= 8) score++;
  if (pw.length >= 12) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  if (score <= 1) return { level: "weak", text: "Y\u1EBFu" };
  if (score <= 3) return { level: "medium", text: "Trung b\xECnh" };
  return { level: "strong", text: "M\u1EA1nh (Khuy\xEAn d\xF9ng)" };
}
function renderApp() {
  const root = document.getElementById("root");
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
            <option value="vi" ${state.language === "vi" ? "selected" : ""}>\u{1F1FB}\u{1F1F3} Ti\u1EBFng Vi\u1EC7t</option>
            <option value="en" ${state.language === "en" ? "selected" : ""}>\u{1F1FA}\u{1F1F8} English</option>
          </select>

          <!-- Theme Toggle Button -->
          <button class="wp-btn-icon" id="btn-theme-toggle" title="Giao di\u1EC7n S\xE1ng/T\u1ED1i" aria-label="Toggle Theme">
            ${state.themeMode === "light" ? `
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
          <div class="wp-stepper-title">${state.language === "vi" ? "Ti\u1EBFn tr\xECnh c\xE0i \u0111\u1EB7t" : "Installation Steps"}</div>
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
            PrestoWorld ${t.brandBadge} \xB7 PHP 8.2+ \xB7 MariaDB/MySQL 8.0+
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
            <h3 class="wp-modal-title">${state.language === "vi" ? "Ch\u1ECDn M\u1EABu C\u1EA5u H\xECnh M\xE1y Ch\u1EE7" : "Choose Server Preset"}</h3>
            <button class="wp-btn-icon" id="btn-close-presets" style="border: none; background: transparent;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="wp-modal-body">
            <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 1.25rem;">
              ${state.language === "vi" ? "N\u1EA1p nhanh th\xF4ng s\u1ED1 Database ph\xF9 h\u1EE3p v\u1EDBi m\xF4i tr\u01B0\u1EDDng tri\u1EC3n khai c\u1EE7a b\u1EA1n:" : "Instantly fill standard database credentials for your specific stack:"}
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
                    ${state.language === "vi" ? "\xC1p d\u1EE5ng" : "Apply"}
                  </button>
                </div>
              `).join("")}
            </div>
          </div>
          <div class="wp-modal-footer">
            <button class="wp-btn-secondary" id="btn-cancel-presets">${state.language === "vi" ? "\u0110\xF3ng" : "Close"}</button>
          </div>
        </div>
      </div>

      <!-- Preview Mockup Modal -->
      <div class="wp-modal-overlay" id="preview-modal">
        <div class="wp-modal" style="max-width: 780px;">
          <div class="wp-modal-header">
            <h3 class="wp-modal-title">${state.language === "vi" ? "Xem tr\u01B0\u1EDBc Trang Ch\u1EE7 PrestoWorld" : "Live PrestoWorld Mockup Preview"}</h3>
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
                <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em;">${state.site.title || "PrestoWorld Site"}</h1>
                <nav style="display: flex; gap: 1rem; font-size: 0.85rem; font-weight: 600; color: #475569;">
                  <span>Trang ch\u1EE7</span>
                  <span>B\xE0i vi\u1EBFt</span>
                  <span>Gi\u1EDBi thi\u1EC7u</span>
                  <span>Li\xEAn h\u1EC7</span>
                </nav>
              </div>
              <p style="font-size: 1.1rem; color: #475569; font-style: italic; margin-bottom: 2rem;">"${state.site.tagline || "Just another PrestoWorld site"}"</p>
              
              <article style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; background: #f8fafc;">
                <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #0f172a;">Ch\xE0o th\u1EBF gi\u1EDBi! (Hello world!)</h2>
                <p style="font-size: 0.88rem; color: #64748b; line-height: 1.6; margin-bottom: 1rem;">
                  Ch\xE0o m\u1EEBng b\u1EA1n \u0111\u1EBFn v\u1EDBi PrestoWorld. \u0110\xE2y l\xE0 b\xE0i vi\u1EBFt \u0111\u1EA7u ti\xEAn c\u1EE7a b\u1EA1n. H\xE3y ch\u1EC9nh s\u1EEDa ho\u1EB7c x\xF3a n\xF3, sau \u0111\xF3 b\u1EAFt \u0111\u1EA7u vi\u1EBFt b\xE0i!
                </p>
                <div style="font-size: 0.75rem; color: #94a3b8; font-family: var(--font-mono);">
                  \u0110\u0103ng b\u1EDFi <strong>${state.site.adminUser}</strong> \xB7 Giao di\u1EC7n k\xEDch ho\u1EA1t: <strong>${THEMES.find((th) => th.id === state.theme)?.name}</strong>
                </div>
              </article>
            </div>
          </div>
          <div class="wp-modal-footer">
            <button class="wp-btn-secondary" id="btn-close-preview-footer">${state.language === "vi" ? "\u0110\xF3ng" : "Close"}</button>
          </div>
        </div>
      </div>
    </div>
  `;
  attachEventHandlers();
}
function renderStepItem(stepNum, title, subtitle) {
  const isActive = state.currentStep === stepNum;
  const isCompleted = state.currentStep > stepNum;
  const isDisabled = stepNum > state.maxStepReached && !isCompleted;
  let classes = "wp-step-item";
  if (isActive) classes += " active";
  if (isCompleted) classes += " completed";
  if (isDisabled) classes += " disabled";
  return `
    <li>
      <button class="${classes}" onclick="window.goToStep(${stepNum})" ${isDisabled ? "disabled" : ""}>
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
function renderCurrentStepContent() {
  const t = i18n[state.language];
  switch (state.currentStep) {
    case 1:
      return `
        <div class="wp-card-header">
          <div class="wp-card-kicker">B\u01B0\u1EDBc 01 / 07</div>
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
              <strong>Giao di\u1EC7n c\xE0i \u0111\u1EB7t n\xE2ng cao th\u1EBF h\u1EC7 m\u1EDBi:</strong>
              Thay th\u1EBF ho\xE0n to\xE0n file <code>wp-admin/install.php</code> m\u1EB7c \u0111\u1ECBnh c\u0169 k\u1EF9 b\u1EB1ng tr\u1EA3i nghi\u1EC7m m\u01B0\u1EE3t m\xE0, h\u1ED7 tr\u1EE3 ki\u1EC3m tra b\u1EA3o m\u1EADt v\xE0 t\u1EA1o c\u1EA5u h\xECnh t\u1EF1 \u0111\u1ED9ng.
            </div>
          </div>

          <div style="margin-top: 1.5rem;">
            <label class="wp-label" style="margin-bottom: 0.5rem;">${t.selectLangLabel}</label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
              <button class="wp-plugin-card ${state.language === "vi" ? "active" : ""}" onclick="window.changeLanguage('vi')">
                <span style="font-size: 1.75rem;">\u{1F1FB}\u{1F1F3}</span>
                <div class="wp-plugin-details">
                  <div class="wp-plugin-title">Ti\u1EBFng Vi\u1EC7t</div>
                  <div class="wp-plugin-desc">Ng\xF4n ng\u1EEF ti\u1EBFng Vi\u1EC7t chu\u1EA9n h\xF3a \u0111\u1EA7y \u0111\u1EE7</div>
                </div>
              </button>

              <button class="wp-plugin-card ${state.language === "en" ? "active" : ""}" onclick="window.changeLanguage('en')">
                <span style="font-size: 1.75rem;">\u{1F1FA}\u{1F1F8}</span>
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
                <span>\u{1F6E1}\uFE0F ${t.feat2}</span>
              </div>
              <div class="wp-health-item">
                <span>\u26A1 ${t.feat1}</span>
              </div>
              <div class="wp-health-item">
                <span>\u{1F4E6} ${t.feat3}</span>
              </div>
              <div class="wp-health-item">
                <span>\u{1F4C4} ${t.feat4}</span>
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
          <div class="wp-card-kicker">B\u01B0\u1EDBc 02 / 07</div>
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
              <strong>M\xE1y ch\u1EE7 \u0111\u1EA1t 100% \u0111i\u1EC1u ki\u1EC7n ti\xEAu chu\u1EA9n:</strong> M\xF4i tr\u01B0\u1EDDng s\u1EB5n s\xE0ng \u0111\xE1p \u1EE9ng PrestoWorld Core, REST API v\xE0 Full Site Editing.
            </div>
          </div>

          <div class="wp-health-grid">
            ${DIAGNOSTICS.map((d) => `
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
            `).join("")}
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
          <div class="wp-card-kicker">B\u01B0\u1EDBc 03 / 07</div>
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
                  <input class="wp-input font-mono" id="db_pass" type="password" value="${state.db.pass}" oninput="window.updateDbField('pass', this.value)" placeholder="\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022" />
                  <div class="wp-input-addon">
                    <button class="wp-btn-icon" style="width: 28px; height: 28px; border:none;" onclick="window.toggleDbPassVisibility()">
                      \u{1F441}\uFE0F
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
              <input class="wp-input font-mono" id="db_prefix" value="${state.db.prefix}" oninput="window.updateDbField('prefix', this.value)" placeholder="pw_" />
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
                ` : ""}
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
          <div class="wp-card-kicker">B\u01B0\u1EDBc 04 / 07</div>
          <h2 class="wp-card-title">${t.siteTitleLabel} & Qu\u1EA3n tr\u1ECB vi\xEAn</h2>
          <p class="wp-card-desc">Thi\u1EBFt l\u1EADp ti\xEAu \u0111\u1EC1 website v\xE0 th\xF4ng tin \u0111\u0103ng nh\u1EADp qu\u1EA3n tr\u1ECB vi\xEAn t\u1ED1i cao.</p>
        </div>

        <div class="wp-card-body">
          <div class="wp-form-grid">
            <div class="wp-form-grid-2">
              <div class="wp-form-group">
                <label class="wp-label" for="site_title">${t.siteTitleLabel}</label>
                <input class="wp-input" id="site_title" value="${state.site.title}" oninput="window.updateSiteField('title', this.value)" placeholder="V\xED d\u1EE5: C\xF4ng Ty TNHH S\xE1ng T\u1EA1o" />
              </div>

              <div class="wp-form-group">
                <label class="wp-label" for="site_tagline">${t.siteTaglineLabel}</label>
                <input class="wp-input" id="site_tagline" value="${state.site.tagline}" oninput="window.updateSiteField('tagline', this.value)" placeholder="Kh\u1EA9u hi\u1EC7u ng\u1EAFn c\u1EE7a trang..." />
              </div>
            </div>

            <div class="wp-form-grid-2">
              <div class="wp-form-group">
                <label class="wp-label" for="admin_user">${t.adminUserLabel}</label>
                <input class="wp-input font-mono" id="admin_user" value="${state.site.adminUser}" oninput="window.updateSiteField('adminUser', this.value)" placeholder="admin" />
                <span class="wp-helper-text">Tr\xE1nh d\xF9ng t\xEAn "admin" \u0111\u01A1n gi\u1EA3n tr\xEAn production \u0111\u1EC3 ph\xF2ng brute-force.</span>
              </div>

              <div class="wp-form-group">
                <label class="wp-label" for="admin_email">${t.adminEmailLabel}</label>
                <input class="wp-input" id="admin_email" type="email" value="${state.site.adminEmail}" oninput="window.updateSiteField('adminEmail', this.value)" placeholder="admin@domain.com" />
                <span class="wp-helper-text">D\xF9ng \u0111\u1EC3 nh\u1EADn th\xF4ng b\xE1o ph\u1EE5c h\u1ED3i m\u1EADt kh\u1EA9u v\xE0 c\u1EADp nh\u1EADt b\u1EA3o m\u1EADt PrestoWorld.</span>
              </div>
            </div>

            <!-- Password field with generator & strength meter -->
            <div class="wp-form-group">
              <div class="wp-label">
                <span>${t.adminPassLabel}</span>
                <button type="button" class="wp-preset-btn" onclick="window.generateNewPassword()">
                  \u26A1 ${t.generatePass}
                </button>
              </div>
              <div class="wp-input-wrapper">
                <input class="wp-input font-mono" id="admin_pass" type="text" value="${state.site.adminPass}" oninput="window.updateSiteField('adminPass', this.value)" />
                <div class="wp-input-addon">
                  <button type="button" class="wp-btn-icon" style="width: 28px; height: 28px; border:none;" onclick="window.copyToClipboard('${state.site.adminPass}', '\u0110\xE3 sao ch\xE9p m\u1EADt kh\u1EA9u!')">
                    \u{1F4CB}
                  </button>
                </div>
              </div>

              <!-- Strength meter bar -->
              <div class="wp-pw-meter">
                <div class="wp-pw-fill ${strength.level}"></div>
              </div>
              <div class="wp-pw-feedback">
                <span>\u0110\u1ED9 m\u1EA1nh: <strong style="color: var(--text-primary);">${strength.text}</strong></span>
                <span>Entropy: 128-bit</span>
              </div>
            </div>

            <!-- Search engine visibility switch -->
            <div class="wp-plugin-card" style="margin-top: 0.5rem; cursor: default;">
              <input type="checkbox" class="wp-checkbox" id="se_visible" ${state.site.searchEnginePublic ? "checked" : ""} onchange="window.updateSiteField('searchEnginePublic', this.checked)" />
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
              \u{1F50D} ${t.previewSite}
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
          <div class="wp-card-kicker">B\u01B0\u1EDBc 05 / 07</div>
          <h2 class="wp-card-title">${t.themeStepTitle}</h2>
          <p class="wp-card-desc">${t.themeStepDesc}</p>
        </div>

        <div class="wp-card-body">
          <div class="wp-theme-grid">
            ${THEMES.map((th) => `
              <div class="wp-theme-card ${state.theme === th.id ? "selected" : ""}" onclick="window.selectTheme('${th.id}')">
                <div class="wp-theme-preview" style="background: ${th.previewBg};">
                  <span class="wp-theme-preview-badge">${th.badge}</span>
                </div>
                <div class="wp-theme-info">
                  <div>
                    <div class="wp-theme-name">${th.name}</div>
                    <div class="wp-theme-desc">${th.desc}</div>
                  </div>
                  <div style="margin-top: 0.75rem; display: flex; justify-content: flex-end;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: ${state.theme === th.id ? "var(--wp-blue-600)" : "var(--text-muted)"};">
                      ${state.theme === th.id ? "\u2713 \u0110\xE3 ch\u1ECDn" : "Ch\u1ECDn giao di\u1EC7n"}
                    </span>
                  </div>
                </div>
              </div>
            `).join("")}
          </div>

          <div style="margin-top: 2rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary);">
              ${t.pluginStepTitle}
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.75rem;">
              ${t.pluginStepDesc}
            </p>

            <div class="wp-plugins-grid">
              ${PLUGINS.map((pl) => {
        const isChecked = state.plugins.includes(pl.id);
        return `
                  <div class="wp-plugin-card ${isChecked ? "active" : ""}" onclick="window.togglePlugin('${pl.id}')">
                    <input type="checkbox" class="wp-checkbox" ${isChecked ? "checked" : ""} onclick="event.stopPropagation(); window.togglePlugin('${pl.id}')" />
                    <div class="wp-plugin-details">
                      <div class="wp-plugin-title">
                        <span>${pl.name}</span>
                        <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 500;">${pl.category}</span>
                      </div>
                      <div class="wp-plugin-desc">${pl.desc}</div>
                    </div>
                  </div>
                `;
      }).join("")}
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
          <div class="wp-card-kicker">B\u01B0\u1EDBc 06 / 07</div>
          <h2 class="wp-card-title">${t.installTitle}</h2>
          <p class="wp-card-desc">${t.installDesc}</p>
        </div>

        <div class="wp-card-body">
          <!-- Installation Progress -->
          <div class="wp-progress-container">
            <div class="wp-progress-header">
              <span id="install-step-text">${state.installing ? "\u0110ang th\u1EF1c thi c\xE1c t\xE1c v\u1EE5 c\xE0i \u0111\u1EB7t..." : "S\u1EB5n s\xE0ng kh\u1EDFi ch\u1EA1y c\xE0i \u0111\u1EB7t PrestoWorld"}</span>
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
              <div class="wp-term-line info">[INIT] Chu\u1EA9n b\u1ECB m\xF4i tr\u01B0\u1EDDng c\xE0i \u0111\u1EB7t PrestoWorld Core 6.7...</div>
              <div class="wp-term-line">[1/6] Ki\u1EC3m tra th\xF4ng s\u1ED1 k\u1EBFt n\u1ED1i Database: ${state.db.name} @ ${state.db.host}</div>
              <div class="wp-term-line">[2/6] T\u1EA1o ti\u1EC1n t\u1ED1 b\u1EA3ng d\u1EEF li\u1EC7u an to\xE0n: ${state.db.prefix}*</div>
              ${state.installing ? '<div class="wp-term-line info">\u0110ang ch\u1EA1y ti\u1EBFn tr\xECnh c\xE0i \u0111\u1EB7t...</div>' : ""}
            </div>
          </div>

          <!-- config.php preview & actions -->
          <div class="wp-code-box">
            <div class="wp-code-header">
              <span>config.php (\u0110\xE3 t\xEDch h\u1EE3p Security Salts)</span>
              <div style="display: flex; gap: 0.5rem;">
                <button class="wp-preset-btn" onclick="window.copyWpConfig()">
                  \u{1F4CB} ${t.copy}
                </button>
                <button class="wp-preset-btn" onclick="window.downloadWpConfig()">
                  \u{1F4BE} ${t.download}
                </button>
              </div>
            </div>
            <pre class="wp-code-content"><code>${escapeHtml(wpConfigCode)}</code></pre>
          </div>
        </div>

        <div class="wp-card-footer">
          <button class="wp-btn wp-btn-secondary" onclick="window.goToStep(5)" ${state.installing ? "disabled" : ""}>
            ${t.back}
          </button>
          <button class="wp-btn wp-btn-primary" id="btn-run-install" onclick="window.runInstallation()" ${state.installing ? "disabled" : ""}>
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
                  <span id="recap-pass">\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022</span>
                  <button class="wp-btn-icon" style="width: 24px; height: 24px; border: none;" onclick="window.toggleRecapPassword()">
                    \u{1F441}\uFE0F
                  </button>
                </span>
              </div>
              <div class="wp-cred-row">
                <span class="wp-cred-label">Giao di\u1EC7n (Theme):</span>
                <span class="wp-cred-val">${THEMES.find((th) => th.id === state.theme)?.name}</span>
              </div>
              <div class="wp-cred-row">
                <span class="wp-cred-label">Plugins k\xEDch ho\u1EA1t:</span>
                <span class="wp-cred-val" style="font-size: 0.8rem;">${state.plugins.length} ti\u1EC7n \xEDch</span>
              </div>
            </div>

            <div style="display: flex; justify-content: center; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
              <button class="wp-btn wp-btn-secondary" onclick="window.openPreviewModal()">
                \u{1F310} ${t.visitSite}
              </button>
              <button class="wp-btn wp-btn-primary" onclick="window.goToAdminDashboard()">
                \u{1F680} ${t.goToAdmin}
              </button>
            </div>
          </div>
        </div>
      `;
    default:
      return "";
  }
}
function escapeHtml(str) {
  return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
function attachEventHandlers() {
  const themeToggle = document.getElementById("btn-theme-toggle");
  if (themeToggle) {
    themeToggle.onclick = () => {
      state.themeMode = state.themeMode === "light" ? "dark" : "light";
      document.body.setAttribute("data-theme", state.themeMode);
      renderApp();
    };
  }
  const langSwitcher = document.getElementById("lang-switcher");
  if (langSwitcher) {
    langSwitcher.onchange = (e) => {
      const target = e.target;
      state.language = target.value;
      renderApp();
    };
  }
  const btnOpenPresets = document.getElementById("btn-open-presets");
  const btnClosePresets = document.getElementById("btn-close-presets");
  const btnCancelPresets = document.getElementById("btn-cancel-presets");
  const presetsModal = document.getElementById("presets-modal");
  if (btnOpenPresets && presetsModal) {
    btnOpenPresets.onclick = () => presetsModal.classList.add("open");
  }
  if (btnClosePresets && presetsModal) {
    btnClosePresets.onclick = () => presetsModal.classList.remove("open");
  }
  if (btnCancelPresets && presetsModal) {
    btnCancelPresets.onclick = () => presetsModal.classList.remove("open");
  }
  const btnClosePreview = document.getElementById("btn-close-preview");
  const btnClosePreviewFooter = document.getElementById("btn-close-preview-footer");
  const previewModal = document.getElementById("preview-modal");
  if (btnClosePreview && previewModal) {
    btnClosePreview.onclick = () => previewModal.classList.remove("open");
  }
  if (btnClosePreviewFooter && previewModal) {
    btnClosePreviewFooter.onclick = () => previewModal.classList.remove("open");
  }
}
window.goToStep = (step) => {
  if (step < 1 || step > 7) return;
  state.currentStep = step;
  if (step > state.maxStepReached) {
    state.maxStepReached = step;
  }
  renderApp();
  window.scrollTo({ top: 0, behavior: "smooth" });
};
window.changeLanguage = (lang) => {
  state.language = lang;
  renderApp();
};
window.updateDbField = (field, val) => {
  state.db[field] = val;
  state.db.tested = false;
};
window.updateSiteField = (field, val) => {
  state.site[field] = val;
  if (field === "adminPass") {
    renderApp();
  }
};
window.toggleDbPassVisibility = () => {
  const input = document.getElementById("db_pass");
  if (input) {
    input.type = input.type === "password" ? "text" : "password";
  }
};
window.testDatabaseConnection = () => {
  const resultDiv = document.getElementById("db-test-result");
  const btn = document.getElementById("btn-test-db");
  if (!resultDiv) return;
  if (btn) btn.disabled = true;
  resultDiv.innerHTML = `
    <div class="wp-callout wp-callout-info" style="margin: 0; padding: 0.75rem 1rem;">
      <span style="display: inline-block; animation: spin 1s linear infinite;">\u23F3</span>
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
  showToast(state.language === "vi" ? "\u0110\xE3 t\u1EA1o m\u1EADt kh\u1EA9u m\u1EA1nh m\u1EDBi!" : "Generated new strong password!");
};
window.selectTheme = (themeId) => {
  state.theme = themeId;
  renderApp();
};
window.togglePlugin = (pluginId) => {
  if (state.plugins.includes(pluginId)) {
    state.plugins = state.plugins.filter((p) => p !== pluginId);
  } else {
    state.plugins.push(pluginId);
  }
  renderApp();
};
window.copyToClipboard = (text, msg) => {
  navigator.clipboard.writeText(text).then(() => {
    showToast(msg);
  });
};
window.copyWpConfig = () => {
  const code = generateWpConfig();
  navigator.clipboard.writeText(code).then(() => {
    showToast(state.language === "vi" ? "\u0110\xE3 sao ch\xE9p n\u1ED9i dung config.php!" : "config.php copied to clipboard!");
  });
};
window.downloadWpConfig = () => {
  const code = generateWpConfig();
  const blob = new Blob([code], { type: "application/x-httpd-php" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = "config.php";
  document.body.appendChild(a);
  a.click();
  document.body.appendChild(a);
  a.remove();
  URL.revokeObjectURL(url);
  showToast(state.language === "vi" ? "\u0110\xE3 t\u1EA3i xu\u1ED1ng file config.php th\xE0nh c\xF4ng!" : "config.php downloaded successfully!");
};
window.applyPreset = (index) => {
  const preset = PRESETS[index];
  if (!preset) return;
  state.db = { ...preset.db, tested: true };
  const modal = document.getElementById("presets-modal");
  if (modal) modal.classList.remove("open");
  renderApp();
  showToast(`${state.language === "vi" ? "\u0110\xE3 \xE1p d\u1EE5ng m\u1EABu" : "Applied preset"}: ${preset.name}`);
};
window.openPreviewModal = () => {
  const modal = document.getElementById("preview-modal");
  if (modal) modal.classList.add("open");
};
var recapVisible = false;
window.toggleRecapPassword = () => {
  recapVisible = !recapVisible;
  const el = document.getElementById("recap-pass");
  if (el) {
    el.innerText = recapVisible ? state.site.adminPass : "\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022";
  }
};
window.goToAdminDashboard = () => {
  showToast("\u0110ang chuy\u1EC3n h\u01B0\u1EDBng t\u1EDBi PrestoWorld wp-admin login...");
  setTimeout(() => {
    window.openPreviewModal();
  }, 600);
};
window.runInstallation = async () => {
  if (state.installing) return;
  state.installing = true;
  state.installProgress = 5;
  const terminal = document.getElementById("terminal-body");
  const bar = document.getElementById("install-bar");
  const pct = document.getElementById("install-pct");
  const stepText = document.getElementById("install-step-text");
  const btn = document.getElementById("btn-run-install");
  if (btn) btn.disabled = true;
  function log(msg, type = "info") {
    if (terminal) {
      const line = document.createElement("div");
      line.className = type === "success" ? "wp-term-line success" : type === "error" ? "wp-term-line error" : "wp-term-line info";
      line.innerText = msg;
      terminal.appendChild(line);
      terminal.scrollTop = terminal.scrollHeight;
    }
  }
  function setProgress(percent, text) {
    state.installProgress = percent;
    if (bar) bar.style.width = percent + "%";
    if (pct) pct.innerText = percent + "%";
    if (stepText) stepText.innerText = text;
  }
  log("[INIT] Kh\u1EDFi \u0111\u1ED9ng ti\u1EBFn tr\xECnh c\xE0i \u0111\u1EB7t PrestoWorld...", "info");
  setProgress(10, "Chu\u1EA9n b\u1ECB k\u1EBFt n\u1ED1i database...");
  await new Promise((r) => setTimeout(r, 400));
  log("[DB] \u0110ang ki\u1EC3m tra k\u1EBFt n\u1ED1i database...", "info");
  setProgress(20, "\u0110ang g\u1EEDi y\xEAu c\u1EA7u c\xE0i \u0111\u1EB7t...");
  try {
    const res = await fetch("/api/setup/install", {
      method: "POST",
      headers: { "Content-Type": "application/json", "Accept": "application/json" },
      body: JSON.stringify({
        db_connection: "pgsql",
        db_host: state.db.host,
        db_port: state.db.host.includes(":") ? state.db.host.split(":")[1] : state.db.host.includes("mysql") ? 3306 : 5432,
        db_name: state.db.name,
        db_username: state.db.user,
        db_password: state.db.pass,
        db_prefix: state.db.prefix,
        site_title: state.site.title,
        admin_username: state.site.adminUser,
        admin_email: state.site.adminEmail,
        admin_password: state.site.adminPass
      })
    });
    const data = await res.json().catch(() => ({}));
    if (res.ok && data.success) {
      setProgress(70, "Migrations ho\xE0n t\u1EA5t, \u0111ang kh\u1EDFi t\u1EA1o d\u1EEF li\u1EC7u...");
      log("[MIGRATION] T\u1EA5t c\u1EA3 migrations \u0111\xE3 \u0111\u01B0\u1EE3c th\u1EF1c thi v\xE0 ghi l\u1ECBch s\u1EED v\xE0o "{$prefix} migrations"", "success");
      const result = data.migrations || {};
      if (result.created && result.created.length > 0) {
        result.created.forEach((m) => log("[MIGRATION] \u2713 " + m, "success"));
      }
      if (result.skipped && result.skipped.length > 0) {
        result.skipped.forEach((m) => log("[MIGRATION] \u21B7 " + m + " (already run)", "info"));
      }
      await new Promise((r) => setTimeout(r, 400));
      log("[AUTH] T\xE0i kho\u1EA3n qu\u1EA3n tr\u1ECB \u0111\xE3 \u0111\u01B0\u1EE3c t\u1EA1o", "success");
      setProgress(85, "\u0110ang ghi tr\u1EA1ng th\xE1i installed...");
      await new Promise((r) => setTimeout(r, 300));
      log("[OPTIONS] presto_installed = 1 \u2192 pw_ options", "success");
      setProgress(100, "C\xE0i \u0111\u1EB7t ho\xE0n t\u1EA5t!");
      await new Promise((r) => setTimeout(r, 300));
      log("[SUCCESS] PrestoWorld \u0111\xE3 \u0111\u01B0\u1EE3c c\xE0i \u0111\u1EB7t th\xE0nh c\xF4ng!", "success");
      state.installing = false;
      state.installed = true;
      setTimeout(() => {
        window.goToStep(7);
      }, 800);
    } else {
      log("[ERROR] " + (data.error || "Unknown error"), "error");
      setProgress(0, "C\xE0i \u0111\u1EB7t th\u1EA5t b\u1EA1i");
      state.installing = false;
      if (btn) btn.disabled = false;
    }
  } catch (err) {
    log("[ERROR] L\u1ED7i k\u1EBFt n\u1ED1i: " + err.message, "error");
    setProgress(0, "C\xE0i \u0111\u1EB7t th\u1EA5t b\u1EA1i");
    state.installing = false;
    if (btn) btn.disabled = false;
  }
};
document.addEventListener("DOMContentLoaded", () => {
  renderApp();
});
if (document.readyState === "complete" || document.readyState === "interactive") {
  renderApp();
}
