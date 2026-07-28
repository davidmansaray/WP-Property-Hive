# Frontend Settings Redesign — Implementation Plan

Branch: `feature/frontend-settings-redesign` (from `feature/property-template-system`)
Mockups: `.design/frontend-settings-redesign/*.png`

## Status — implemented and verified in browser (17 Jul 2026)
First pass reviewed against the mocks and revised: the page now renders as the
dashboard-style view in `visual-editor-settings.png` (icon nav, breadcrumb
sub-nav, header band with Save pill + help button, chooser in a white shell
card, contextual cards swapping per mode). Verified live at
propertyhive.local: AJAX mode select persists; Page Builder mode shows the
Elementor-detected panel (v4.1.3) and stands the template set down on the
front end; Visual mode re-activates it; header Save preserves the enabled
flag (sanitizer now preserves `template_set_enabled` when absent from POST).

Second-pass revisions on top of the original build:
- Page header band on all tabs (title/description from tab meta, Save pill,
  cancel where applicable, help button); view buffers tab output so
  `$hide_save_button` set during rendering still controls the header.
- Sections sub-nav restyled as breadcrumb, placed between nav and header.
- Chooser wrapped in `.ph-tx-shell` white card; panels restructured to match
  mocks (visual = two cards; builder = one card, 3 divided columns incl.
  Helpful resources; developer = one card, 2 columns).
- Legacy table: Enable + Editing Experience rows removed (owned by chooser);
  remainder wrapped in a collapsed `<details>` "Advanced template settings"
  card, hidden in Visual Editor/Page Builder modes and available only in
  Developer Mode.
- Add-on tabs fall back to a neutral puzzle icon (was error-style icon).

Remaining deltas vs mock (agreed): "Place" section deferred (Flags shows in
its slot); nav wraps to two rows on
installs with many add-on tabs.

### Nav restyle + add-on handling evaluation (17 Jul 2026)
Nav rebuilt to match the mock exactly: bare icon glyphs (no chip squares),
amber icon on the active tab, vertical divider lines between items, tabs
edge-to-edge vertically with the tint touching container top/bottom, yellow
bar on the container's bottom edge, tabs distributed across the full width.
Added `<hr class="wp-header-end">` so WP core notice-relocation no longer
splits the header band.

Add-on tab handling: six variants were built and evaluated side-by-side via a
temporary switcher (Dropdown / Second row / Compact / Scroll / Expandable /
Icons only). **Decision: Dropdown.** The switcher and losing variants were
removed. Final behaviour:
- Core tabs in one row; add-on tabs live in an "Add-ons (n)" dropdown with a
  count pill, puzzle icon, and outside-click close (aria-expanded/haspopup).
- When the active tab IS an add-on, it renders inline as the visible active
  tab and the Add-ons toggle collapses to a compact icon+count chip so the
  nav stays on one row.
- Only the active add-on is rendered inline (no hidden dead DOM).

### Icon fidelity pass (17 Jul 2026)
All SVGs redrawn against magnified crops of the mock PNGs (crops in scratchpad
via sips). Nav set (1.6 stroke): scalloped six-lobe gear, twin office blocks
with window dots, three slider rows with round knobs, browser-window Frontend,
envelope, star, diagonal wrench (License), three-layer stack (Demo Data),
puzzle fallback for add-on tabs. Card set (bold): layout, Elementor-style
bars glyph, 2.2-stroke `</>`. Panels: solid 45° paintbrush, right-nub/
bottom-notch puzzle, Elementor brand roundel (#92003B, shown when Elementor is
the detected builder), doc-with-folded-corner, `</>`-in-box, folder, GitHub
octocat, yellow lightbulb hint icon. All verified in-browser against the mocks
across the three modes.

Files changed/added:
- `includes/class-ph-template-set.php` — new mode constants, `get_editor_mode()`,
  `editor_mode_stands_down()`, `detect_page_builder()`, experience AJAX handler.
- `includes/template-set/class-ph-template-set-page-builders.php` — **new** detector.
- `includes/template-set/class-ph-template-set-options.php` — 4 editor modes.
- `includes/template-set/class-ph-template-set-request-context.php` — stand-down guard.
- `includes/admin/settings/class-ph-settings-frontend.php` — card chooser field + panels.
- `includes/admin/class-ph-admin-settings.php` — tab meta + icon SVG helpers.
- `includes/admin/views/html-admin-settings.php` — icon nav bar markup.
- `includes/admin/class-ph-admin-assets.php` — enqueue redesign CSS.
- `assets/css/admin-frontend-redesign.css` — **new** styles (nav bar + chooser).

## Goal
Redesign **Settings → Frontend** around a card-based chooser — *"How will you be
building your property pages?"* — with three paths: **Visual Editor**,
**Page Builder**, **Developer Mode**. Also restyle the top settings tab strip into
the icon nav bar shown in `visual-editor-settings.png`.

