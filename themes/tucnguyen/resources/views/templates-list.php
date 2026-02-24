<?php include __DIR__ . '/parts/header.php'; ?>

<style>
    .templates-hero {
        background: radial-gradient(circle at top right, rgba(59, 130, 246, 0.05), transparent), #fff;
        padding: 80px 0;
        text-align: center;
        border-bottom: 1px solid #f1f5f9;
    }
    .templates-hero h1 { font-size: 48px; font-weight: 800; margin-bottom: 20px; }
    .templates-hero p { color: var(--gray); font-size: 18px; max-width: 700px; margin: 0 auto; }

    .templates-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        padding: 60px 0;
    }

    .tp-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        transition: 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    }
    .tp-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 30px 60px rgba(0,0,0,0.08);
    }

    .tp-thumb {
        height: 240px;
        position: relative;
        overflow: hidden;
    }
    .tp-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .tp-badge {
        position: absolute;
        top: 15px;
        left: 15px;
        background: var(--primary);
        color: white;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .tp-info { padding: 25px; }
    .tp-info h3 { font-size: 20px; font-weight: 800; margin-bottom: 10px; color: var(--dark); }
    .tp-info p { font-size: 14px; color: var(--gray); line-height: 1.6; margin-bottom: 20px; height: 45px; overflow: hidden; }
    
    .tp-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 20px;
        border-top: 1px solid #f8fafc;
    }
    .tp-price { font-size: 22px; font-weight: 800; color: var(--primary); }
    .btn-buy-tp {
        background: var(--dark);
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
        transition: 0.3s;
    }
    .btn-buy-tp:hover { background: var(--primary); }

    @media (max-width: 900px) {
        .templates-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .templates-grid { grid-template-columns: 1fr; }
    }
</style>

<main>
    <section class="templates-hero">
        <div class="container text-center">
            <div class="hero-badge" style="margin: 0 auto 20px;">🏠 Kho Giao Diện Có Sẵn</div>
            <h1>Chọn <span class="gradient-text">Website Mẫu</span> Của Bạn</h1>
            <p>Hàng trăm website thuộc mọi lĩnh vực đã được tối ưu sẵn SEO và tốc độ, chỉ việc cài đặt và sử dụng ngay.</p>
        </div>
    </section>

    <div class="container">
        <div class="templates-grid">
            <?php foreach($templates as $tp): ?>
            <div class="tp-card">
                <div class="tp-thumb">
                    <img src="<?php echo $tp['image_url']; ?>" alt="<?php echo $tp['name']; ?>">
                    <div class="tp-badge"><?php echo $tp['category']; ?></div>
                </div>
                <div class="tp-info">
                    <h3><?php echo $tp['name']; ?></h3>
                    <p><?php echo $tp['description']; ?></p>
                    <div class="tp-footer">
                        <div class="tp-price">$<?php echo number_format($tp['price'], 2); ?></div>
                        <a href="/checkout/template/<?php echo $tp['slug']; ?>" class="btn-buy-tp">Mua Ngay</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/parts/footer.php'; ?>
