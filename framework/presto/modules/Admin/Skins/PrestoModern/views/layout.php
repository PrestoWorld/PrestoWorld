<div class="presto-admin-layout">
    <aside class="presto-sidebar">
        <div class="presto-logo">
            <img src="/presto-logo.svg" alt="PrestoWorld">
        </div>
        <nav class="presto-nav">
            <!-- Nav items -->
        </nav>
    </aside>
    <main class="presto-main">
        <header class="presto-header">
            <h1><?php echo $title ?? ''; ?></h1>
            <div class="header-actions">
                <!-- User profile, notifications -->
            </div>
        </header>
        <section class="presto-content">
            <?php echo $content; ?>
        </section>
    </main>
</div>
