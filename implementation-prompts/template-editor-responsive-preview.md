# Template editor responsive preview

## Objective

Implement the approved Prototype 01 toolbar pattern in the front-end Property Hive template editor. Users can switch the page preview between Mobile (390 px), Tablet (768 px), and Desktop (1280 px) while the existing sidebar remains visible and fully functional.

## Implementation status

Completed on `feature/template-editor-responsive-preview` on 11 August 2026. The implementation uses a same-origin iframe with automatic fit and centered device transitions. It also bridges unsaved search-form builder replacements into the visible iframe and preserves the complete server-defined preview context when GET forms navigate inside it.

## Definition of done

- A toolbar matching the current Property Hive editor visual language is displayed above the page preview, outside the sidebar form.
- Mobile, Tablet, and Desktop buttons expose their state with `aria-pressed`.
- The preview uses a real same-origin iframe viewport so existing media queries, viewport units, fixed elements, and responsive JavaScript evaluate at the selected width.
- Device selection persists for the browser tab/session and survives template swaps and full preview navigation.
- Larger logical viewports automatically fit inside the available canvas without changing the iframe's internal viewport width.
- The preview is horizontally centered, changes size with a short eased transition, and keeps scrolling inside the preview page rather than adding a second canvas scrollbar.
- Switching device does not mark settings dirty, enable Save, submit data, or alter sidebar accordion/collapse state.
- The existing sidebar remains the only editor form; its desktop left rail, mobile bottom sheet, collapse tray, and admin-bar reopen behavior continue to work.
- Search and detail pages remain interactive in the preview, with a useful loading/error state and keyboard-accessible controls.
- PHP and JavaScript syntax checks pass, `git diff --check` passes, and the flow is exercised in the running Local WordPress site at desktop and narrow browser widths.

## Architecture before this feature

- `PH_Template_Set_Editor_Controller::render_template_editor()` renders the fixed `<aside data-ph-template-editor>`, its form, collapse control, unsaved warning, and JSON config in the footer.
- `.ph-template-detail` or `.ph-template-search` is currently the live preview in the same document.
- `editor-sidebar.js` exclusively owns sidebar state, focus, `inert`, session storage, and admin-bar visibility.
- `editor-preview.js` applies unsaved control changes directly to the preview DOM.
- `template-set.js` owns dirty/save state, fetches template changes, replaces preview/editor/config DOM, updates history, and emits `ph:template_set_preview_swapped`.
- `class-ph-template-set-assets.php` registers independent ES5-style browser scripts and loads `template-set.js` after them.
- `template-set.css` uses `--ph-template-editor-space` to reserve the sidebar and converts the sidebar to a bottom sheet at 720 px.

## Architectural decision

Use a same-origin iframe for the simulated viewport. A CSS-only narrow wrapper is rejected because the existing templates use viewport media queries, `vw`, fixed positioning, and responsive JavaScript; these would continue to observe the outer browser width.

This follows the established Elementor and Beaver Builder parent/iframe architecture. The first release also adopts the fit-to-canvas behavior used by Elementor, Divi, and Breakdance: the iframe retains its true 390/768/1280 px layout viewport while its outer presentation is automatically scaled down when needed. There is no manual scale mode: the preview uses as much available space as possible while remaining centered. Drag-resizing, arbitrary dimensions, orientation controls, named devices, and custom breakpoints remain follow-up capabilities rather than inflating the first release.

The parent document remains the editor application. It owns the toolbar, sidebar, form, URL/history, loading state, and selected device. The iframe loads the equivalent preview URL with `ph_template_edit=1`, `ph_template_editor_closed=1`, and a dedicated `ph_template_editor_frame=1` marker. Keeping the edit argument preserves capability-gated preview rendering when the template set is not yet globally enabled; the closed argument prevents a nested editor; the frame marker suppresses the iframe's WordPress admin bar. A focused bridge mirrors live unsaved changes into the iframe and reinitializes preview behavior after iframe navigation. Cross-origin access must fail safely, although normal Local/live use is same-origin.

## Implemented file changes

### PHP and asset registration

- `includes/class-ph-template-set.php`
  - Define the frame query argument and suppress the WordPress admin bar only for an authorized responsive-preview frame request.
- `includes/template-set/class-ph-template-set-request-context.php`
  - Recognize the dedicated frame request without weakening the existing capability checks.
- `includes/template-set/class-ph-template-set-editor-controller.php`
  - Render a preview workspace/toolbar as a sibling of the sidebar, not inside the form.
  - Include accessible device buttons, iframe title, loading copy, and fallback/open-preview link.
  - Do not add named controls to the editor form.
- `includes/template-set/class-ph-template-set-assets.php`
  - Register `editor-responsive-preview.js` before `template-set.js` and include it in the latter's dependencies.

### Responsive preview controller

