# Implementation Plan — Make the block-rendering pipeline throw on unresolvable blocks

## Context / key findings (grounded in code read during exploration)

- Active render path (the failing BookPress subsite page):
  `BookPressContextLoader::renderSubsite()` → `$this->baseLoader->renderTemplate($templatePath, $data)`
  in `framework/presto/Context/Loader.php`. When `$this->blockRenderer !== null`
  (the gutenberg-core path, active here) it does:
  `file_get_contents` → `$parser->parse($content)` → `$this->blockRenderer->render($blocks, ...)` and
  returns the HTML with **no failure check and no try/catch**.

- `content/themes/timeless/templates/home.html` parses to four blocks. The parser
  (`PrestoWorld\Gutenberg\Parser\BlockSerializer`, tokenizer in `nextToken()`) emits
  fully-qualified names: `core/template-part{header}`, `core/group` (the `<main>` wrapper,
  containing `core/pattern{timeless/hidden-blog-heading}` and
  `core/pattern{timeless/template-query-loop}`), `core/template-part{footer}`.
  `BlockTypeRegistry::get()` normalizes via `BlockName::from()->full()`, so all four resolve
  to *registered* block types (blockType !== null). `core/group` is a *static* block
  (no render callback, `isDynamic()` false); `core/template-part` and `core/pattern` are
  *dynamic* (handlers registered in `Registrar::HANDLERS`).

