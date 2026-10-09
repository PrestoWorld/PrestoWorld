<?php
/**
 * Presto Modern Admin Layout
 *
 * @var string $title
 * @var string $content
 * @var string $adminBar
 * @var array  $user
 */
?>
<div class="presto-admin-layout">
    <?php if (!empty($adminBar)): ?>
        <?= $adminBar ?>
    <?php endif; ?>

    <div class="presto-admin-body">
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
</div>