- `assets/js/frontend/template-set/editor-responsive-preview.js` (new)
  - Presets: mobile 390, tablet 768, desktop 1280.
  - Store state under its own session-storage key.
  - Build a preview-only URL by removing only the editor-open argument, retaining/adding the editor edit argument, and adding the existing closed-preview and dedicated frame arguments.
  - Own toolbar event delegation, iframe source/loading state, dimensions, ARIA state, state restoration, and safe same-origin document access.
  - Calculate automatic fit from the available canvas without changing the iframe's logical viewport width; use `ResizeObserver` where available and a resize-event fallback.
  - Expose an idempotent module API on `window.phTemplateSetModules.editorResponsivePreview` such as `init`, `refresh`, `getDocument`, and `navigate`.
  - Listen for the existing preview-swapped event and preserve selected device.
  - Mirror AJAX-generated search-form builder markup and live field state into the iframe, including multiple selections and builder initialization.
  - Intercept same-origin GET forms and links so iframe navigation retains the server-localized template and frame query arguments without retaining cleared search filters.
  - Contain inherited and explicit parent/top targets inside the preview for same-origin navigation, while allowing external destinations to open separately.
- `assets/js/frontend/template-set.js`
  - Initialize/refresh the responsive module with the existing editor lifecycle.
  - Route template preview navigation to the iframe while retaining parent history/config/sidebar replacement invariants.
  - Keep responsive UI state outside dirty/save serialization.
- `assets/js/frontend/template-set/editor-preview.js`
  - Allow live preview operations to target the iframe document (or mirror each applied control into it) without regressing the parent fallback preview.
  - Re-run gallery/interactive initialization after iframe load where required.
- `assets/js/frontend/template-set/search-form-builder.js`
  - Emit replacement details when AJAX builder markup changes and expose reusable form initialization for the iframe copy.

### Styling

- `assets/css/template-set.css`
  - Add a normal-flow toolbar/workspace over the content region, deriving available space from the existing body/sidebar contract rather than adding another sidebar offset.
  - Center the iframe and apply a presentation-only fit scale when needed, without adding a scrollable outer canvas.
  - Animate device-size changes briefly while respecting reduced-motion preferences.
  - Provide selected, hover, focus-visible, loading, and error states using current carbon/honey tokens.
  - Keep the toolbar usable when the sidebar is collapsed and when the outer viewport invokes the existing bottom sheet.
  - Respect the WordPress admin bar and reduced-motion preferences.

## State and event contract

- Sidebar state and responsive state remain independent.
- Default device is Desktop; valid session state may override it.
- Responsive controls have no `name` and live outside the form.
- Device switches only update UI/session state and iframe dimensions.
- Automatic fit updates when the canvas or sidebar space changes; it never changes the width observed by media queries inside the iframe.
- Parent template changes continue to update history and editor config; the responsive controller navigates/reloads the preview iframe to the corresponding editor-closed URL.
- Parent setting changes are mirrored to the iframe document without navigation so unsaved values remain visible.
- Unsaved search-form structure and configuration are reapplied after iframe loads and remain visible after an in-preview search.
- GET form navigation uses native successful-control semantics, then restores the full server-defined preview context, including the active template and view arguments.
- The iframe never renders another active editor sidebar.

## Risks to handle

- Prevent iframe recursion with `ph_template_editor_closed=1`; keep `ph_template_edit=1` for the valid preview context, strip `ph_template_editor_open`, and add `ph_template_editor_frame=1` to suppress iframe-only chrome.
- Preserve selected device when `replaceFetchedTemplate()` replaces sidebar/config DOM.
- Do not let iframe links take over parent editor history unexpectedly.
- Treat same-origin DOM access as optional and surface a usable fallback if access fails.
- Reinitialize galleries, maps, sticky search UI, and other iframe-local behavior after load.
- Ensure full-bleed templates bleed to the iframe viewport, not the outer browser.
- Avoid double offsets from body padding plus workspace margins.
- At a narrow outer viewport, preserve access to both toolbar and the existing bottom-sheet collapse handle.

## Verification checklist

1. Run PHP lint on both changed PHP classes and the plugin entry point.
2. Run `node --check` on every changed JavaScript file.
3. Run `git diff --check` and inspect the complete diff against `staging`.
4. In the Local WordPress detail editor, verify 390/768/1280 iframe `clientWidth`, active labels, `aria-pressed`, and visible responsive layout changes.
5. Verify automatic fit changes only the iframe's outer visual scale, the frame remains centered, and the outer canvas does not scroll.
6. Change an editor control and confirm the iframe updates while Save/dirty behavior remains unchanged except for the setting itself.
7. Switch templates and use browser back/forward; confirm device and sidebar states survive.
8. Collapse/reopen the sidebar through the tray/admin bar and confirm the preview remains selected and usable.
9. Repeat on the search editor and on a narrow outer browser viewport where the sidebar becomes a bottom sheet.
10. Check console errors and screenshots for standard detail, search, and at least one full-bleed template.

## Original senior engineer handoff prompt

The following prompt was used for implementation and is retained as a record; the work is now complete.

Implement the Property Hive template editor responsive preview described in this document on `feature/template-editor-responsive-preview`, based on `staging`. Preserve the established server-rendered sidebar, save lifecycle, admin-bar behavior, and ES5-compatible no-build JavaScript style. Use a same-origin iframe for truthful responsive rendering at 390, 768, and 1280 px; do not substitute a CSS-only wrapper. Automatically fit and center the preview while preserving the iframe's real logical viewport, animate device changes briefly, and avoid a second canvas scrollbar. Keep all responsive state out of the WordPress settings form and dirty state. Work only in the files assigned to your scope, use WordPress escaping/i18n conventions, make initialization idempotent across AJAX template replacement, and list every file changed. Do not commit. Validate syntax and describe any integration assumptions for the orchestrator.
