<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DigitalCore - Nâng tầm dự án số của bạn</title>
    <link rel="stylesheet" href="/css/frontend.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container header-inner">
            <div class="logo">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="32" height="32" rx="8" fill="#3B82F6"/>
                    <path d="M12 10H20C21.1046 10 22 10.8954 22 12V20C22 21.1046 21.1046 22 20 22H12C10.8954 22 10 21.1046 10 20V12C10 10.8954 10.8954 10 12 10Z" stroke="white" stroke-width="2"/>
                    <path d="M16 14V18" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <path d="M14 16H18" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Digital<span>Core.</span>
            </div>
            <nav>
                <ul>
                    <li><a href="#">Sản phẩm</a></li>
                    <li><a href="#">Gói thành viên</a></li>
                    <li><a href="#">Tài liệu</a></li>
                    <li><a href="#">Cộng đồng</a></li>
                    <li><a href="#">Hỗ trợ</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <button class="btn-search">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                </button>
                <a href="#" class="btn-login">Đăng nhập</a>
                <a href="#" class="btn-primary">Tham gia</a>
            </div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="hero container">
            <div class="hero-content">
                <div class="hero-badge">🚀 Kho tài nguyên số hàng đầu</div>
                <h1>Nâng tầm <span class="gradient-text">dự án số</span> với kho tài nguyên cao cấp</h1>
                <p>Khám phá hàng ngàn Theme, Plugin và Phần mềm chất lượng giúp tăng tốc quá trình phát triển dự án của bạn.</p>
                <div class="hero-btns">
                    <a href="#" class="btn-orange">Xem kho tài nguyên</a>
                    <a href="#" class="btn-outline">Dùng thử ngay</a>
                </div>
                <div style="margin-top: 30px; display: flex; align-items: center; gap: 10px;">
                    <div class="avatars" style="display: flex;">
                        <img src="https://i.pravatar.cc/40?u=1" style="border-radius: 50%; border: 2px solid white; margin-right: -10px;">
                        <img src="https://i.pravatar.cc/40?u=2" style="border-radius: 50%; border: 2px solid white; margin-right: -10px;">
                        <img src="https://i.pravatar.cc/40?u=3" style="border-radius: 50%; border: 2px solid white;">
                    </div>
                    <div style="font-size: 14px; font-weight: 500;">+10,000 khách hàng tin dùng</div>
                </div>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&q=80&w=800" alt="Tech Dashboard">
            </div>
        </section>

        <!-- Domain Section -->
        <section class="domain-section">
            <div class="container">
                <h2>Bắt đầu với tên miền hoàn hảo của bạn</h2>
                <div class="domain-search-bar">
                    <input type="text" placeholder="Nhập tên miền bạn mong muốn...">
                    <button>Kiểm tra</button>
                </div>
                <div class="domain-tips">
                    <span>.com <b>$9.99</b></span>
                    <span>.net <b>$12.50</b></span>
                    <span>.org <b>$8.00</b></span>
                    <span>.vn <b>$25.00</b></span>
                </div>
            </div>
        </section>

        <!-- Ecosystem -->
        <section class="ecosystem container">
            <div class="section-header">
                <h2>Khám phá hệ sinh thái DigitalCore</h2>
                <a href="#">Xem tất cả danh mục →</a>
            </div>
            <div class="ecosystem-grid">
                <div class="eco-card">
                    <div class="eco-icon blue">🎨</div>
                    <h3>Themes</h3>
                    <p>Bộ sưu tập giao diện website đa dạng, chuẩn SEO và tối ưu trải nghiệm.</p>
                    <a href="#" class="read-more">Khám phá ngay →</a>
                </div>
                <div class="eco-card">
                    <div class="eco-icon purple">🔌</div>
                    <h3>Plugins</h3>
                    <p>Mở rộng tính năng website với kho Plugin mạnh mẽ và linh hoạt.</p>
                    <a href="#" class="read-more">Khám phá ngay →</a>
                </div>
                <div class="eco-card">
                    <div class="eco-icon orange">💻</div>
                    <h3>Software</h3>
                    <p>Phần mềm bản quyền hỗ trợ thiết kế, lập trình và quản lý doanh nghiệp.</p>
                    <a href="#" class="read-more">Khám phá ngay →</a>
                </div>
                <div class="eco-card">
                    <div class="eco-icon cyan">👑</div>
                    <h3>Membership</h3>
                    <p>Đăng ký thành viên để sở hữu toàn bộ kho tài nguyên không giới hạn.</p>
                    <a href="#" class="read-more">Khám phá ngay →</a>
                </div>
            </div>
        </section>

        <!-- Featured Products -->
        <section class="featured">
            <div class="container">
                <h2>Sản phẩm nổi bật</h2>
                <div class="products-grid">
                    <?php 
                    $demo_products = [
                        ['title' => 'Tokoo Theme', 'tag' => 'Theme', 'price' => '$59.00', 'img' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&q=80&w=300'],
                        ['title' => 'Elementor Pro', 'tag' => 'Plugin', 'price' => '$9.00', 'img' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=300'],
                        ['title' => 'Adobe Creative Cloud', 'tag' => 'Software', 'price' => '$15.00', 'img' => 'https://images.unsplash.com/photo-1626785774573-4b799315345d?auto=format&fit=crop&q=80&w=300'],
                        ['title' => 'Rank Math SEO Pro', 'tag' => 'Plugin', 'price' => '$5.00', 'img' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=300']
                    ];
                    foreach($demo_products as $p): 
                    ?>
                    <div class="product-card">
                        <div class="product-thumb">
                            <img src="<?php echo $p['img']; ?>" alt="">
                        </div>
                        <div class="product-info">
                            <span class="product-tag"><?php echo $p['tag']; ?></span>
                            <h3><?php echo $p['title']; ?></h3>
                            <div class="product-footer">
                                <span class="product-price"><?php echo $p['price']; ?></span>
                                <button style="background: #f1f5f9; border: none; padding: 4px; border-radius: 4px; cursor: pointer;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section class="pricing container">
            <div class="pricing-header">
                <p style="color: var(--secondary); font-weight: 700; margin-bottom: 10px;">LỰA CHỌN PHÙ HỢP CHO BẠN</p>
                <h2 style="font-size: 36px; font-weight: 800;">Hiệu năng cao, Tốc độ vượt trội</h2>
            </div>
            <div class="pricing-grid">
                <div class="price-card">
                    <div class="price-name">Starter</div>
                    <div class="price-amt">$3.99 <span>/ tháng</span></div>
                    <ul class="price-features">
                        <li>✅ 01 Website</li>
                        <li>✅ 10GB SSD Storage</li>
                        <li>✅ Unlimited Bandwidth</li>
                        <li>❌ No Priority Support</li>
                    </ul>
                    <a href="#" class="btn-price">Đăng ký ngay</a>
                </div>
                <div class="price-card featured-plan">
                    <div class="price-name">Pro</div>
                    <div class="price-amt">$9.99 <span>/ tháng</span></div>
                    <ul class="price-features">
                        <li>✅ 10 Website</li>
                        <li>✅ 50GB SSD Storage</li>
                        <li>✅ Priority Support</li>
                        <li>✅ Advanced Analytics</li>
                    </ul>
                    <a href="#" class="btn-price">Bắt đầu ngay</a>
                </div>
                <div class="price-card">
                    <div class="price-name">Business</div>
                    <div class="price-amt">$24.99 <span>/ tháng</span></div>
                    <ul class="price-features">
                        <li>✅ Unlimited Website</li>
                        <li>✅ 200GB SSD Storage</li>
                        <li>✅ 24/7 Support</li>
                        <li>✅ Managed Service</li>
                    </ul>
                    <a href="#" class="btn-price">Liên hệ ngay</a>
                </div>
            </div>
        </section>

        <!-- CTA Banner -->
        <section class="cta-banner container">
            <div class="cta-inner">
                <div class="cta-content">
                    <p style="text-transform: uppercase; font-weight: 700; margin-bottom: 20px;">CHƯƠNG TRÌNH ĐẶC BIỆT</p>
                    <h2>Mở khóa quyền truy cập Không giới hạn.</h2>
                    <p>Chỉ với $29.99/tháng, bạn có thể tải toàn bộ kho tài nguyên hơn 5.000+ sản phẩm liên tục được cập nhật.</p>
                    <a href="#" class="btn-white">Đăng ký thành viên ngay</a>
                </div>
                <div class="cta-vignette"></div>
            </div>
        </section>

        <!-- Highlights -->
        <section class="highlights container">
            <div class="highlights-grid">
                <div class="highlight-item">
                    <div class="highlight-icon">⚡</div>
                    <b>Hiệu năng cao</b>
                    <p>Tối ưu tốc độ tải trang</p>
                </div>
                <div class="highlight-item">
                    <div class="highlight-icon">🛡️</div>
                    <b>Bảo mật tuyệt đối</b>
                    <p>Mã hóa dữ liệu 256-bit</p>
                </div>
                <div class="highlight-item">
                    <div class="highlight-icon">💬</div>
                    <b>Hỗ trợ 24/7</b>
                    <p>Đội ngũ hỗ trợ chuyên nghiệp</p>
                </div>
                <div class="highlight-item">
                    <div class="highlight-icon">🔄</div>
                    <b>Cập nhật liên tục</b>
                    <p>Dữ liệu được làm mới hàng ngày</p>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container footer-grid">
            <div class="footer-col">
                <div class="logo" style="color: white; margin-bottom: 20px;">
                    Digital<span>Core.</span>
                </div>
                <p>Nền tảng cung cấp giải pháp tài nguyên số hàng đầu dành cho các nhà phát triển và doanh nghiệp sáng tạo.</p>
                <div class="socials" style="display: flex; gap: 10px;">
                    <!-- Social icons here -->
                    <div style="width: 32px; height: 32px; background: #1e293b; border-radius: 50%;"></div>
                    <div style="width: 32px; height: 32px; background: #1e293b; border-radius: 50%;"></div>
                    <div style="width: 32px; height: 32px; background: #1e293b; border-radius: 50%;"></div>
                </div>
            </div>
            <div class="footer-col">
                <h4>Sản phẩm</h4>
                <ul>
                    <li><a href="#">Tên miền</a></li>
                    <li><a href="#">Themes</a></li>
                    <li><a href="#">Plugins</a></li>
                    <li><a href="#">Softwares</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Công ty</h4>
                <ul>
                    <li><a href="#">Về chúng tôi</a></li>
                    <li><a href="#">Tuyển dụng</a></li>
                    <li><a href="#">Liên hệ</a></li>
                    <li><a href="#">Bảo mật</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Đăng ký nhận tin</h4>
                <p>Nhận ngay thông tin về các sản phẩm và khuyến mãi mới nhất.</p>
                <div class="newsletter">
                    <input type="email" placeholder="Email của bạn">
                    <button>Gửi</button>
                </div>
            </div>
        </div>
        <div class="container footer-bottom">
            <div>© 2024 DigitalCore. Tất cả quyền được bảo lưu.</div>
            <div style="display: flex; gap: 20px;">
                <a href="#">Điều khoản sử dụng</a>
                <a href="#">Chính sách bảo mật</a>
            </div>
        </div>
    </footer>
</body>
</html>