## Decisions locked
- Base branch: `feature/property-template-system`.
- Scope: Frontend tab content **+** top icon nav bar.
- Mode behaviour: **functional** — the chosen mode changes what renders on the front end.
- Design assets live in `.design/`.
- **"Place" section: deferred** — build the chooser/modes/nav now; spec Place as separate work.
- **Legacy sites:** show the chooser defaulting to Visual Editor (no Legacy card).
- **Existing `template_set_*` styling controls:** leave exactly as-is (chooser sits above them).
- **Save model:** instant AJAX select (clicking a card saves immediately, matching the "Selected" pill).
- **Divi:** supported now, equal footing with Elementor (integration already ships).
- **Icons:** custom inline SVGs matching the mock's line-icon style; resource links are placeholders (`#`).

## Current architecture (as found)
- Frontend tab: `includes/admin/settings/class-ph-settings-frontend.php`.
  Sections: Search Results, Search Forms, Flags, **Template Set**.
- Editor-mode model lives in the `propertyhive_template_assistant` option under
  `template_set_editor_mode`. **Today only two values exist:** `legacy`,
  `visual_editor` (`includes/class-ph-template-set.php`,
  `includes/template-set/class-ph-template-set-options.php`).
- Front-end takeover is gated by `PH_Template_Set_Request_Context::is_enabled()`
  (checks `template_set_enabled == 'yes'`).
- Elementor + Divi integrations already ship (`includes/class-ph-elementor.php`,
  `includes/class-ph-divi.php`). Detection: `did_action('elementor/loaded')`,
  `class_exists('ET_Builder_Module')`.
- Custom-rendered settings UI precedent: `propertyhive_admin_field_<type>`
  callbacks (Features "pro_features", Demo Data "demodata").
- Top tabs render in `includes/admin/views/html-admin-settings.php` from the
  `propertyhive_settings_tabs_array` filter (name => label only).
- Admin CSS: `assets/css/admin.css`; Font Awesome already enqueued on admin.

## Target model — three editor modes
Extend the two-value editor mode to three, collapsing cleanly onto one question:
*"Is the visual template set driving the front end?"*

| Card            | `template_set_editor_mode` | Front-end effect |
|-----------------|----------------------------|------------------|
| Visual Editor   | `visual_editor`            | Template set active (`is_enabled()` true) |
| Page Builder    | `page_builder` (new)       | Template set stands down so Elementor/Divi theme-builder templates render |
| Developer Mode  | `developer` (new)          | Template set stands down; plain PH templates (theme-overridable) render |

`legacy` stays as a back-compat value (existing sites) — see Open Question 2.

Functional wiring: `is_enabled()` (and/or the takeover hooks) gains a guard so
that only `visual_editor` mode injects the template-set front end. `page_builder`
and `developer` differ in **admin presentation**, not front-end mechanism.

## Build sequence

### Phase 1 — Data / model
1. Add constants `EDITOR_MODE_PAGE_BUILDER = 'page_builder'`,
   `EDITOR_MODE_DEVELOPER = 'developer'` to `PH_Template_Set`.
2. Extend `PH_Template_Set_Options::get_editor_modes()` to include them.
3. Update `sanitize_template_set_settings()` to accept the new modes.
4. Add a page-builder detector (reuse Elementor/Divi checks): active builder,
   name, version.

### Phase 2 — Front-end behaviour (functional)
5. Guard `PH_Template_Set_Request_Context::is_enabled()` so template-set takeover
   only applies in `visual_editor` mode (preview requests still allowed for admins).
6. Verify Page Builder mode lets Elementor/Divi theme-builder templates win, and
   Developer mode falls through to standard PH templates.

### Phase 3 — Admin card UI
7. New custom field type `propertyhive_admin_field_template_experience`
   registered from the Frontend settings class, rendering:
   - Three chooser cards (Visual Editor recommended / Page Builder / Developer).
   - Contextual panel per selection:
     - Visual Editor: "NEW" panel + Edit Search Template / Edit Details Template
       (link to existing front-end visual editor), plus help panel.
     - Page Builder: detected state (`page-builder-detected.png`) vs none
       (`page-builder-none.png`).
     - Developer: "Build without limits" + quick links (`developer.png`).
8. Selection interaction (radio-backed cards) + save model — see Open Question 8.
9. Decide placement of existing `template_set_*` styling controls — see OQ 4.

### Phase 4 — Icon nav bar
10. Enrich tab data (icon + subtitle) — likely a new
    `propertyhive_settings_tab_meta` filter keyed by tab id, with a graceful
    fallback for third-party tabs that only supply a label.
11. Update `html-admin-settings.php` markup + `admin.css` to render the icon bar.

### Phase 5 — Assets / polish
12. Scoped CSS for cards + nav bar in `admin.css` (or a new settings-redesign.css).
13. Minimal JS for card selection (no framework; jQuery already present).

## Resolved decisions (was: open questions)
1. **"Place" sub-tab** — deferred; separate spec. Not built on this branch.
2. **Legacy mode** — show chooser defaulting to Visual Editor for legacy sites.
3. **Divi** — supported now, equal footing with Elementor.
4. **Styling controls placement** — leave exactly as-is beneath the chooser.
5. **Icons** — custom inline SVGs matching the mock's line-icon style.
6. **Resource URLs** — placeholders (`#`) for now.
7. **Third-party tabs in icon bar** — generic fallback icon + label-only subtitle
   for tabs that don't register meta.
8. **Save model** — instant AJAX select with nonce + AJAX handler.
