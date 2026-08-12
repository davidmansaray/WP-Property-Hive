# Map Search live preview - Investigation and Implementation Plan Request

## User Request

On the Map Search template, changing the “Show map search” option does not update the visible preview. The expected behavior is that the page preview immediately reflects “Don’t show map”, “List and map toggle”, or “Map beside results”. Investigate with Luna Max sub-agents and determine the implementation plan.

## Current Problem

The sidebar control and iframe bridge are connected, but they apply only a body class. Map Search format is structural server-rendered state: the saved option determines which WordPress hooks, result wrappers, map canvas, provider assets, localized JavaScript, and result filters exist for the request. A CSS class cannot create those missing structures.

Local reproduction on 11 August 2026 confirmed that selecting `split` updated both parent and iframe to `ph-template-map-search-format-split`, while the iframe still had no `.half-map-view` and no `.propertyhive-map-canvas`; the fallback panel remained `display: none`. The control was restored to its original `view` value after the check.

## Desired Outcome

- Changing “Show map search” refreshes only the responsive preview iframe and immediately shows a faithful server-rendered representation of the unsaved choice.
- `none` shows no map UI, `view` shows the list/map switcher and the appropriate current view, and `split` shows the real split results/map structure.
- The saved `propertyhive_map_search` option remains unchanged until the user presses Save.
- The sidebar, dirty state, responsive device, active search criteria, search-form builder changes, and preview/editor query context survive the iframe refresh.
- Public and unauthorized requests cannot use the preview-only override.

## Confirmed Repository Context

- `PH_Template_Set_Addon_Settings::get_definitions()` declares `ph_template_set_addons[map_search][format]` with saved option key `propertyhive_map_search['format']` and values `''`, `view`, and `split` in `includes/template-set/class-ph-template-set-addon-settings.php`.
- `initTemplateEditor()` binds the generic control change listener in `assets/js/frontend/template-set.js`. The Map Search select is not a template selector, so it does not enter `loadTemplatePreview()`.
- `editorControlHandlers['ph_template_set_addons[map_search][format]']` in `assets/js/frontend/template-set/editor-preview.js` only changes `ph-template-map-search-format-*` on the parent and iframe bodies.
- `initialiseFrameDocument()` in `assets/js/frontend/template-set/editor-responsive-preview.js` correctly mirrors all current controls after each frame load. The iframe bridge is receiving the Map Search change; the applied update is insufficient.
- The editor CSS in `assets/css/template-set.css` can hide or reveal existing map elements, but it cannot create the view switcher, split wrappers, map canvas, map scripts, or server-side result behavior. Its current selectors also require `ph-template-editor-active`, which a deliberately closed frame request does not have.
- `PH_Template_Set_Request_Context::get_map_search_state()` reads the saved `propertyhive_map_search` option. `resolve_search_presentation()` uses that state to choose `none`, `toggle-list`, `map-only`, or `split` output.
- `PH_Map_Search::check_can_be_used()` in the installed `propertyhive-map-search` add-on reads the same option during `plugins_loaded` priority 1 and conditionally registers `do_map_actions()`. Those callbacks create `.half-map-view` / `.half-list-view`, output the real map, suppress results for map-only mode, and enqueue/localize provider-specific runtime state.
- The Map Search JavaScript creates global map/provider state and initializes on window load; it has no general teardown/reinitialize API. A fresh iframe navigation is the safe lifecycle boundary.
- `PH_Template_Set_Request_Context::get_preview_query_args()` is already the source used by the iframe navigation/form bridge to retain editor/template context.
- `editorResponsivePreview.navigate(url, { force: true })` already performs a contained iframe navigation and re-applies unsaved ordinary controls and search-form builder state after load.
- No core Property Hive automated test suite for this editor path was found. The installed Map Search add-on has Node tests, but they cover map runtime behavior rather than editor format switching.

## Product / Technical Gap

### Server request state

There is no authorized request-scoped representation of an unsaved Map Search format. Both core presentation resolution and the add-on independently see only the persisted option.

### Frontend preview lifecycle

