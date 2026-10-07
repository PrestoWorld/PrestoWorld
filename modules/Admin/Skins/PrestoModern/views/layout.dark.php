<div class="presto-admin-layout">
    <aside class="presto-sidebar">
        <div class="presto-logo">
            <img src="/presto-logo.svg" alt="PrestoWorld">
        </div>
        <nav class="presto-nav">
            <stack:collect name="admin-menu"/>
        </nav>
    </aside>
    <main class="presto-main">
        <header class="presto-header">
            <h1>${title}</h1>
            <div class="header-actions">
                <stack:collect name="header-actions"/>
            </div>
        </header>
        <section class="presto-content">
            ${context}
        </section>
    </main>
</div>
