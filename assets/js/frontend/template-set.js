(function () {
	'use strict';

	var modules = window.phTemplateSetModules = window.phTemplateSetModules || {};
	var config = window.phTemplateSet || {};

	// Keep the existing CSS and public JavaScript contract stable while feature code lives in focused modules.
	function setEditorStatus(editor, message, state) {
		var status = editor.querySelector('[data-ph-template-editor-status]');

		if (status) {
			status.textContent = message;
		}

		editor.setAttribute('data-ph-template-editor-state', state || 'ready');
	}

	function buildEditorFormData(form) {
		var data = new window.FormData();

		data.append('action', 'propertyhive_template_set_save');
		data.append('security', config.security || '');

		Array.prototype.slice.call(form.elements).forEach(function (field) {
			if (!field.name || field.disabled) {
				return;
			}

			if (field.type === 'checkbox') {
				data.append(field.name, field.checked ? field.value : '');
				return;
			}

			if (field.type === 'radio') {
				if (field.checked) {
					data.append(field.name, field.value);
				}
				return;
			}

			data.append(field.name, field.value);
		});

		return data;
	}

	function hasUnsavedEditorChanges(editor, searchFormBuilder) {
		return editor.classList.contains('is-dirty') || !!(searchFormBuilder && typeof searchFormBuilder.isDirty === 'function' && searchFormBuilder.isDirty());
	}

	function getControlValue(control) {
		if (control.type === 'checkbox') {
			return control.checked ? control.value : '';
		}

		if (control.type === 'radio') {
			return control.checked ? control.value : '';
		}

		return control.value;
	}

	function restoreControlValue(control, value) {
		if (control.type === 'checkbox') {
			control.checked = value === control.value;
			return;
		}

		if (control.type === 'radio') {
			control.checked = value === control.value;
			return;
		}

		control.value = value;
	}

	function confirmTemplateNavigation(labels) {
		return window.confirm(labels.unsavedNavigation || 'You have unsaved changes. Leave this page without saving?');
	}

	function getPreviewLoader(labels) {
		var loader = document.querySelector('[data-ph-template-preview-loader]');

		if (loader) {
			return loader;
		}

		loader = document.createElement('div');
		loader.className = 'ph-template-preview-loader';
		loader.hidden = true;
		loader.setAttribute('data-ph-template-preview-loader', '');
		loader.setAttribute('role', 'status');
		loader.setAttribute('aria-live', 'polite');
		loader.innerHTML = '<span class="ph-template-preview-spinner" aria-hidden="true"></span><span data-ph-template-preview-loader-label></span>';
		document.body.appendChild(loader);
		loader.querySelector('[data-ph-template-preview-loader-label]').textContent = labels.loading || 'Loading...';

		return loader;
	}

	function setPreviewLoading(isLoading, labels) {
		var loader = getPreviewLoader(labels || {});
		var preview = document.querySelector('.ph-template-detail, .ph-template-search');

		loader.hidden = !isLoading;
		document.body.classList.toggle('ph-template-preview-is-loading', isLoading);

		if (preview) {
			if (isLoading) {
				preview.setAttribute('aria-busy', 'true');
			} else {
				preview.removeAttribute('aria-busy');
			}
		}
	}

	function parseFetchedEditorConfig(nextDocument) {
		var configNode = nextDocument.querySelector('[data-ph-template-editor-config]');

		if (!configNode) {
			throw new Error('The template preview did not include editor configuration.');
		}

		return JSON.parse(configNode.textContent || '{}');
	}

	function replaceFetchedTemplate(nextDocument, previewUrl, updateHistory) {
		var currentEditor = document.querySelector('[data-ph-template-editor]');
		var nextEditor = nextDocument.querySelector('[data-ph-template-editor]');
		var context = currentEditor ? currentEditor.getAttribute('data-ph-template-editor-context') : 'detail';
		var previewSelector = context === 'search' ? '.ph-template-search' : '.ph-template-detail';
		var currentPreview = document.querySelector(previewSelector);
		var nextPreview = nextDocument.querySelector(previewSelector);
		var currentConfigNode = document.querySelector('[data-ph-template-editor-config]');
		var nextConfigNode = nextDocument.querySelector('[data-ph-template-editor-config]');
		var nextConfig = parseFetchedEditorConfig(nextDocument);
		var importedEditor;
		var importedPreview;
		var importedConfigNode;
		var isEditorSidebarCollapsed = !!(currentEditor && (
			currentEditor.classList.contains('is-collapsed')
			|| currentEditor.getAttribute('data-ph-template-editor-collapsed') === 'true'
			|| document.body.classList.contains('ph-template-editor-sidebar-collapsed')
		));
		var scrollPosition = { x: window.scrollX, y: window.scrollY };

		if (!currentEditor || !nextEditor || !currentPreview || !nextPreview || !nextConfigNode) {
			throw new Error('The template preview response was incomplete.');
		}

		importedEditor = document.importNode(nextEditor, true);
		importedPreview = document.importNode(nextPreview, true);
		importedConfigNode = document.importNode(nextConfigNode, true);

		if (isEditorSidebarCollapsed) {
			importedEditor.classList.add('is-collapsed');
			importedEditor.setAttribute('data-ph-template-editor-collapsed', 'true');
		}

		document.querySelectorAll('.ph-template-gallery-lightbox').forEach(function (lightbox) {
			lightbox.remove();
		});

		currentPreview.parentNode.replaceChild(importedPreview, currentPreview);
		currentEditor.parentNode.replaceChild(importedEditor, currentEditor);

		if (currentConfigNode && currentConfigNode.parentNode) {
			currentConfigNode.parentNode.replaceChild(importedConfigNode, currentConfigNode);
		} else {
			document.body.appendChild(importedConfigNode);
		}

		document.body.className = nextDocument.body.className;
		document.body.classList.toggle('ph-template-editor-sidebar-collapsed', isEditorSidebarCollapsed);
		document.title = nextDocument.title;
		config = nextConfig;
		window.phTemplateSet = nextConfig;
		window.phTemplateSet.refresh = refresh;

		if (updateHistory !== false && window.history && typeof window.history.pushState === 'function') {
			window.history.pushState({ propertyHiveTemplatePreview: true }, '', previewUrl);
		}

		refresh(importedPreview);

		initTemplateEditor();
		document.dispatchEvent(new window.CustomEvent('ph:template_set_preview_swapped', {
			detail: { root: importedPreview }
		}));
		if (window.jQuery) {
			window.jQuery(document).trigger('ph:template_set_preview_swapped', [{ root: importedPreview }]);
		}
		window.scrollTo(scrollPosition.x, scrollPosition.y);
	}

	function loadTemplatePreview(previewUrl, labels, updateHistory) {
		if (config.requiresFullPreviewNavigation) {
			window.location.href = previewUrl;
			return window.Promise.resolve(false);
		}

		if (!window.fetch || !window.DOMParser) {
			window.location.href = previewUrl;
			return window.Promise.resolve(false);
		}

		setPreviewLoading(true, labels);

		return window.fetch(previewUrl, {
			method: 'GET',
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('Could not load the selected template.');
			}

			return response.text();
		}).then(function (html) {
			var nextDocument = new window.DOMParser().parseFromString(html, 'text/html');
			replaceFetchedTemplate(nextDocument, previewUrl, updateHistory);
			setPreviewLoading(false, labels);
			return true;
		}).catch(function () {
			window.location.href = previewUrl;
			return false;
		});
	}

	function refresh(root) {
		var scope = root || document;

		if (modules.gallery && typeof modules.gallery.init === 'function') {
			modules.gallery.init(config, scope);
		}

		if (modules.searchMapRail && typeof modules.searchMapRail.init === 'function') {
			modules.searchMapRail.init(scope);
		}

		if (typeof window.ph_check_if_shortlisted === 'function') {
			window.ph_check_if_shortlisted();
		}

		initShortlistButtonLabels(scope);
	}

	function applyShortlistButtonLabel(button) {
		var shortlist = window.propertyhive_shortlist || {};
		var addLabel = button.getAttribute('data-add-label');
		var removeLabel = button.getAttribute('data-remove-label');
		var currentLabel = (button.textContent || '').trim();

		if (!addLabel || !removeLabel) {
			return;
		}

		if (shortlist.add_link_text && currentLabel === shortlist.add_link_text.trim()) {
			button.textContent = addLabel;
		} else if (shortlist.remove_link_text && currentLabel === shortlist.remove_link_text.trim()) {
			button.textContent = removeLabel;
		}
	}

	function initShortlistButtonLabels(scope) {
		var buttons = [];

		if (scope && scope.matches && scope.matches('a[data-add-to-shortlist][data-add-label][data-remove-label]')) {
			buttons.push(scope);
		}

		if (scope && scope.querySelectorAll) {
			buttons = buttons.concat(Array.prototype.slice.call(scope.querySelectorAll('a[data-add-to-shortlist][data-add-label][data-remove-label]')));
		}

		buttons.forEach(function (button) {
			applyShortlistButtonLabel(button);

			if (button.getAttribute('data-ph-template-shortlist-label-observer') === 'true' || !window.MutationObserver) {
				return;
			}

			button.setAttribute('data-ph-template-shortlist-label-observer', 'true');
			new window.MutationObserver(function () {
				applyShortlistButtonLabel(button);
			}).observe(button, { childList: true, characterData: true, subtree: true });
		});
	}

	function shouldForceLocationMapNavigation(form) {
		var control = form.querySelector('[name="template_set_location_map"]');

		return !!control
			&& control.value === 'real-map'
			&& control.getAttribute('data-ph-template-editor-saved-value') !== 'real-map'
			&& !!config.locationMapProviderConfigured;
	}

	function initTemplateEditor() {
		var editor = document.querySelector('[data-ph-template-editor]');
		var form;
		var labels;
		var searchFormBuilder;

		if (!editor || !config.editorActive) {
			return;
		}

		if (editor.getAttribute('data-ph-template-editor-initialized') === 'true') {
			return;
		}

		editor.setAttribute('data-ph-template-editor-initialized', 'true');

		form = editor.querySelector('[data-ph-template-editor-form]');
		labels = config.labels || {};

		if (!form) {
			return;
		}

		if (modules.searchFormBuilder && typeof modules.searchFormBuilder.init === 'function') {
			searchFormBuilder = modules.searchFormBuilder.init(config, editor, form, labels, {
				setEditorStatus: setEditorStatus,
				refreshEditorGroupBody: function (targetForm) {
					if (modules.editorSidebar && typeof modules.editorSidebar.refreshActiveGroupBodyWithoutTransition === 'function') {
						modules.editorSidebar.refreshActiveGroupBodyWithoutTransition(targetForm);
					}
				}
			});
		}

		if (modules.editorSidebar && typeof modules.editorSidebar.init === 'function') {
			modules.editorSidebar.init(editor, form, config.editorSidebarLayout || null);
		}

		form.querySelectorAll('[data-ph-template-editor-control]').forEach(function (control) {
			control.setAttribute('data-ph-template-editor-previous-value', getControlValue(control));
			control.setAttribute('data-ph-template-editor-saved-value', getControlValue(control));

			control.addEventListener('change', function () {
				var previousValue = control.getAttribute('data-ph-template-editor-previous-value') || '';
				var previewUrl = modules.editorPreview && typeof modules.editorPreview.getTemplatePreviewUrl === 'function' ? modules.editorPreview.getTemplatePreviewUrl(control) : '';

				if (previewUrl && previewUrl !== window.location.href) {
					if (hasUnsavedEditorChanges(editor, searchFormBuilder) && !confirmTemplateNavigation(labels)) {
						restoreControlValue(control, previousValue);
						return;
					}

					setEditorStatus(editor, labels.loading || 'Loading...', 'saving');
					loadTemplatePreview(previewUrl, labels, true);
					return;
				}

				if (modules.editorPreview && typeof modules.editorPreview.applyControl === 'function') {
					modules.editorPreview.applyControl(control);
				}
				control.setAttribute('data-ph-template-editor-previous-value', getControlValue(control));
				editor.classList.add('is-dirty');
				setEditorStatus(editor, labels.changed || 'Unsaved changes', 'changed');
			});

			if (control.type === 'color') {
				control.addEventListener('input', function () {
					if (modules.editorPreview && typeof modules.editorPreview.applyControl === 'function') {
						modules.editorPreview.applyControl(control);
					}
					control.setAttribute('data-ph-template-editor-previous-value', getControlValue(control));
					editor.classList.add('is-dirty');
					setEditorStatus(editor, labels.changed || 'Unsaved changes', 'changed');
				});
			}
		});

		form.addEventListener('submit', function (event) {
			var saveButton = form.querySelector('[data-ph-template-editor-save]');
			var forceLocationMapNavigation = shouldForceLocationMapNavigation(form);

			event.preventDefault();

			if (!window.fetch || !window.FormData) {
				setEditorStatus(editor, labels.error || 'Could not save', 'error');
				return;
			}

			setEditorStatus(editor, labels.saving || 'Saving...', 'saving');
			if (saveButton) {
				saveButton.disabled = true;
			}

			window.fetch(config.ajaxUrl || '', {
				method: 'POST',
				credentials: 'same-origin',
				body: buildEditorFormData(form)
			}).then(function (response) {
				return response.json();
			}).then(function (payload) {
				if (!payload || !payload.success) {
					throw new Error(payload && payload.data && payload.data.message ? payload.data.message : (labels.error || 'Could not save'));
				}

				config.settings = payload.data && payload.data.settings ? payload.data.settings : config.settings;

				if (searchFormBuilder && searchFormBuilder.isDirty()) {
					return searchFormBuilder.save();
				}
			}).then(function () {
				editor.classList.remove('is-dirty');
				form.querySelectorAll('[data-ph-template-editor-control]').forEach(function (control) {
					control.setAttribute('data-ph-template-editor-previous-value', getControlValue(control));
				});
				setEditorStatus(editor, labels.saved || 'Saved', 'saved');

				if (forceLocationMapNavigation) {
					window.location.reload();
				}
			}).catch(function (error) {
				setEditorStatus(editor, error && error.message ? error.message : (labels.error || 'Could not save'), 'error');
			}).finally(function () {
				if (saveButton) {
					saveButton.disabled = false;
				}
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		refresh(document);
		updateInfiniteScrollProgress();

		initTemplateEditor();

		window.addEventListener('popstate', function () {
			if (config.editorActive && document.querySelector('[data-ph-template-editor]')) {
				loadTemplatePreview(window.location.href, config.labels || {}, false);
			}
		});
	});

	var infiniteList = null;
	var infiniteCount = 0;

	function captureInfiniteScrollStart() {
		infiniteList = document.querySelector('.ph-template-search ul.properties');
		infiniteCount = infiniteList ? infiniteList.children.length : 0;
	}

	function refreshInfiniteScrollResults() {
		if (!infiniteList) {
			refresh(document);
			return;
		}

		Array.prototype.slice.call(infiniteList.children, infiniteCount).forEach(function (node) {
			refresh(node);
		});
	}

	function replaceResultsProgressPlaceholders(format, loaded, total) {
		return format.replace('%1$s', String(loaded)).replace('%2$s', String(total));
	}

	function updateInfiniteScrollProgress() {
		var list = document.querySelector('.ph-template-search ul.properties');
		var progress = document.querySelector('.ph-template-results-progress');
		var total;
		var loaded;
		var format;

		if (!list || !progress) {
			return;
		}

		total = parseInt(progress.getAttribute('data-total') || '0', 10);
		loaded = list.querySelectorAll('li.ph-template-card').length;
		format = (config.labels && config.labels.resultsProgress) || 'Showing %1$s of %2$s';
		progress.textContent = replaceResultsProgressPlaceholders(format, Math.min(loaded, total), total);
	}

	function handleInfiniteScrollLoaded() {
		refreshInfiniteScrollResults();
		updateInfiniteScrollProgress();
	}

	document.addEventListener('ph:infinite_scroll_loading_properties', captureInfiniteScrollStart);
	document.addEventListener('ph:infinite_scroll_loaded_properties', handleInfiniteScrollLoaded);
	window.addEventListener('pageshow', function (event) {
		if (event.persisted) {
			updateInfiniteScrollProgress();
		}
	});

	if (window.jQuery) {
		window.jQuery(document)
			.on('ph:infinite_scroll_loading_properties.phTemplateSet', captureInfiniteScrollStart)
			.on('ph:infinite_scroll_loaded_properties.phTemplateSet', handleInfiniteScrollLoaded);

		window.jQuery(document).on('focus.phTemplateSetDatepicker', '.ph-template-enquiry-modal input[name="preferred_viewing_date"]', function () {
			var input = this;

			// The Viewing Request add-on initialises and positions its datepicker on click.
			// Queue after that handler so its body-scroll calculation cannot override the modal anchor.
			window.setTimeout(function () {
				window.setTimeout(function () {
					var $input = window.jQuery(input);

					if (typeof $input.datepicker !== 'function') {
						return;
					}

					try {
						$input.datepicker('widget').position({
							my: 'left top',
							at: 'left bottom',
							of: input
						});
					} catch (error) {
						// The add-on is absent or has not initialised this field yet.
					}
				}, 0);
			}, 0);
		});
	}

	window.phTemplateSetGallery = window.phTemplateSetGallery || {};
	window.phTemplateSetGallery.setVariant = function (variant, persist) {
		if (modules.gallery && typeof modules.gallery.setVariant === 'function') {
			modules.gallery.setVariant(variant, persist);
		}
	};

	window.phTemplateSet = window.phTemplateSet || config;
	window.phTemplateSet.refresh = refresh;
}());