The generic change handler calls a CSS preview adapter and marks the editor dirty. It never asks the responsive iframe controller to navigate for this structural control.

### Closed-frame styling

The frame intentionally lacks `ph-template-editor-active`, but “Don’t show map” currently relies on selectors scoped to that class. A frame-specific editor-preview class or equivalent selector is needed so the temporary `none` state suppresses the decorative Map Atlas fallback too.

### Save reconciliation

After Save, the temporary override must be removed or reconciled with the persisted value without reloading the parent editor. The iframe should continue to show the saved format and all existing dirty-state rules must remain correct.

## Recommended Architecture

1. Define a dedicated preview query argument for Map Search format in core, using an explicit `none` sentinel plus `view` and `split`.
2. Add the argument to `PH_Template_Set_Request_Context::get_preview_query_args()` so frame links, GET forms, and navigation preserve it.
3. Register an early `option_propertyhive_map_search` filter from `PH_Template_Set::init()`. For an authorized `is_template_editor_frame_request()` only, sanitize the preview value and replace only the returned option array’s `format` key. Never call `update_option()` and never honor the argument for public, admin, non-frame, or unauthorized requests.
4. Localize the argument name in editor script data rather than duplicating a PHP contract as an unexplained JavaScript string.
5. Treat `ph_template_set_addons[map_search][format]` as a structural preview control in the parent change flow. Keep the immediate class adapter as a loading-state fallback, then force the responsive iframe to its current preview URL with the normalized override added.
6. Use full iframe navigation, not in-place map markup replacement. This lets WordPress and the Map Search add-on register the correct hooks, output the correct result structure, enqueue the selected provider, localize the correct `ajax_object.format`, initialize a fresh map, and discard old map globals with the old browsing context.
7. Preserve the current Map Search `view` parameter when it remains meaningful. Define deterministic transitions for stale state: `none` may ignore `view=map`; returning to `view` may preserve the previous list/map choice, while `split` should render its normal split state. Explicitly test `view`, `draw`, and `pgp` retention rather than adding them blindly to the protected editor-argument list.
8. Add a frame-specific body class from server request context and update the `none` preview CSS to hide the decorative panel and Map Search controls inside both the active parent preview and the closed responsive frame.
9. After Save succeeds, remove or normalize the temporary URL override to the saved format and refresh only the iframe if needed. Do not trigger a full parent-page reload for this setting.

## Planning Focus

- Specify the exact query-argument constant, allowed values, `none` sentinel, sanitization, and capability/frame checks.
- Establish the earliest safe option-filter registration so the Map Search add-on sees the override before its `plugins_loaded` setup, without changing the add-on repository.
- Define how the option filter avoids recursion and changes only `format` while retaining all advanced Map Search settings.
- Specify the frontend API for setting/removing a preview query argument and forcing a contained iframe navigation.
- Define transition semantics for `none`, `view`, and `split`, including existing `view=map`, polygon `pgp`, and draw mode.
- Preserve the selected responsive device, iframe scroll behavior, dirty state, sidebar state, search criteria, and unsaved search-form builder state.
- Define Save success/failure behavior and how temporary preview state is reconciled with persisted state.
- Keep public URLs, unauthorized requests, admin requests, and ordinary editor-closed links unaffected.
- Avoid DOM-level map reinitialization and duplicate provider scripts/listeners.
- Provide precise browser verification for Google Maps, OSM, and Mapbox where available, plus a practical automated-test seam for sanitization and URL/state logic.

## Inferences To Verify

- Core Property Hive is loaded early enough to register an option filter before the Map Search add-on’s `plugins_loaded` priority-1 callback. The plugin bootstrap order should be confirmed in a representative installation, although WordPress loads all active plugin files before firing `plugins_loaded`.
- The preferred UX is immediate unsaved preview rather than refreshing only after Save. This follows the stated expected behavior and the rest of the visual editor’s live-preview contract.
- Preserving `view=map` across format changes is preferable to always returning to list view; product behavior should be made explicit in the detailed plan.

## Important Constraints

