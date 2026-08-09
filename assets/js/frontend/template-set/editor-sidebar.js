(function () {
	'use strict';

	var modules = window.phTemplateSetModules = window.phTemplateSetModules || {};
	var activeEditorForm = null;
	var editorSidebarEventsReady = false;
	var editorSidebarCollapseStorageKey = 'propertyhive-template-editor-sidebar-collapsed';
	var fallbackEditorSidebarLayout = {
		active: { search: 'layout', detail: 'media' },
		groups: {
			search: [
				{ id: 'template', label: 'Template', controls: ['template_set_search_template'] },
				{ id: 'search-form', label: 'Search form', controls: ['ph_search_form_builder'] },
				{ id: 'layout', label: 'Layout', controls: ['template_set_search_layout', 'template_set_search_grid_columns'] },
				{ id: 'card-appearance', label: 'Card appearance', controls: ['template_set_search_card_size', 'template_set_image_style'] },
				{ id: 'details', label: 'Details shown', controls: ['template_set_show_branch', 'template_set_show_badges'] },
				{ id: 'addon-map_search', label: 'Map Search', controls: ['ph_template_set_addons[map_search][format]'] }
			],
			detail: [
				{ id: 'template', label: 'Template', controls: ['template_set_detail_template'] },
				{ id: 'media', label: 'Media', controls: ['template_set_gallery_layout', 'template_set_cinema_card_position', 'template_set_editorial_show_brief', 'template_set_show_floorplans', 'template_set_show_virtual_tours'] },
				{ id: 'enquiry', label: 'Enquiries', controls: ['template_set_button_style', 'template_set_contact_card_style', 'template_set_show_mobile_cta'] },
				{ id: 'recommended', label: 'Related properties', controls: ['template_set_show_recommended', 'template_set_recommended_count', 'template_set_recommended_layout', 'template_set_recommended_image_size'] }
			]
		}
	};

	function removePrefixedClass(element, prefix) {
		Array.prototype.slice.call(element.classList).forEach(function (className) {
			if (className.indexOf(prefix) === 0) {
				element.classList.remove(className);
			}
		});
	}

	function normalizeEditorSidebarLayout(layout) {
		if (
			!layout
			|| !layout.groups
			|| !Array.isArray(layout.groups.search)
			|| !Array.isArray(layout.groups.detail)
		) {
			return fallbackEditorSidebarLayout;
		}

		return layout;
	}

	function getEditorGroupText(value) {
		if (typeof value !== 'string' && typeof value !== 'number') {
			return '';
		}

		return String(value).trim();
	}

	function getEditorGroupAdvancedUrl(value) {
		var url;

		if (typeof value !== 'string' || !value.trim() || !window.URL || !window.location) {
			return '';
		}

		try {
			url = new window.URL(value, window.location.href);
			if ((url.protocol !== 'http:' && url.protocol !== 'https:') || url.hostname !== window.location.hostname) {
				return '';
			}

			return url.href;
		} catch (error) {
			return '';
		}
	}

	function getEditorGroupControls(group, items) {
		var controls = Array.isArray(group.controls) ? group.controls : [];

		return controls.map(function (control) {
			if (typeof control === 'string') {
				return control;
			}

			return control && typeof control.name === 'string' ? control.name : '';
		}).filter(function (controlName) {
			return !!controlName && !!items[controlName];
		});
	}

	function getEditorGroupStorageKey(context) {
		return 'propertyhive-template-editor-active-group-' + (context || 'search');
	}

	function getStoredEditorGroup(context) {
		try {
			return window.sessionStorage.getItem(getEditorGroupStorageKey(context)) || '';
		} catch (error) {
			return '';
		}
	}

	function storeEditorGroup(context, groupId) {
		try {
			if (groupId) {
				window.sessionStorage.setItem(getEditorGroupStorageKey(context), groupId);
			} else {
				window.sessionStorage.removeItem(getEditorGroupStorageKey(context));
			}
		} catch (error) {
			// Storage can be unavailable in privacy modes; the in-page state still works.
		}
	}

	function getStoredEditorSidebarCollapsed() {
		try {
			var value = window.sessionStorage.getItem(editorSidebarCollapseStorageKey);

			if (value === 'true') {
				return true;
			}

			if (value === 'false') {
				return false;
			}
		} catch (error) {
			// Storage can be unavailable in privacy modes; the in-page state still works.
		}

		return null;
	}

	function storeEditorSidebarCollapsed(isCollapsed) {
		try {
			window.sessionStorage.setItem(editorSidebarCollapseStorageKey, isCollapsed ? 'true' : 'false');
		} catch (error) {
			// Storage can be unavailable in privacy modes; the in-page state still works.
		}
	}

	function isEditorSidebarOpenRequested() {
		try {
			return new window.URLSearchParams(window.location.search).get('ph_template_editor_open') === '1';
		} catch (error) {
			return false;
		}
	}

	function clearEditorSidebarOpenRequest() {
		if (!window.history || typeof window.history.replaceState !== 'function') {
			return;
		}

		try {
			var url = new window.URL(window.location.href);

			url.searchParams.delete('ph_template_editor_open');
			window.history.replaceState(window.history.state, document.title, url.pathname + url.search + url.hash);
		} catch (error) {
			// URL APIs can be unavailable in older browsers; the launch state still works.
		}
	}

	function updateEditorSidebarCollapseLabel(toggle, isCollapsed) {
		if (!toggle) {
			return;
		}

		var labelAttribute = isCollapsed ? 'data-ph-template-editor-expand-label' : 'data-ph-template-editor-collapse-label';
		var fallbackLabel = isCollapsed ? 'Expand template editor' : 'Collapse template editor';
		var label = toggle.getAttribute(labelAttribute) || fallbackLabel;

		toggle.setAttribute('aria-label', label);
		toggle.setAttribute('title', label);
	}

	function setEditorSidebarCollapsed(editor, isCollapsed, shouldStore) {
		if (!editor) {
			return;
		}

		var toggle = editor.querySelector('[data-ph-template-editor-collapse-toggle]');
		var form = editor.querySelector('[data-ph-template-editor-form]');
		var shouldMoveFocus = !!(isCollapsed && form && form.contains(document.activeElement));

		editor.classList.toggle('is-collapsed', isCollapsed);
		editor.setAttribute('data-ph-template-editor-collapsed', isCollapsed ? 'true' : 'false');
		document.body.classList.toggle('ph-template-editor-sidebar-collapsed', isCollapsed);

		if (toggle) {
			toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
			updateEditorSidebarCollapseLabel(toggle, isCollapsed);
		}

		if (shouldMoveFocus && toggle) {
			try {
				toggle.focus({ preventScroll: true });
			} catch (error) {
				toggle.focus();
			}
		}

		if (form) {
			form.setAttribute('aria-hidden', isCollapsed ? 'true' : 'false');
			if ('inert' in form) {
				form.inert = isCollapsed;
			}
		}

		if (shouldStore !== false) {
			storeEditorSidebarCollapsed(isCollapsed);
		}
	}

	function initEditorSidebarCollapse(editor) {
		var toggle = editor ? editor.querySelector('[data-ph-template-editor-collapse-toggle]') : null;

		if (!editor || !toggle) {
			return;
		}

		if (toggle.getAttribute('data-ph-template-editor-collapse-bound') !== 'true') {
			toggle.addEventListener('click', function () {
				setEditorSidebarCollapsed(editor, !editor.classList.contains('is-collapsed'), true);
			});
			toggle.setAttribute('data-ph-template-editor-collapse-bound', 'true');
		}

		var markupState = editor.getAttribute('data-ph-template-editor-collapsed');
		var storedState = getStoredEditorSidebarCollapsed();
		var openOnLaunch = isEditorSidebarOpenRequested();
		var isCollapsed = openOnLaunch
			? false
			: markupState === 'true'
				? true
				: markupState === 'false'
					? false
					: storedState !== null
						? storedState
						: document.body.classList.contains('ph-template-editor-sidebar-collapsed');

		if (openOnLaunch) {
			storeEditorSidebarCollapsed(false);
			clearEditorSidebarOpenRequest();
		}

		setEditorSidebarCollapsed(editor, isCollapsed, false);
	}

	function getEditorControlItem(control) {
		if (!control || !control.name || control.type === 'hidden') {
			return null;
		}

		if (control.name === 'template_set_gallery_layout') {
			return control.closest('.ph-template-editor-field') || control.closest('.ph-template-editor-segmented');
		}

		return control.closest('.ph-template-editor-field, .ph-template-editor-toggle');
	}

	function getEditorControlItems(form) {
		var items = {};

		form.querySelectorAll('[data-ph-template-editor-panel-control]').forEach(function (item) {
			var name = item.getAttribute('data-ph-template-editor-panel-control');

			if (name && !items[name]) {
				items[name] = item;
			}
		});

		form.querySelectorAll('[data-ph-template-editor-control]').forEach(function (control) {
			var item;

			if (!control.name || items[control.name]) {
				return;
			}

			item = getEditorControlItem(control);

			if (!item) {
				return;
			}

			item.setAttribute('data-ph-template-editor-control-item', control.name);
			items[control.name] = item;
		});

		return items;
	}

	function getEditorLayoutGroups(layout, context, items) {
		var contextGroups = Array.isArray(layout.groups[context]) ? layout.groups[context] : [];

		return contextGroups.map(function (group) {
			var groupId;
			var description;
			var scope;
			var advancedUrl;

			if (!group || typeof group !== 'object') {
				return null;
			}

			groupId = getEditorGroupText(group.id);
			description = getEditorGroupText(group.description);
			scope = getEditorGroupText(group.scopeLabel || group.scope_label || group.scope);
			advancedUrl = getEditorGroupAdvancedUrl(group.advancedUrl);

			if (!groupId) {
				return null;
			}

			return {
				id: groupId,
				label: getEditorGroupText(group.label) || groupId,
				description: description,
				scope: scope,
				advancedUrl: advancedUrl,
				advancedLabel: getEditorGroupText(group.advancedLabel),
				controls: getEditorGroupControls(group, items)
			};
		}).filter(function (group) {
			return group && (group.controls.length > 0 || group.description || group.scope || group.advancedUrl);
		});
	}

	function createEditorGroupPanel(layoutId, group, items) {
		var panel = document.createElement('section');
		var button = createEditorGroupButton(layoutId, group);
		var body = document.createElement('div');
		var content = document.createElement('div');

		panel.className = 'ph-template-editor-group';
		panel.setAttribute('data-ph-template-editor-group', group.id);

		body.className = 'ph-template-editor-group-body';
		body.id = 'ph-template-editor-' + layoutId + '-' + group.id;
		body.setAttribute('data-ph-template-editor-group-body', group.id);

		content.className = 'ph-template-editor-group-content';

		if (group.description) {
			var description = document.createElement('p');

			description.className = 'ph-template-editor-group-description';
			description.setAttribute('data-ph-template-editor-group-description', '');
			description.textContent = group.description;
			content.appendChild(description);
		}

		if (group.scope) {
			var scope = document.createElement('p');

			scope.className = 'ph-template-editor-group-scope';
			scope.setAttribute('data-ph-template-editor-group-scope', '');
			scope.textContent = group.scope;
			content.appendChild(scope);
		}

		group.controls.forEach(function (controlName) {
			content.appendChild(items[controlName]);
		});

		if (group.advancedUrl) {
			var advancedLink = document.createElement('a');

			advancedLink.className = 'ph-template-editor-advanced-link';
			advancedLink.setAttribute('data-ph-template-editor-advanced-link', '');
			advancedLink.href = group.advancedUrl;
			advancedLink.textContent = group.advancedLabel || 'Edit advanced settings';
			content.appendChild(advancedLink);
		}

		body.appendChild(content);
		panel.appendChild(button);
		panel.appendChild(body);

		return panel;
	}

	function createEditorGroupButton(layoutId, group) {
		var button = document.createElement('button');

		button.type = 'button';
		button.className = 'ph-template-editor-group-toggle';
		button.setAttribute('data-ph-template-editor-group-toggle', group.id);
		button.setAttribute('aria-controls', 'ph-template-editor-' + layoutId + '-' + group.id);
		button.setAttribute('aria-expanded', 'false');
		button.textContent = group.label || group.id;

		return button;
	}

	function updateEditorGroupBodyHeight(body) {
		if (!body) {
			return;
		}

		body.style.setProperty('--ph-template-editor-group-height', body.scrollHeight + 'px');
	}

	function updateEditorGroupBodyHeightWithoutTransition(body) {
		if (!body) {
			return;
		}

		body.classList.add('is-resizing-content');
		updateEditorGroupBodyHeight(body);
		window.requestAnimationFrame(function () {
			body.classList.remove('is-resizing-content');
		});
	}

	function setEditorGroupBodyOpen(body, isActive) {
		var focusableSelector = 'a[href], button, input, select, textarea, [tabindex]';

		if (!body) {
			return;
		}

		if (isActive) {
			updateEditorGroupBodyHeight(body);
			body.removeAttribute('aria-hidden');
			if ('inert' in body) {
				body.inert = false;
			} else {
				body.querySelectorAll(focusableSelector).forEach(function (item) {
					var previousTabIndex = item.getAttribute('data-ph-template-editor-previous-tabindex');

					if (previousTabIndex === null) {
						return;
					}

					if (previousTabIndex === '') {
						item.removeAttribute('tabindex');
					} else {
						item.setAttribute('tabindex', previousTabIndex);
					}
					item.removeAttribute('data-ph-template-editor-previous-tabindex');
				});
			}
			return;
		}

		body.style.setProperty('--ph-template-editor-group-height', '0px');
		body.setAttribute('aria-hidden', 'true');
		if ('inert' in body) {
			body.inert = true;
		} else {
			body.querySelectorAll(focusableSelector).forEach(function (item) {
				if (!item.hasAttribute('data-ph-template-editor-previous-tabindex')) {
					item.setAttribute('data-ph-template-editor-previous-tabindex', item.getAttribute('tabindex') || '');
				}
				item.setAttribute('tabindex', '-1');
			});
		}
	}

	function refreshActiveEditorGroupBody(organizer) {
		var activeBody = organizer ? organizer.querySelector('.ph-template-editor-group.is-active [data-ph-template-editor-group-body]') : null;

		updateEditorGroupBodyHeight(activeBody);
	}

	function refreshActiveEditorGroupBodyWithoutTransition(organizer) {
		var activeBody = organizer ? organizer.querySelector('.ph-template-editor-group.is-active [data-ph-template-editor-group-body]') : null;

		updateEditorGroupBodyHeightWithoutTransition(activeBody);
	}

	function refreshActiveEditorGroupBodyOnNextFrame(organizer) {
		window.requestAnimationFrame(function () {
			refreshActiveEditorGroupBody(organizer);
		});
	}

	function setActiveEditorGroup(organizer, activeGroupId) {
		organizer.querySelectorAll('[data-ph-template-editor-group]').forEach(function (group) {
			var groupId = group.getAttribute('data-ph-template-editor-group');
			var button = null;
			var body = group.querySelector('[data-ph-template-editor-group-body]');
			var isActive = groupId === activeGroupId;

			organizer.querySelectorAll('[data-ph-template-editor-group-toggle]').forEach(function (candidate) {
				if (candidate.getAttribute('data-ph-template-editor-group-toggle') === groupId) {
					button = candidate;
				}
			});

			group.classList.toggle('is-active', isActive);
			setEditorGroupBodyOpen(body, isActive);

			if (button) {
				button.classList.toggle('is-active', isActive);
				button.setAttribute('aria-expanded', isActive ? 'true' : 'false');
			}
		});
	}

	function createEditorSidebarOrganizer(layoutId, layout, context, groups, items) {
		var organizer = document.createElement('div');
		var storedGroupId = getStoredEditorGroup(context);
		var activeGroupId = storedGroupId || (layout.active && layout.active[context] ? layout.active[context] : groups[0].id);

		if (!groups.some(function (group) { return group.id === activeGroupId; })) {
			activeGroupId = groups[0].id;
		}

		organizer.className = 'ph-template-editor-groups ph-template-editor-groups-compact-tabs';
		organizer.setAttribute('data-ph-template-editor-groups', layoutId);
		organizer.setAttribute('data-ph-template-editor-active-group', activeGroupId);

		groups.forEach(function (group) {
			var panel = createEditorGroupPanel(layoutId, group, items);
			var button = panel.querySelector('[data-ph-template-editor-group-toggle]');

			if (button) {
				button.addEventListener('click', function () {
					var isOpen = panel.classList.contains('is-active');
					var nextGroupId = isOpen ? '' : group.id;
					setActiveEditorGroup(organizer, nextGroupId);
					organizer.setAttribute('data-ph-template-editor-active-group', nextGroupId);
					storeEditorGroup(context, nextGroupId);
					refreshActiveEditorGroupBodyOnNextFrame(organizer);
				});
			}

			organizer.appendChild(panel);
		});

		setActiveEditorGroup(organizer, activeGroupId);
		refreshActiveEditorGroupBody(organizer);

		return organizer;
	}

	function hideEditorSourceSections(form) {
		form.querySelectorAll('.ph-template-editor-source-section').forEach(function (section) {
			section.hidden = true;
			section.setAttribute('aria-hidden', 'true');
		});
	}

	function renderEditorSidebarGroups(editor, form, layout) {
		layout = normalizeEditorSidebarLayout(layout);

		var currentOrganizer = form.querySelector('[data-ph-template-editor-groups]');
		var footer = form.querySelector('.ph-template-editor-footer');
		var context = editor.getAttribute('data-ph-template-editor-context') || 'search';
		var items = getEditorControlItems(form);
		var groups = getEditorLayoutGroups(layout, context, items);
		var organizer;

		if (!groups.length) {
			return;
		}

		removePrefixedClass(editor, 'ph-template-editor-layout-');
		editor.classList.add('ph-template-editor-layout-compact-tabs');

		organizer = createEditorSidebarOrganizer('compact-tabs', layout, context, groups, items);

		if (footer && footer.parentNode) {
			footer.parentNode.insertBefore(organizer, footer);
		} else {
			form.appendChild(organizer);
		}

		setActiveEditorGroup(organizer, organizer.getAttribute('data-ph-template-editor-active-group') || '');
		refreshActiveEditorGroupBody(organizer);

		window.requestAnimationFrame(function () {
			refreshActiveEditorGroupBody(organizer);
			organizer.classList.add('is-ready');
		});

		if (currentOrganizer && currentOrganizer.parentNode) {
			currentOrganizer.parentNode.removeChild(currentOrganizer);
		}

		hideEditorSourceSections(form);
	}

	function initEditorSidebarGroups(editor, form, layout) {
		activeEditorForm = form;
		initEditorSidebarCollapse(editor);
		renderEditorSidebarGroups(editor, form, layout);

		if (editorSidebarEventsReady) {
			return;
		}

		editorSidebarEventsReady = true;

		window.addEventListener('resize', function () {
			var organizer = activeEditorForm ? activeEditorForm.querySelector('[data-ph-template-editor-groups]') : null;
			refreshActiveEditorGroupBody(organizer);
		});

		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(function () {
				var organizer = activeEditorForm ? activeEditorForm.querySelector('[data-ph-template-editor-groups]') : null;
				refreshActiveEditorGroupBody(organizer);
			});
		}
	}

	function refreshActiveGroupBody(form) {
		refreshActiveEditorGroupBody(form ? form.querySelector('[data-ph-template-editor-groups]') : null);
	}

	function refreshActiveGroupBodyWithoutTransition(form) {
		refreshActiveEditorGroupBodyWithoutTransition(form ? form.querySelector('[data-ph-template-editor-groups]') : null);
	}

	modules.editorSidebar = {
		fallbackLayout: fallbackEditorSidebarLayout,
		init: initEditorSidebarGroups,
		refreshActiveGroupBody: refreshActiveGroupBody,
		refreshActiveGroupBodyWithoutTransition: refreshActiveGroupBodyWithoutTransition
	};
}());