- **Where unresolvable blocks silently produce empty output today:**
  1. `TemplatePartHandler::render()` (`vendor/prestoworld/gutenberg-core/src/Core/Blocks/TemplatePartHandler.php`)
     wraps `$repository->part($slug)` in `try { ... } catch (TemplateNotFoundException) { return $content; }`.
     `TemplateRepository::part($slug)` throws `TemplateNotFoundException::named('part:'.$slug, ...)`
     when `ThemeFileTemplateSource::findPart($slug)` returns null (no `parts/<slug>.html`).
     The catch swallows it and returns `$content`, which is `''` for a void/self-closing
     template-part → header/footer render to nothing.
  2. `PatternHandler::render()` (`vendor/prestoworld/gutenberg-core/src/Core/Blocks/PatternHandler.php`)
     does `$pattern = $registry->get($slug); if ($pattern === null) { return ''; }`.
     `PatternRegistry::get($slug)` returns null when no `patterns/*.php` declared that slug
     (patterns are discovered by `ThemeFilePatternSource::discover()` from the kernel's themeDir)
     → the two patterns render to nothing.
  3. `BlockRenderer::renderBlock()` (`vendor/prestoworld/gutenberg-core/src/Rendering/BlockRenderer.php`)
     does **not** throw when `$block->blockType === null` (an unknown/unregistered block type);
     `$isDynamic` is false so the dynamic callback is skipped and the (empty) inner content is
     returned. Not hit by home.html's blocks (they are all registered), but the task requires this
     distinct failure category to throw too.

- **Why the real subsite page goes blank:** the gutenberg `Kernel` is a singleton built once
  (`framework/presto/Context/Builder/Module.php`, `Gutenberg::boot($this->resolveThemePath(), ...)`),
  so its `TemplateRepository` and `PatternRegistry` are keyed to that one themeDir.
  `BookPressContextLoader::renderSubsite()` calls `setThemeDir($themeDir)` on the *loader*
  (which only affects `home.html` file resolution), not on the kernel's repositories. When the
  resolved theme dir lacks the referenced `parts/header.html`, `parts/footer.html`, and the
  `patterns/` files (e.g. `PW_THEME_DIR` points at such a dir), the parts/patterns vanish while
  `core/group` still renders its `<main>` wrapper → the observed empty `<main>`.
  (The coder's snippet/repo will confirm exactly which artifacts are missing.)

- **Exception types already exist** in `PrestoWorld\Gutenberg\Exceptions` (all extend
  `GutenbergException` ⊂ `\RuntimeException`):
  `BlockNotRegisteredException::forName($name)`, `TemplateNotFoundException::named($name, $dir)`,
  `UnhandledBlockException`. **No new exception class is needed — reuse these.**

## Decisions

- **(a) Unknown/unregistered block type (no handler):** throw
  `BlockNotRegisteredException::forName($block->name())` in `BlockRenderer::renderBlock()`,
  placed **after** the `pre_render_block` filter short-circuit (so a filter can still rescue an
  unregistered block, matching core). Rationale: it is the existing exception built for exactly
  this ("asked the registry for a block that was never registered").
- **(b1) Unresolvable core/template-part slug:** let `TemplateNotFoundException` from
  `TemplateRepository::part($slug)` propagate — remove the `try/catch` in
  `TemplatePartHandler::render()`. Rationale: a non-empty slug that resolves to no part file is a
  genuine failure; the exception already carries `part:<slug>` + the reason.
- **(b2) Unresolvable core/pattern slug:** throw `TemplateNotFoundException::named('pattern:'.$slug, ...)`
  in `PatternHandler::render()`. Rationale: it represents "a referenced theme artifact
  (template/part/pattern) could not be resolved," is consistent with how `TemplateRepository::part()`
  throws `TemplateNotFoundException::named('part:'.$slug, ...)`, and avoids inventing a new class.
- **Do NOT throw for** legitimately-empty-by-design output: freeform text (blockName === null,
  emitted directly in `render()` before `renderBlock` is ever called); registered blocks whose
  handler legitimately returns `''` (e.g. a query with no results) — those have blockType !== null so
  the (a) throw never fires; and an inline/unsaved template-part (slug === '') which renders inner
  content (already handled before the repository lookup).

## Propagation audit (no code change needed — confirmed by reading the chain)

- `Loader::renderTemplate()` blockRenderer path: no try/catch around `$this->blockRenderer->render(...)`.
- `BlockRenderer::renderBlock()`: the dynamic-callback call is `try { $block->blockType->render(...) } finally { Gutenberg::setCurrentBlock($block->parent); }` — the `finally` resets state but does **not** swallow.
- `BookPressContextLoader::renderSubsite()`: no try/catch around `renderTemplate(...)`.
- `SubsiteController::handle()` / `renderPage()`: no try/catch. The exception propagates to the
  framework dispatcher, which surfaces uncaught exceptions (proven by the original `RuntimeException`
  logged with a full stack trace in the bug report). **No catch-all swallows the new throw.**
- The coder should re-confirm these four spots are still catch-free before finishing.

## Happy-path preservation

- `core/group` and other registered static blocks: blockType !== null → the (a) throw does not
  fire; they render inner content as before. Registered dynamic blocks whose handler returns `''` for
  a valid state keep doing so (blockType !== null).
- Public API signatures are unchanged: `renderTemplate(string, array = []): string`,
  `BlockRenderer::render(array, array = []): string`, `renderBlock(BlockInstance, array = []): string`,
  and handler `render(array, string, BlockInstance): string`. `tests/Unit/Services/ContentRendererTest.php`
  mocks `renderTemplate` and is unaffected.
- `phpunit.xml` includes only `tests/`, `tests/Feature`, and module `tests/` dirs — it does **not**
  run `vendor/prestoworld/gutenberg-core`'s own suite, so editing the vendor handlers adds no project-test risk.

## Known, intended behavior change (flag, do not "fix")

Throwing for `blockType === null` is a deliberate "fail loud" change: any template that currently
relies on an unregistered block silently rendering as static inner markup will now raise
`BlockNotRegisteredException`. This is exactly what the task asks for; the coder should mention it
in the report but not add fallbacks for it.

---

- [ ] 1. Make `BlockRenderer::renderBlock()` throw for an unregistered block type.
      In `renderBlock()`, after Stage 1 (the `pre_render_block` filter short-circuit
      `if ($preRender !== null) { return (string) $preRender; }`) and before Stage 2
      (the `render_block_data` filter), add:
      `if ($block->blockType === null) { throw BlockNotRegisteredException::forName($block->name()); }`
      (renderBlock is only reached for blocks with a non-null blockName — freeform text with
      blockName === null is emitted directly in `render()` — so `name()` is non-empty here.)
      Add `use PrestoWorld\Gutenberg\Exceptions\BlockNotRegisteredException;` to the file's imports.
      Files: /Users/puleeno/Projects/bookowl.org/vendor/prestoworld/gutenberg-core/src/Rendering/BlockRenderer.php
      Verify: `php -l /Users/puleeno/Projects/bookowl.org/vendor/prestoworld/gutenberg-core/src/Rendering/BlockRenderer.php` → "No syntax errors detected".

- [ ] 2. Stop swallowing `TemplateNotFoundException` in `TemplatePartHandler::render()`.
      Replace the `try { $part = $repository->part($slug); } catch (TemplateNotFoundException) { return $content; }`
      block with a plain `$part = $repository->part($slug);` so a non-empty slug that resolves to no
      part file propagates `TemplateNotFoundException::named('part:'.$slug, ...)`. Keep the earlier
      `$slug === ''` early-return (inline/unsaved part renders inner content) and the `$repository === null`
      throw unchanged. `TemplateNotFoundException` is already imported in this file.
      Files: /Users/puleeno/Projects/bookowl.org/vendor/prestoworld/gutenberg-core/src/Core/Blocks/TemplatePartHandler.php
      Verify: `php -l /Users/puleeno/Projects/bookowl.org/vendor/prestoworld/gutenberg-core/src/Core/Blocks/TemplatePartHandler.php` → "No syntax errors detected".

- [ ] 3. Throw for an unregistered pattern slug in `PatternHandler::render()`.
      Replace `if ($pattern === null) { return ''; }` with
      `if ($pattern === null) { throw TemplateNotFoundException::named('pattern:'.$slug, '(pattern not registered in the theme; no patterns/*.php declares this slug)'); }`.
      Add `use PrestoWorld\Gutenberg\Exceptions\TemplateNotFoundException;` to the file's imports
      (currently only `BlockInstance` is imported). Keep the `$slug === ''` early-return and the
      `$registry === null || $parser === null` throw unchanged.
      Files: /Users/puleeno/Projects/bookowl.org/vendor/prestoworld/gutenberg-core/src/Core/Blocks/PatternHandler.php
      Verify: `php -l /Users/puleeno/Projects/bookowl.org/vendor/prestoworld/gutenberg-core/src/Core/Blocks/PatternHandler.php` → "No syntax errors detected".

- [ ] 4. (Doc-only) Document the propagated exceptions on `Loader::renderTemplate()`.
      In `framework/presto/Context/Loader.php`, extend the `renderTemplate()` `@throws` docblock
      (currently `@throws UnsupportedBlockException`) to note the blockRenderer path also propagates
      `PrestoWorld\Gutenberg\Exceptions\BlockNotRegisteredException` and
  `TemplateNotFoundException`. No signature or behavior change — keeps the API docs honest.
      Files: /Users/puleeno/Projects/bookowl.org/framework/presto/Context/Loader.php
      Verify: `php -l /Users/puleeno/Projects/bookowl.org/framework/presto/Context/Loader.php` → "No syntax errors detected".

- [ ] 5. Verify the fix end to end and report findings.
      (a) Run `php -l` on all four changed files (BlockRenderer.php, TemplatePartHandler.php,
      PatternHandler.php, Loader.php) — each must report no syntax errors.
      (b) From the project root run
      `./vendor/bin/phpunit tests/Unit/Services/ContentRendererTest.php`.
      Expected: the suite's **4 pre-existing** failures caused by the unrelated
      `PrestoWorld\Modules\ClassicTheme\*` autoloader mismatch (classes live at
      `framework/presto/Theme/Legacy/`) remain — and **no new failures** are introduced.
      (Optional extra: `./vendor/bin/phpunit --testsuite BookPress` to confirm no BookPress
      regression; the grep for BlockRenderer/renderSubsite/handlers in `tests/` returned nothing, so
      this suite is not expected to exercise the real pipeline.)
      (c) Best-effort runtime proof with a self-contained snippet (run with `php`, clean up after):
      create a temp theme dir containing **only** `templates/home.html` (copied from the real
      `content/themes/timeless/templates/home.html`) and **no** `parts/` or `patterns/` dir; then
      `require __DIR__ . '/vendor/autoload.php'; \PrestoWorld\Gutenberg\Gutenberg::reset();`
      `$kernel = \PrestoWorld\Gutenberg\Gutenberg::boot($tmpDir, []);`
      `$loader = new \Presto\Context\Loader(new \PrestoWorld\Modules\ContextBuilder\BlockRegistry(), $storage, $tmpDir);`
      `$loader->setBlockRenderer($kernel->renderer()); $loader->setBlockParser($kernel->parser());`
      and call `$loader->renderTemplate($tmpDir . '/templates/home.html', [])` inside a
      `try/catch (\Throwable $e)`. The Kernel uses `InternalHooks` and a pure-static `ThemeContext`
      when WP functions are absent, so it boots standalone; if a missing dependency prevents the
      full pipeline, state that clearly and verify by inspection instead. Expected after the fix:
      it **throws** (the first block, `core/template-part{header}`, raises
      `TemplateNotFoundException` naming `part:header`) rather than returning the empty `<main>`
      wrapper. Assert the message mentions `header`/`part`. Clean up the temp dirs.
      Files: snippet lives outside the repo (e.g. `/tmp/verify-throw.php`); delete it after.
      Verify: `php /tmp/verify-throw.php` prints `THREW ...TemplateNotFoundException... part:header...`
      and exits 0; before the fix it printed the empty-wrapper byte count and exited non-zero.

---

## VERIFICATION RESULTS (recorded by the coder)

All checklist items 1–4 implemented; item 5 verified as follows.

### (a) `php -l` on every changed file — PASS
```
No syntax errors detected in .../gutenberg-core/src/Rendering/BlockRenderer.php
No syntax errors detected in .../gutenberg-core/src/Core/Blocks/TemplatePartHandler.php
No syntax errors detected in .../gutenberg-core/src/Core/Blocks/PatternHandler.php
No syntax errors detected in framework/presto/Context/Loader.php
```

### (b) `./vendor/bin/phpunit tests/Unit/Services/ContentRendererTest.php` — no new failures
Result with the fix in place: **5 tests, 4 errors, 1 pass**. The 4 errors are the known
pre-existing `PrestoWorld\Modules\ClassicTheme\*` autoloader mismatch
(`ThemeDetector` / `ClassicThemeEngine` not found in `app/Services/ContentRenderer.php`),
unrelated to this change. Proven pre-existing by reverting the change set (stash) and
re-running: identical **4 errors / 1 pass** with the fix reverted. **No new failures introduced.**
`ContentRendererTest` mocks `renderTemplate`, so the renderer/handler edits never run in it.

### (c) Runtime proof (self-contained snippet against the real home.html) — PASS
Built a temp theme dir containing only `templates/home.html` (copied from
`content/themes/timeless/templates/home.html`) and no `parts/` or `patterns/` dir, then
booted the gutenberg-core `Kernel` (with `InternalHooks`, faithful to the app where no
`pre_render_block` filter is registered) and rendered through `Presto\Context\Loader`.

**BEFORE the fix (3 vendor files reverted via stash):**
```
NO THROW — returned HTML (length=98):
<main class="wp-block-group" style="margin-top:var(--wp--preset--spacing--60)">
</main>
RESULT: FAIL — silently produced empty output (the bug)
```
This is exactly the user's reported symptom (the empty `<main>` wrapper).

**AFTER the fix (restored):**
```
THREW PrestoWorld\Gutenberg\Exceptions\TemplateNotFoundException ::
  Block theme template "part:header" not found in "(theme has no matching parts/*.html)".
RESULT: PASS — block pipeline surfaced the failure
```
The first block, `core/template-part{slug:header}`, now raises a clear exception instead of
rendering to nothing. Snippet deleted after running.

### Propagation re-audit (confirming no catch-all swallows the throw)
- `Loader::renderTemplate()` blockRenderer path: no try/catch around `$this->blockRenderer->render(...)` — propagates.
- `BlockRenderer::renderBlock()` Stage 7 `try { ... } finally { ... }`: `finally` only resets
  `Gutenberg::setCurrentBlock`; it does **not** catch — propagates.
- `BookPressContextLoader::renderSubsite()`: no try/catch around `renderTemplate()` — propagates.
- `SubsiteController::renderPage()` / `handle()`: no try/catch — propagates.
- `RouteRegistry::runAction()` / `dispatch()` / `runMiddlewarePipeline()`: no catch — propagates.
- `Witals\Framework\Application::handle()`: no catch around `$registry->dispatch()` — propagates.
- `TraditionalServer::start()`: no catch around `$this->app->handle($request)` — surfaces to PHP
  (logged as an error like the original `RuntimeException` in the bug report, not a blank page).
- `App\Http\Kernel` catches `App\Exceptions\TemplateNotFoundException` (a *different* class from
  `PrestoWorld\Gutenberg\Exceptions\TemplateNotFoundException`), and the BookPress subsite route
  bypasses `PageService`/`Kernel` entirely, so nothing converts the throw back to a blank page.

### Files changed
- `vendor/prestoworld/gutenberg-core/src/Rendering/BlockRenderer.php` — throw `BlockNotRegisteredException::forName($block->name())` when `blockType === null` (after the `pre_render_block` short-circuit, before the data filter).
- `vendor/prestoworld/gutenberg-core/src/Core/Blocks/TemplatePartHandler.php` — removed the `try/catch (TemplateNotFoundException)` so `$repository->part($slug)` propagates instead of falling back to empty inner content.
- `vendor/prestoworld/gutenberg-core/src/Core/Blocks/PatternHandler.php` — `if ($pattern === null)` now throws `TemplateNotFoundException::named('pattern:'.$slug, ...)` instead of returning `''`.
- `framework/presto/Context/Loader.php` — docblock-only: extended `renderTemplate()` `@throws` to document the propagated gutenberg exceptions.

---

## Phase 2 — Deeper root cause: re-key the kernel singleton per subsite

The throw (Phase 1) converts the symptom from **blank page** → **clear error**
(`TemplateNotFoundException` naming `part:header`). It does **not** fix the
cause: the gutenberg `Kernel` is a singleton booted once
(`framework/presto/Context/Builder/Module.php:54`, `Gutenberg::boot(...)`) with a
fixed `themeDir`, so its `TemplateRepository` and `PatternRegistry` are keyed to
that one dir. `BookPressContextLoader::renderSubsite()` called `setThemeDir()`
only on the *loader* (which affects `home.html` file resolution), not on the
kernel's repositories. When the resolved subsite theme dir lacks
`parts/header.html`, `parts/footer.html` and the `patterns/` files, those resolve
to nothing while `core/group` still renders its `<main>` wrapper.

### Changes
- `vendor/prestoworld/gutenberg-core/src/Kernel/Kernel.php` — add
  `setThemeDir(string $themeDir): void`. Rebuilds `TemplateRepository` and
  `PatternRegistry` from the new themeDir and re-points the shared
  `RenderContext` references (`template_repository`, `pattern_registry`) so
  template-part/pattern blocks resolve against the active theme. `themeDir`
  changed from `readonly` to mutable. No-op when the dir is unchanged.
- `modules/BookPress/ContextLoader/BookPressContextLoader.php` — accept an
  optional `?Kernel $kernel` and call `$this->kernel->setThemeDir($themeDir)`
  alongside the existing `$this->baseLoader->setThemeDir($themeDir)`.
- `modules/BookPress/Module.php` — resolve `Kernel::class` from the container
  (null-safe when context-builder is not loaded) and pass it into the
  `BookPressContextLoader` constructor.

### Verification
- `php -l` on all three changed files — no syntax errors.
- `./vendor/bin/phpunit tests/Unit/Services/ContentRendererTest.php` — 5 tests,
  4 errors, 1 pass. The 4 errors are the known pre-existing
  `PrestoWorld.Modules\ClassicTheme\*` autoloader mismatch; identical with the
  change stashed. No new failures.
- `./vendor/bin/phpunit --testsuite BookPress` — 58 tests, 213 assertions,
  1 failure (`SiteCatalogControllerTest::test_unauthenticated_gets_401`
  expects 401, gets 302). Proven pre-existing by stashing the change set and
  re-running: identical failure. No new failures introduced.
- **Runtime proof (self-contained snippet, `php`, cleaned up after):**
  Boot the kernel on a dir containing only `templates/home.html` (no `parts/`,
  no `patterns/`) — render **throws**
  `TemplateNotFoundException :: Block theme template "part:header" not found in
  "(theme has no matching parts/*.html)"` (Phase 1 behaviour). Then call
  `$kernel->setThemeDir($subsiteDir)` where `$subsiteDir` has
  `parts/header.html`, `parts/footer.html` and a `patterns/heading.php` —
  render **succeeds** (length=148, HEADER=yes, `<main`=yes, FOOTER=yes).
  Result: re-keying fixes the themeDir mismatch; the page renders instead of
  blanking. Snippet deleted after running.

### Files changed (Phase 2)
- `vendor/prestoworld/gutenberg-core/src/Kernel/Kernel.php` — `setThemeDir()`.
- `modules/BookPress/ContextLoader/BookPressContextLoader.php` — optional
  `?Kernel` injection + re-key call.
- `modules/BookPress/Module.php` — wire `Kernel::class` into the loader DI.

> Note: `vendor/` is gitignored, so the `Kernel.php` change is **not** tracked.
> If the vendor dir is ever reinstalled (composer install), the Phase 1 vendor
> edits (BlockRenderer/TemplatePartHandler/PatternHandler) and this Phase 2
> `Kernel::setThemeDir` must be re-applied — they are recorded here.

---

## Phase 3 — Wrap template-file output in the template-canvas shell

### Symptom
After Phase 2 the page rendered (parts/patterns resolved), but the HTML was
**not wrapped** in the WordPress-style document shell
(`<!DOCTYPE html><html><head><body>`). Only the raw block markup came back.

### Root cause
`renderSubsite()` has three branches:
1. Storage/theme layout → `$this->baseLoader->render(...)` which calls
   `renderCanvas()` → includes `template-canvas.php` → **wrapped**.
2. Template file → `$this->baseLoader->renderTemplate(...)` which returns the
   raw block HTML from `BlockRenderer::render()` → **unwrapped**.
3. Final fallback → `ob_start(); include $canvasPath;` → **wrapped**.

Branch 2 was the odd one out: `renderTemplate()` deliberately returns only the
inner markup (the canvas is the parent's job), but `renderSubsite()` consumed
that raw string directly without wrapping it.

### Change
`modules/BookPress/ContextLoader/BookPressContextLoader.php` — add
`wrapWithCanvas(string $html, string $subsiteId, array $data): string` which
includes the sub-site's `template-canvas.php` (resolved by the existing
`resolveSubsiteCanvas()`) with the rendered markup exposed as `$BP_CONTENT`,
exactly as branch 3 does. Call it on branch-2 output before placeholder
substitution.

### Verification
- `php -l` clean.
- `./vendor/bin/phpunit --testsuite BookPress` — 58 tests, 213 assertions,
  1 failure (`SiteCatalogControllerTest::test_unauthenticated_gets_401`,
  302 vs 401). Proven pre-existing by stashing. No new failures.
- **Runtime proof:** a self-contained theme dir with `home.html` +
  `parts/header.html`, `parts/footer.html`, `patterns/heading.php`, rendered
  through the real `BookPressContextLoader::renderSubsite()` (boot dir =
  subsite dir, kernel re-keyed). Result: length=264, `<!DOCTYPE`=yes,
  `<html`/`<head`/`<body`=yes, HEADER=`<main`=yes, FOOTER=yes. Snippet deleted
  after running.

### Files changed (Phase 3)
- `modules/BookPress/ContextLoader/BookPressContextLoader.php` — `wrapWithCanvas()`
  + branch-2 call.