- Do not persist the preview override before Save.
- Do not modify the Map Search add-on as the primary fix; core owns the visual-editor integration contract.
- Do not weaken `can_manage_template_set()` or frame-request authorization.
- Do not replace/reinitialize a live third-party map in place.
- Keep ES5-compatible, no-build frontend conventions used by the current editor modules.
- Preserve existing responsive iframe navigation and GET-form argument semantics.
- No commit, push, deployment, or option mutation is part of this planning task.

## Relevant Files

| File | Why it matters |
|---|---|
| `includes/class-ph-template-set.php` | Constants, early hook registration, body classes, and template-set bootstrap. |
| `includes/template-set/class-ph-template-set-request-context.php` | Frame authorization, preview query-argument contract, Map Search state and presentation resolution. |
| `includes/template-set/class-ph-template-set-editor-controller.php` | Localized editor configuration and preview argument name. |
| `includes/template-set/class-ph-template-set-addon-settings.php` | Map Search control definition, persistence contract, and save metadata. |
| `assets/js/frontend/template-set.js` | Generic control-change, dirty/save, and post-save lifecycle. |
| `assets/js/frontend/template-set/editor-preview.js` | Existing Map Search class adapter and document mirroring. |
| `assets/js/frontend/template-set/editor-responsive-preview.js` | Safe iframe URL construction, forced navigation, frame load, form/link argument preservation, and state reapplication. |
| `assets/css/template-set.css` | Active-editor/frame preview visibility rules for `none`, `view`, and `split`. |
| `includes/template-set/traits/trait-ph-template-set-search.php` | Search wrapper, fallback panel, dependency notice, and map-aware result rendering. |
| `templates/archive-property.php` | Hook seams used by the Map Search add-on to output maps and split wrappers. |
| `../propertyhive-map-search/propertyhive-map-search.php` | External integration evidence: early saved-option read, conditional hooks, wrappers, assets, localization, and map canvas. Read-only dependency. |

## Senior Engineer Prompt

```text
You are a senior engineer. Read implementation-prompts/map-search-live-preview-investigation.md, inspect any additional repository and installed Map Search add-on files needed to fill gaps, then produce a detailed, implementation-ready plan for a junior engineer.

Feature/problem:
In the visual template editor’s Map Search template, changing “Show map search” between Don’t show map, List and map toggle, and Map beside results must immediately update the responsive iframe with a faithful rendering before Save. Today the iframe receives only a CSS class while the real structure and runtime are chosen server-side from the persisted add-on option.

Planning direction:
Use a capability-gated, frame-only query override for the unsaved Map Search format; make core’s request state and the add-on’s early option read observe it without persisting it; force a fresh iframe navigation on format changes; and reconcile/remove the override after Save. Do not propose in-place third-party map DOM reinitialization. Preserve editor/frame query context, search criteria, search-form builder state, responsive device, sidebar state, dirty state, and safe GET navigation.

The plan must specify:
1. Exact files, classes, functions, constants, and JavaScript APIs to change.
2. The query-argument name, `none` sentinel, allowed values, sanitization, authorization, and option-filter timing/recursion behavior.
3. How only the option array’s `format` key is overridden while all advanced Map Search settings remain intact.
4. The frontend event sequence from control change through loading, iframe navigation, frame initialization, and post-save reconciliation.
5. Deterministic handling of `view=map`, list view, `draw`, and `pgp` across all format transitions.
6. CSS/body-class changes needed for a closed responsive frame, especially the no-map fallback state.
7. Existing invariants to preserve: no premature persistence, no parent reload, no public override, no duplicate map globals/listeners, no loss of unsaved search-form state, and no responsive/sidebar state regression.
8. A step-by-step implementation order suitable for a junior engineer.
9. Named test scenarios for every none/view/split transition, Save success/failure, navigation/form submissions, unauthorized requests, repeated changes, and each available map provider.
10. Rollout, Local WordPress verification, regression checks, rollback guidance, risks, assumptions, and any open product question.

Do not write product code yet. Save the final detailed implementation plan as implementation-plans/map-search-live-preview.md and report that path when finished.
```
