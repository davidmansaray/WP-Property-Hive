(function () {
	'use strict';

	var modules = window.phTemplateSetModules = window.phTemplateSetModules || {};
	var storageKey = 'propertyhive-template-editor-responsive-preview';
	var devices = {
		mobile: { label: 'Mobile', width: 390 },
		tablet: { label: 'Tablet', width: 768 },
		desktop: { label: 'Desktop', width: 1280 }
	};
	var state = {
		device: 'desktop'
	};
	var config = {};
	var previewRoot = null;
	var previewCanvas = null;
	var previewFrame = null;
	var frameDocument = null;
	var frameLoadHandler = null;
	var frameErrorHandler = null;
	var rootClickHandler = null;
	var observedCanvas = null;
	var resizeObserver = null;
	var resizeHandlerBound = false;
	var previewSwapHandlerBound = false;
	var searchFormReplacementHandlerBound = false;
	var searchFormReplacementHandler = null;
	var stateRestored = false;
	var rootInitialized = false;
	var storedPreviewUrl = '';
	var fitUpdateTimer = null;
	var frameNavigationPending = false;
	var lastFrameSubmitter = null;
	var mirroringFrameControls = false;

	function isElement(value) {
		return !!value && value.nodeType === 1;
	}

	function getPreviewRoot() {
		return document.querySelector('[data-ph-template-editor-preview], [data-ph-template-editor-preview-workspace], [data-ph-template-preview-workspace]');
	}

	function getAttribute(element, names) {
		var value;

		if (!element) {
			return '';
		}

		for (var index = 0; index < names.length; index += 1) {
			value = element.getAttribute(names[index]);

			if (value !== null && value !== '') {
				return value;
			}
		}

		return '';
	}

	function getPreviewToolbar(root) {
		return root ? root.querySelector('[data-ph-template-editor-preview-toolbar], [data-ph-template-preview-toolbar]') : null;
	}

	function getPreviewCanvas(root) {
		return root ? root.querySelector('[data-ph-template-editor-preview-canvas], [data-ph-template-preview-canvas]') : null;
	}

	function getPreviewFrame(root) {
		return root ? root.querySelector('[data-ph-template-editor-preview-frame], [data-ph-template-preview-frame]') : null;
	}

	function getPreviewFrameShell() {
		if (!previewFrame) {
			return null;
		}

		return previewFrame.closest('[data-ph-template-editor-preview-frame-shell], [data-ph-template-preview-frame-shell]') || previewFrame.parentElement;
	}

	function getDeviceButtons(root) {
		return root ? Array.prototype.slice.call(root.querySelectorAll('[data-ph-template-editor-preview-device], [data-ph-template-preview-device]')) : [];
	}

	function getDeviceName(button) {
		return getAttribute(button, [
			'data-ph-template-editor-preview-device',
			'data-ph-template-preview-device'
		]).toLowerCase();
	}

	function getDevice() {
		return devices[state.device] ? devices[state.device] : devices.desktop;
	}

	function getDocumentOrigin(url) {
		var parsedUrl;

		try {
			if (window.URL) {
				parsedUrl = new window.URL(url, window.location.href);
				return parsedUrl.protocol + '//' + parsedUrl.host;
			}
		} catch (error) {
			// Fall through to the anchor fallback below.
		}

		try {
			var anchor = document.createElement('a');
			anchor.href = url;
			return anchor.protocol + '//' + anchor.host;
		} catch (fallbackError) {
			return '';
		}
	}

	function isSameOrigin(url) {
		var origin = getDocumentOrigin(url);
		var currentOrigin = window.location.protocol + '//' + window.location.host;

		return !!origin && origin === currentOrigin;
	}

	function getPreviewUrl(url) {
		var parsedUrl;
		var fallbackAnchor;
		var fallbackSearch;
		var fallbackParts;
		var fallbackQuery = {};
		var fallbackHash = '';

		try {
			parsedUrl = new window.URL(url || window.location.href, window.location.href);
			parsedUrl.searchParams.delete('ph_template_editor_open');
			parsedUrl.searchParams.set('ph_template_edit', '1');
			parsedUrl.searchParams.set('ph_template_editor_closed', '1');
			parsedUrl.searchParams.set('ph_template_editor_frame', '1');

			return parsedUrl.href;
		} catch (error) {
			// Older browsers may not provide URL or URLSearchParams.
		}

		try {
			fallbackAnchor = document.createElement('a');
			fallbackAnchor.href = url || window.location.href;
			fallbackSearch = (fallbackAnchor.search || '').replace(/^\?/, '');
			fallbackHash = fallbackAnchor.hash || '';
			fallbackParts = fallbackSearch ? fallbackSearch.split('&') : [];

			fallbackParts.forEach(function (part) {
				var separator = part.indexOf('=');
				var key = separator === -1 ? part : part.slice(0, separator);

				if (key && key !== 'ph_template_editor_open') {
					fallbackQuery[key] = separator === -1 ? '' : part.slice(separator + 1);
				}
			});

			fallbackQuery.ph_template_edit = '1';
			fallbackQuery.ph_template_editor_closed = '1';
			fallbackQuery.ph_template_editor_frame = '1';
			fallbackSearch = Object.keys(fallbackQuery).map(function (key) {
				return key + (fallbackQuery[key] === '' ? '' : '=' + fallbackQuery[key]);
			}).join('&');

			return fallbackAnchor.protocol + '//' + fallbackAnchor.host + fallbackAnchor.pathname + (fallbackSearch ? '?' + fallbackSearch : '') + fallbackHash;
		} catch (fallbackError) {
			return '';
		}
	}

	function getInitialPreviewUrl() {
		var url = getAttribute(previewRoot, [
			'data-ph-template-editor-preview-url',
			'data-ph-template-preview-url'
		]);

		if (!url && previewFrame) {
			url = getAttribute(previewFrame, [
				'data-ph-template-editor-preview-url',
				'data-ph-template-preview-url',
				'src'
			]);
		}

		return getPreviewUrl(url || window.location.href);
	}

	function storeState() {
		try {
			window.sessionStorage.setItem(storageKey, JSON.stringify(state));
		} catch (error) {
			// Storage can be unavailable in privacy modes; the current page still works.
		}
	}

	function restoreState() {
		var storedState;

		if (stateRestored) {
			return;
		}

		stateRestored = true;

		try {
			storedState = JSON.parse(window.sessionStorage.getItem(storageKey) || '{}');
		} catch (error) {
			storedState = {};
		}

		if (storedState && devices[storedState.device]) {
			state.device = storedState.device;
		}
	}

	function findControl(root, selectors) {
		if (!root) {
			return null;
		}

		for (var index = 0; index < selectors.length; index += 1) {
			var control = root.querySelector(selectors[index]);

			if (control) {
				return control;
			}
		}

		return null;
	}

	function setText(control, value) {
		if (control) {
			control.textContent = value;
		}
	}

	function setPreviewAttribute(element, name, value) {
		if (!element) {
			return;
		}

		element.setAttribute(name, value);
	}

	function setPreviewStyle(element, name, value) {
		if (element && element.style && typeof element.style.setProperty === 'function') {
			element.style.setProperty(name, value);
		}
	}

	function updateControls() {
		var device = getDevice();
		var scale;

		if (!previewRoot) {
			return;
		}

		getDeviceButtons(previewRoot).forEach(function (button) {
			var isActive = getDeviceName(button) === state.device;

			button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
			button.classList.toggle('is-active', isActive);
		});

		setPreviewAttribute(previewRoot, 'data-ph-template-editor-preview-device', state.device);
		setPreviewAttribute(previewRoot, 'data-ph-template-editor-preview-active-device', state.device);
		setPreviewAttribute(previewRoot, 'data-ph-template-preview-device', state.device);
		setPreviewStyle(previewRoot, '--ph-template-editor-preview-width', device.width + 'px');
		setPreviewStyle(previewRoot, '--ph-template-preview-width', device.width + 'px');

		if (previewCanvas) {
			setPreviewAttribute(previewCanvas, 'data-ph-template-editor-preview-device', state.device);
			setPreviewAttribute(previewCanvas, 'data-ph-template-editor-preview-active-device', state.device);
			setPreviewStyle(previewCanvas, '--ph-template-editor-preview-width', device.width + 'px');
			setPreviewStyle(previewCanvas, '--ph-template-preview-width', device.width + 'px');
		}

		if (previewFrame) {
			previewFrame.setAttribute('data-ph-template-editor-preview-device', state.device);
			previewFrame.setAttribute('data-ph-template-editor-preview-logical-width', String(device.width));
			previewFrame.setAttribute('width', String(device.width));
			setPreviewStyle(previewFrame, '--ph-template-editor-preview-width', device.width + 'px');
			setPreviewStyle(previewFrame, '--ph-template-preview-width', device.width + 'px');
			setPreviewStyle(previewFrame, '--ph-template-editor-preview-logical-width', device.width + 'px');
			setPreviewStyle(previewFrame, '--ph-template-preview-logical-width', device.width + 'px');
			previewFrame.style.width = device.width + 'px';
			previewFrame.style.maxWidth = 'none';
		}

		scale = updateFitScale();
		setPreviewAttribute(previewRoot, 'data-ph-template-editor-preview-scale', String(scale));
		setPreviewAttribute(previewRoot, 'data-ph-template-preview-scale', String(scale));
	}

	function getAvailableCanvasWidth() {
		var width = 0;
		var computedStyle;
		var paddingLeft;
		var paddingRight;

		if (!previewCanvas) {
			return 0;
		}

		width = previewCanvas.clientWidth || previewCanvas.offsetWidth || 0;

		if (window.getComputedStyle) {
			computedStyle = window.getComputedStyle(previewCanvas);
			paddingLeft = parseFloat(computedStyle.paddingLeft) || 0;
			paddingRight = parseFloat(computedStyle.paddingRight) || 0;
			width -= paddingLeft + paddingRight;
		}

		return Math.max(0, width);
	}

	function getAvailableCanvasHeight() {
		var height = 0;
		var computedStyle;
		var paddingTop;
		var paddingBottom;

		if (!previewCanvas) {
			return 0;
		}

		height = previewCanvas.clientHeight || previewCanvas.offsetHeight || 0;

		if (window.getComputedStyle) {
			computedStyle = window.getComputedStyle(previewCanvas);
			paddingTop = parseFloat(computedStyle.paddingTop) || 0;
			paddingBottom = parseFloat(computedStyle.paddingBottom) || 0;
			height -= paddingTop + paddingBottom;
		}

		return Math.max(0, height);
	}

	function updateFitScale() {
		var device = getDevice();
		var availableWidth = getAvailableCanvasWidth();
		var availableHeight = getAvailableCanvasHeight();
		var scale = 1;
		var frameShell = getPreviewFrameShell();
		var frameViewport = previewFrame ? previewFrame.parentElement : null;
		var logicalHeight = frameViewport ? frameViewport.offsetHeight : 0;
		var presentedHeight;

		if (availableWidth > 0 && availableWidth < device.width) {
			scale = availableWidth / device.width;
		}

		logicalHeight = availableHeight > 0 ? availableHeight / scale : logicalHeight;
		logicalHeight = Math.max(1, logicalHeight || 1);
		presentedHeight = availableHeight > 0 ? availableHeight : logicalHeight * scale;

		if (previewRoot) {
			setPreviewStyle(previewRoot, '--ph-template-editor-preview-logical-width', device.width + 'px');
			setPreviewStyle(previewRoot, '--ph-template-preview-logical-width', device.width + 'px');
			setPreviewStyle(previewRoot, '--ph-template-editor-preview-scale', String(scale));
			setPreviewStyle(previewRoot, '--ph-template-preview-scale', String(scale));
			setPreviewStyle(previewRoot, '--ph-template-editor-preview-presented-width', (device.width * scale) + 'px');
			setPreviewStyle(previewRoot, '--ph-template-preview-presented-width', (device.width * scale) + 'px');
			setPreviewStyle(previewRoot, '--ph-template-editor-preview-logical-height', logicalHeight + 'px');
			setPreviewStyle(previewRoot, '--ph-template-editor-preview-presented-height', presentedHeight + 'px');
		}

		if (previewCanvas) {
			setPreviewStyle(previewCanvas, '--ph-template-editor-preview-logical-width', device.width + 'px');
			setPreviewStyle(previewCanvas, '--ph-template-preview-logical-width', device.width + 'px');
			setPreviewStyle(previewCanvas, '--ph-template-editor-preview-scale', String(scale));
			setPreviewStyle(previewCanvas, '--ph-template-preview-scale', String(scale));
			setPreviewStyle(previewCanvas, '--ph-template-editor-preview-presented-width', (device.width * scale) + 'px');
			setPreviewStyle(previewCanvas, '--ph-template-preview-presented-width', (device.width * scale) + 'px');
			setPreviewStyle(previewCanvas, '--ph-template-editor-preview-logical-height', logicalHeight + 'px');
			setPreviewStyle(previewCanvas, '--ph-template-editor-preview-presented-height', presentedHeight + 'px');
		}

		if (previewFrame) {
			setPreviewStyle(previewFrame, '--ph-template-editor-preview-scale', String(scale));
			setPreviewStyle(previewFrame, '--ph-template-preview-scale', String(scale));
			setPreviewStyle(previewFrame, '--ph-template-editor-preview-presented-width', (device.width * scale) + 'px');
			setPreviewStyle(previewFrame, '--ph-template-preview-presented-width', (device.width * scale) + 'px');
		}

		if (frameShell) {
			setPreviewStyle(frameShell, '--ph-template-editor-preview-scale', String(scale));
			setPreviewStyle(frameShell, '--ph-template-preview-scale', String(scale));
			setPreviewStyle(frameShell, '--ph-template-editor-preview-presented-width', (device.width * scale) + 'px');
			setPreviewStyle(frameShell, '--ph-template-editor-preview-presented-height', presentedHeight + 'px');
		}

		return scale;
	}

	function scheduleFitUpdate() {
		if (fitUpdateTimer) {
			return;
		}

		if (window.requestAnimationFrame) {
			fitUpdateTimer = window.requestAnimationFrame(function () {
				fitUpdateTimer = null;
				updateFitScale();
			});
			return;
		}

		fitUpdateTimer = window.setTimeout(function () {
			fitUpdateTimer = null;
			updateFitScale();
		}, 0);
	}

	function disconnectResizeObserver() {
		if (resizeObserver && typeof resizeObserver.disconnect === 'function') {
			resizeObserver.disconnect();
		}

		resizeObserver = null;
		observedCanvas = null;
	}

	function bindResizeHandling() {
		if (!resizeHandlerBound) {
			window.addEventListener('resize', scheduleFitUpdate);
			resizeHandlerBound = true;
		}

		if (observedCanvas === previewCanvas) {
			return;
		}

		disconnectResizeObserver();

		if (!previewCanvas || !window.ResizeObserver) {
			return;
		}

		try {
			resizeObserver = new window.ResizeObserver(scheduleFitUpdate);
			resizeObserver.observe(previewCanvas);
			observedCanvas = previewCanvas;
		} catch (error) {
			resizeObserver = null;
			observedCanvas = null;
		}
	}

	function findClosest(element, selector, boundary) {
		var current = element;

		while (current && current !== boundary) {
			if (current.matches && current.matches(selector)) {
				return current;
			}

			current = current.parentElement;
		}

		if (current === boundary && current.matches && current.matches(selector)) {
			return current;
		}

		return null;
	}

	function handleRootClick(event) {
		var deviceButton = findClosest(event.target, 'button[data-ph-template-editor-preview-device], button[data-ph-template-preview-device]', previewRoot);
		if (deviceButton) {
			var deviceName = getDeviceName(deviceButton);

			if (devices[deviceName]) {
				event.preventDefault();
				state.device = deviceName;
				storeState();
				updateControls();
				scheduleFitUpdate();
			}

		}
	}

	function bindRoot(root) {
		var toolbar = getPreviewToolbar(root);
		var nextCanvas;
		var nextFrame;

		if (previewRoot === root && rootInitialized) {
			nextCanvas = getPreviewCanvas(root);
			nextFrame = getPreviewFrame(root);

			if (nextCanvas !== previewCanvas || nextFrame !== previewFrame) {
				if (previewFrame && frameLoadHandler) {
					previewFrame.removeEventListener('load', frameLoadHandler);
					previewFrame.removeEventListener('error', frameErrorHandler);
					previewFrame.removeAttribute('data-ph-template-editor-preview-bound');
				}

				previewCanvas = nextCanvas;
				previewFrame = nextFrame;
				frameDocument = null;
				bindResizeHandling();
				bindFrame();
			}

			return;
		}

		if (previewRoot && rootClickHandler) {
			previewRoot.removeEventListener('click', rootClickHandler);
		}

		if (previewFrame && frameLoadHandler) {
			previewFrame.removeEventListener('load', frameLoadHandler);
			previewFrame.removeEventListener('error', frameErrorHandler);
			previewFrame.removeAttribute('data-ph-template-editor-preview-bound');
		}

		previewRoot = root;
		previewCanvas = getPreviewCanvas(root);
		previewFrame = getPreviewFrame(root);
		rootClickHandler = handleRootClick;
		rootInitialized = true;

		if (toolbar && !toolbar.getAttribute('aria-label')) {
			toolbar.setAttribute('aria-label', 'Preview size');
		}

		root.addEventListener('click', rootClickHandler);
		bindResizeHandling();
		bindFrame();
	}

	function setWorkspaceState(stateValue, isBusy) {
		if (previewRoot) {
			previewRoot.setAttribute('data-ph-template-editor-preview-state', stateValue);
			previewRoot.setAttribute('aria-busy', isBusy ? 'true' : 'false');
		}

		if (previewCanvas) {
			previewCanvas.setAttribute('data-ph-template-editor-preview-state', stateValue);
			previewCanvas.setAttribute('aria-busy', isBusy ? 'true' : 'false');
		}
	}

	function setLoading(isLoading, message) {
		var loadingControl;
		var loadingLabel;

		if (!previewRoot) {
			return;
		}

		if (!isLoading && frameNavigationPending) {
			return;
		}

		previewRoot.classList.toggle('is-loading', isLoading);
		previewRoot.classList.toggle('is-error', false);
		setWorkspaceState(isLoading ? 'loading' : 'ready', isLoading);
		loadingControl = findControl(previewRoot, [
			'[data-ph-template-editor-preview-loading]',
			'[data-ph-template-preview-loading]'
		]);

		if (loadingControl && loadingControl !== previewRoot) {
			loadingControl.hidden = !isLoading;
			loadingControl.setAttribute('aria-hidden', isLoading ? 'false' : 'true');
			loadingLabel = loadingControl.querySelector('[data-ph-template-editor-preview-loading-label], [data-ph-template-preview-loading-label]');
			setText(loadingLabel, message || (config.labels && config.labels.loading) || 'Loading preview...');
		}

		if (previewFrame) {
			if (isLoading) {
				previewFrame.setAttribute('aria-busy', 'true');
			} else {
				previewFrame.removeAttribute('aria-busy');
			}
		}
	}

	function setError(message) {
		var errorControl;
		var errorLabel;

		if (!previewRoot) {
			return;
		}

		frameNavigationPending = false;
		setLoading(false);
		previewRoot.classList.add('is-error');
		setWorkspaceState('error', false);
		errorControl = findControl(previewRoot, [
			'[data-ph-template-editor-preview-error]',
			'[data-ph-template-preview-error]'
		]);

		if (errorControl && errorControl !== previewRoot) {
			errorControl.hidden = false;
			errorControl.setAttribute('aria-hidden', 'false');
			errorLabel = errorControl.querySelector('[data-ph-template-editor-preview-error-label], [data-ph-template-preview-error-label], p');
			setText(errorLabel, message || 'Preview could not be loaded. Open it in a new tab to continue.');
		}
	}

	function clearError() {
		var errorControl;

		if (!previewRoot) {
			return;
		}

		previewRoot.classList.remove('is-error');
		errorControl = findControl(previewRoot, [
			'[data-ph-template-editor-preview-error]',
			'[data-ph-template-preview-error]'
		]);

		if (errorControl && errorControl !== previewRoot) {
			errorControl.hidden = true;
			errorControl.setAttribute('aria-hidden', 'true');
		}
	}

	function updateFallbackLink(url) {
		var fallbackLink;

		if (!previewRoot) {
			return;
		}

		fallbackLink = findControl(previewRoot, [
			'[data-ph-template-editor-preview-fallback]',
			'[data-ph-template-preview-fallback]'
		]);

		if (fallbackLink && url) {
			fallbackLink.setAttribute('href', url);
		}
	}

	function getFrameDocument() {
		var frameWindow;
		var frameLocation;

		if (!previewFrame) {
			return null;
		}

		try {
			frameWindow = previewFrame.contentWindow;
			frameLocation = frameWindow && frameWindow.location ? frameWindow.location.href : '';

			if (!frameWindow || (frameLocation && !isSameOrigin(frameLocation))) {
				return null;
			}

			return previewFrame.contentDocument || frameWindow.document || null;
		} catch (error) {
			return null;
		}
	}

	function getFrameLink(eventTarget, frameRoot) {
		var current = eventTarget;

		while (current && current !== frameRoot) {
			if (current.nodeType === 1 && current.tagName && current.tagName.toLowerCase() === 'a') {
				return current;
			}

			current = current.parentNode;
		}

		return null;
	}

	function getFrameForm(eventTarget, frameRoot) {
		var current = eventTarget;

		while (current && current !== frameRoot) {
			if (current.nodeType === 1 && current.tagName && current.tagName.toLowerCase() === 'form') {
				return current;
			}

			current = current.parentNode;
		}

		return null;
	}

	function getDocumentBaseTarget(nextDocument) {
		var base = nextDocument ? nextDocument.querySelector('base[target]') : null;

		return base ? (base.getAttribute('target') || '').toLowerCase() : '';
	}

	function getCurrentFrameUrl() {
		var frameWindow;

		if (frameNavigationPending && storedPreviewUrl) {
			return storedPreviewUrl;
		}

		try {
			frameWindow = previewFrame && previewFrame.contentWindow;

			if (frameWindow && frameWindow.location && frameWindow.location.href && isSameOrigin(frameWindow.location.href)) {
				return frameWindow.location.href;
			}
		} catch (error) {
			// Fall back to the last URL owned by the parent controller.
		}

		return storedPreviewUrl || (previewFrame ? previewFrame.getAttribute('src') : '') || window.location.href;
	}

	function getMapSearchPreviewConfig() {
		return {
			queryArg: String(config.mapSearchPreviewQueryArg || ''),
			noneValue: 'none'
		};
	}

	function normalizeMapSearchFormat(value) {
		var mapPreview = getMapSearchPreviewConfig();

		value = String(value || '').toLowerCase();

		return ['view', 'split'].indexOf(value) !== -1 ? value : mapPreview.noneValue;
	}

	function getQueryParameterKey(part) {
		var separator = part.indexOf('=');
		var key = separator === -1 ? part : part.slice(0, separator);

		try {
			return decodeURIComponent(key.replace(/\+/g, ' '));
		} catch (error) {
			return key;
		}
	}

	function updateQueryParameterFallback(url, key, value, remove) {
		var anchor = document.createElement('a');
		var query = (anchor.search || '').replace(/^\?/, '');
		var parts = query ? query.split('&') : [];
		var encodedKey = encodeURIComponent(key);
		var nextParts = [];
		var replaced = false;

		anchor.href = url;
		query = (anchor.search || '').replace(/^\?/, '');
		parts = query ? query.split('&') : [];

		parts.forEach(function (part) {
			if (getQueryParameterKey(part) === key) {
				if (!remove && !replaced) {
					nextParts.push(encodedKey + '=' + encodeURIComponent(value));
				}

				replaced = true;
				return;
			}

			nextParts.push(part);
		});

		if (!remove && !replaced) {
			nextParts.push(encodedKey + '=' + encodeURIComponent(value));
		}

		return anchor.protocol + '//' + anchor.host + anchor.pathname + (nextParts.length ? '?' + nextParts.join('&') : '') + (anchor.hash || '');
	}

	function updateQueryParameter(url, key, value, remove) {
		var parsedUrl;

		if (!url || !key) {
			return url || '';
		}

		try {
			parsedUrl = new window.URL(url, window.location.href);

			if (!parsedUrl.searchParams) {
				throw new Error('URLSearchParams is unavailable.');
			}

			if (remove) {
				parsedUrl.searchParams.delete(key);
			} else {
				parsedUrl.searchParams.set(key, value);
			}

			return parsedUrl.href;
		} catch (error) {
			try {
				return updateQueryParameterFallback(url, key, value, remove);
			} catch (fallbackError) {
				return url;
			}
		}
	}

	function hasQueryParameter(url, key) {
		var parsedUrl;
		var query;

		if (!url || !key) {
			return false;
		}

		try {
			parsedUrl = new window.URL(url, window.location.href);

			if (parsedUrl.searchParams) {
				return parsedUrl.searchParams.has(key);
			}
		} catch (error) {
			// Use the anchor fallback below.
		}

		try {
			var anchor = document.createElement('a');
			anchor.href = url;
			query = (anchor.search || '').replace(/^\?/, '');

			return !!query && query.split('&').some(function (part) {
				return getQueryParameterKey(part) === key;
			});
		} catch (fallbackError) {
			return false;
		}
	}

	function getQueryParameterValue(url, key) {
		var parsedUrl;
		var query;
		var value = null;

		if (!url || !key) {
			return value;
		}

		try {
			parsedUrl = new window.URL(url, window.location.href);

			if (parsedUrl.searchParams) {
				return parsedUrl.searchParams.get(key);
			}
		} catch (error) {
			// Use the anchor fallback below.
		}

		try {
			var anchor = document.createElement('a');
			anchor.href = url;
			query = (anchor.search || '').replace(/^\?/, '');

			if (!query) {
				return value;
			}

			query.split('&').some(function (part) {
				var separator;
				var rawValue;

				if (getQueryParameterKey(part) !== key) {
					return false;
				}

				separator = part.indexOf('=');
				rawValue = separator === -1 ? '' : part.slice(separator + 1);

				try {
					value = decodeURIComponent(rawValue.replace(/\+/g, ' '));
				} catch (decodeError) {
					value = rawValue;
				}

				return true;
			});
		} catch (fallbackError) {
			return value;
		}

		return value;
	}

	function getNavigationPreviewUrl(url, options) {
		var targetUrl = url || storedPreviewUrl || window.location.href;
		var mapPreview = getMapSearchPreviewConfig();
		var currentUrl;
		var currentValue;

		if (!(options && options.preserveMapSearchPreview === false) && mapPreview.queryArg) {
			currentUrl = getCurrentFrameUrl();
			currentValue = getQueryParameterValue(currentUrl, mapPreview.queryArg);

			if (currentValue !== null && !hasQueryParameter(targetUrl, mapPreview.queryArg)) {
				targetUrl = updateQueryParameter(targetUrl, mapPreview.queryArg, currentValue, false);
			}
		}

		return getPreviewUrl(targetUrl);
	}

	function setMapSearchPreviewFormat(value, options) {
		var mapPreview = getMapSearchPreviewConfig();
		var currentUrl;
		var previewUrl;
		var format = normalizeMapSearchFormat(value);
		var forceReload = !options || options.force !== false;

		if (mirroringFrameControls || !mapPreview.queryArg) {
			return false;
		}

		currentUrl = getCurrentFrameUrl();
		previewUrl = updateQueryParameter(currentUrl, mapPreview.queryArg, format, false);

		// The add-on treats an explicit list request as incompatible with split
		// mode. Remove that stale toggle state so split renders its normal map.
		if (format === 'split' && getQueryParameterValue(previewUrl, 'view') === 'list') {
			previewUrl = updateQueryParameter(previewUrl, 'view', '', true);
		}

		return navigate(previewUrl, { force: forceReload });
	}

	function clearMapSearchPreviewFormat(options) {
		var mapPreview = getMapSearchPreviewConfig();
		var currentUrl;
		var previewUrl;
		var forceReload = !options || options.force !== false;

		if (!mapPreview.queryArg) {
			return false;
		}

		currentUrl = getCurrentFrameUrl();

		if (!hasQueryParameter(currentUrl, mapPreview.queryArg)) {
			return true;
		}

		previewUrl = updateQueryParameter(currentUrl, mapPreview.queryArg, '', true);

		return navigate(previewUrl, { force: forceReload, preserveMapSearchPreview: false });
	}

	function buildFrameFormSubmissionUrl(form, submitter, action) {
		action = action || form.action || getCurrentFrameUrl();
		var sourceUrl;
		var targetUrl;
		var formData;
		var mapPreview = getMapSearchPreviewConfig();
		var contextKeys = config.previewQueryArgs && typeof config.previewQueryArgs.slice === 'function' ? config.previewQueryArgs.slice() : ['ph_detail_template', 'ph_search_template', 'ph_module_template', 'ph_template_preview', 'ph_view'];

		if (mapPreview.queryArg && contextKeys.indexOf(mapPreview.queryArg) === -1) {
			contextKeys.push(mapPreview.queryArg);
		}

		if (!action || !isSameOrigin(action) || !window.URL || !window.FormData) {
			return '';
		}

		try {
			sourceUrl = new window.URL(getCurrentFrameUrl(), window.location.href);
			targetUrl = new window.URL(action, sourceUrl.href);
			targetUrl.search = '';
			formData = new window.FormData(form);

			if (submitter && submitter.name && !submitter.disabled) {
				if ((submitter.type || '').toLowerCase() === 'image') {
					formData.append(submitter.name + '.x', submitter.getAttribute('data-ph-template-editor-submit-x') || '0');
					formData.append(submitter.name + '.y', submitter.getAttribute('data-ph-template-editor-submit-y') || '0');
				} else {
					formData.append(submitter.name, submitter.value || '');
				}
			}

			formData.forEach(function (value, name) {
				if (typeof value !== 'string') {
					return;
				}

				targetUrl.searchParams.append(name, value);
			});

			contextKeys.filter(function (key) {
				return typeof key === 'string' && key;
			}).forEach(function (key) {
				if (sourceUrl.searchParams.has(key)) {
					targetUrl.searchParams.set(key, sourceUrl.searchParams.get(key));
				}
			});

			return getPreviewUrl(targetUrl.href);
		} catch (error) {
			return '';
		}
	}

	function retargetParentFormSubmission(form, submitter) {
		var control = submitter && submitter.hasAttribute('formtarget') ? submitter : form;
		var attribute = control === form ? 'target' : 'formtarget';
		var previousValue = control.getAttribute(attribute);

		control.setAttribute(attribute, '_blank');
		window.setTimeout(function () {
			if (previousValue === null) {
				control.removeAttribute(attribute);
			} else {
				control.setAttribute(attribute, previousValue);
			}
		}, 0);
	}

	function handleFrameFormSubmit(event, nextDocument) {
		var form = getFrameForm(event.target, nextDocument);
		var submitter = event.submitter || (lastFrameSubmitter && lastFrameSubmitter.form === form ? lastFrameSubmitter : null);
		var action;
		var method;
		var target;
		var targetsParent;
		var url;

		lastFrameSubmitter = null;

		if (!form || event.defaultPrevented) {
			return;
		}

		action = submitter && submitter.hasAttribute('formaction') ? submitter.formAction : form.action;
		method = (submitter && submitter.hasAttribute('formmethod') ? submitter.formMethod : (form.getAttribute('method') || 'get')).toLowerCase();
		if (submitter && submitter.hasAttribute('formtarget')) {
			target = submitter.formTarget;
		} else if (form.hasAttribute('target')) {
			target = form.getAttribute('target') || '';
		} else {
			target = getDocumentBaseTarget(nextDocument);
		}
		target = target.toLowerCase();
		targetsParent = target === '_top' || target === '_parent';

		if (method !== 'get') {
			if (targetsParent) {
				// Preview content must not replace the editor shell; preserve native submission in a new tab.
				retargetParentFormSubmission(form, submitter);
			}
			return;
		}

		if (target && target !== '_self' && !targetsParent) {
			return;
		}

		url = buildFrameFormSubmissionUrl(form, submitter, action);

		if (!url) {
			if (targetsParent) {
				// Cross-origin preview submissions remain native but cannot escape the editor shell.
				retargetParentFormSubmission(form, submitter);
			}
			return;
		}

		event.preventDefault();
		navigate(url, { force: true });
	}

	function bindFrameNavigation(nextDocument) {
		var documentElement = nextDocument ? nextDocument.documentElement : null;

		if (!documentElement || documentElement.getAttribute('data-ph-template-editor-frame-navigation-bound') === 'true') {
			return;
		}

		documentElement.setAttribute('data-ph-template-editor-frame-navigation-bound', 'true');
		nextDocument.addEventListener('click', function (event) {
			var link = getFrameLink(event.target, nextDocument);
			var submitter = event.target && event.target.closest ? event.target.closest('button, input') : null;
			var imageSubmitter = event.target && event.target.closest ? event.target.closest('input[type="image"]') : null;
			var target;
			var href;

			if (imageSubmitter && imageSubmitter.form) {
				imageSubmitter.setAttribute('data-ph-template-editor-submit-x', String(Math.max(0, Math.round(event.offsetX || 0))));
				imageSubmitter.setAttribute('data-ph-template-editor-submit-y', String(Math.max(0, Math.round(event.offsetY || 0))));
			}

			if (submitter && submitter.form && ((submitter.type || '').toLowerCase() === 'submit' || (submitter.type || '').toLowerCase() === 'image')) {
				lastFrameSubmitter = submitter;
				window.setTimeout(function () {
					if (lastFrameSubmitter === submitter) {
						lastFrameSubmitter = null;
					}
				}, 0);
			}

			if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.hasAttribute('download')) {
				return;
			}

			target = (link.hasAttribute('target') ? (link.getAttribute('target') || '') : getDocumentBaseTarget(nextDocument)).toLowerCase();

			if (target !== '_top' && target !== '_parent') {
				return;
			}

			href = link.href || link.getAttribute('href');

			if (!href) {
				return;
			}

			event.preventDefault();

			if (isSameOrigin(href)) {
				// Keep same-origin parent-target links inside the truthful preview context.
				navigate(href, { force: true });
				return;
			}

			// External parent-target links open separately so they cannot replace an editor with unsaved work.
			window.open(href, '_blank', 'noopener,noreferrer');
		});

		nextDocument.addEventListener('submit', function (event) {
			handleFrameFormSubmit(event, nextDocument);
		});
	}

	function querySearchForm(root, selector) {
		var form = null;

		if (!root) {
			return null;
		}

		if (selector) {
			try {
				form = root.querySelector(selector);
			} catch (error) {
				form = null;
			}
		}

		return form || root.querySelector('form.property-search-form');
	}

	function copySearchFormControlState(sourceForm, targetForm) {
		var sourceControls = sourceForm ? Array.prototype.slice.call(sourceForm.elements || []) : [];
		var targetControls = targetForm ? Array.prototype.slice.call(targetForm.elements || []) : [];
		var controlsByKey = {};

		sourceControls.forEach(function (sourceControl) {
			var key = (sourceControl.name || '') + '::' + (sourceControl.type || '');

			if (!sourceControl.name) {
				return;
			}

			controlsByKey[key] = controlsByKey[key] || [];
			controlsByKey[key].push(sourceControl);
		});

		targetControls.forEach(function (targetControl) {
			var key = (targetControl.name || '') + '::' + (targetControl.type || '');
			var sourceControl = controlsByKey[key] && controlsByKey[key].length ? controlsByKey[key].shift() : null;

			if (!sourceControl) {
				return;
			}

			if (sourceControl.type === 'checkbox' || sourceControl.type === 'radio') {
				targetControl.checked = sourceControl.checked;
			}

			if (sourceControl.tagName && sourceControl.tagName.toLowerCase() === 'select') {
				if (sourceControl.multiple) {
					Array.prototype.slice.call(targetControl.options).forEach(function (targetOption, optionIndex) {
						targetOption.selected = !!(sourceControl.options[optionIndex] && sourceControl.options[optionIndex].selected);
					});
				} else {
					targetControl.selectedIndex = sourceControl.selectedIndex;
				}
			} else if (sourceControl.type !== 'file') {
				targetControl.value = sourceControl.value;
			}
		});
	}

	function executeSearchFormScripts(nextDocument, nextForm) {
		Array.prototype.slice.call(nextForm.querySelectorAll('script')).forEach(function (script) {
			var executable = nextDocument.createElement('script');

			executable.text = script.text || script.textContent || '';
			nextDocument.body.appendChild(executable);
			nextDocument.body.removeChild(executable);
		});
	}

	function replaceFrameSearchForm(sourceForm, selector, stateSourceForm) {
		var nextDocument = getFrameDocument();
		var currentFrameForm;
		var importedForm;
		var frameWindow;

		if (!sourceForm || !nextDocument || !nextDocument.body) {
			return false;
		}

		currentFrameForm = querySearchForm(nextDocument, selector);

		if (!currentFrameForm || !currentFrameForm.parentNode) {
			return false;
		}

		importedForm = nextDocument.importNode ? nextDocument.importNode(sourceForm, true) : sourceForm.cloneNode(true);
		copySearchFormControlState(stateSourceForm || sourceForm, importedForm);
		currentFrameForm.parentNode.replaceChild(importedForm, currentFrameForm);
		executeSearchFormScripts(nextDocument, importedForm);
		frameWindow = nextDocument.defaultView || (previewFrame ? previewFrame.contentWindow : null);

		if (modules.searchFormBuilder && typeof modules.searchFormBuilder.initializeForm === 'function') {
			modules.searchFormBuilder.initializeForm(importedForm, frameWindow);
		}

		return true;
	}

	function mirrorSearchFormReplacement(event) {
		var detail = event && event.detail ? event.detail : {};
		var sourceForm = detail.form;
		var selector = detail.selector || '';

		window.setTimeout(function () {
			var currentSourceForm = querySearchForm(document, selector);

			if (!sourceForm || currentSourceForm !== sourceForm) {
				return;
			}

			replaceFrameSearchForm(sourceForm, selector, sourceForm);
		}, 0);
	}

	function bindSearchFormReplacementEvent() {
		if (searchFormReplacementHandlerBound) {
			return;
		}

		searchFormReplacementHandler = mirrorSearchFormReplacement;
		document.addEventListener('propertyhive_template_set_search_form_replaced', searchFormReplacementHandler);
		searchFormReplacementHandlerBound = true;
	}

	function initialiseFrameDocument() {
		var editor = document.querySelector('[data-ph-template-editor]');
		var form = editor ? editor.querySelector('[data-ph-template-editor-form]') : null;
		var searchFormConfig = config.searchFormEditor || {};
		var searchFormSelector = searchFormConfig.previewSelector || '.property-search-form';
		var sourceSearchForm;
		var loadedSearchForm;

		frameDocument = getFrameDocument();

		if (!frameDocument || !frameDocument.body) {
			setError('Preview could not be accessed. Open it in a new tab to continue.');
			return;
		}

		clearError();
		bindFrameNavigation(frameDocument);

		if (modules.editorPreview && typeof modules.editorPreview.mirrorControls === 'function') {
			// Frame-load reconciliation is a one-way DOM mirror. Keep it fenced from
			// the parent change handler so it cannot schedule another navigation.
			mirroringFrameControls = true;
			try {
				modules.editorPreview.mirrorControls(form, frameDocument);
			} finally {
				mirroringFrameControls = false;
			}
		}

		sourceSearchForm = querySearchForm(document, searchFormSelector);
		loadedSearchForm = querySearchForm(frameDocument, searchFormSelector);

		if (sourceSearchForm && loadedSearchForm) {
			replaceFrameSearchForm(sourceSearchForm, searchFormSelector, loadedSearchForm);
			frameDocument = getFrameDocument();
		}

		if (window.CustomEvent) {
			document.dispatchEvent(new window.CustomEvent('ph:template_set_preview_frame_loaded', {
				detail: { document: frameDocument, frame: previewFrame, device: state.device, width: getDevice().width }
			}));
		}
	}

	function handleFrameLoad() {
		frameNavigationPending = false;
		setLoading(false);
		initialiseFrameDocument();
		scheduleFitUpdate();
	}

	function handleFrameError() {
		frameNavigationPending = false;
		setError('Preview could not be loaded. Open it in a new tab to continue.');
	}

	function bindFrame() {
		if (!previewFrame) {
			return;
		}

		if (previewFrame.getAttribute('data-ph-template-editor-preview-bound') === 'true') {
			return;
		}

		frameLoadHandler = handleFrameLoad;
		frameErrorHandler = handleFrameError;
		previewFrame.addEventListener('load', frameLoadHandler);
		previewFrame.addEventListener('error', frameErrorHandler);
		previewFrame.setAttribute('data-ph-template-editor-preview-bound', 'true');
	}

	function navigate(url, options) {
		var previewUrl = getNavigationPreviewUrl(url, options);
		var currentFrameUrl;
		var forceReload = !!(options && options.force);

		if (!previewRoot || !previewFrame) {
			return false;
		}

		if (!previewUrl || !isSameOrigin(previewUrl)) {
			setError('Preview is unavailable on a different origin. Open the original page to continue.');
			return false;
		}

		storedPreviewUrl = previewUrl;
		updateFallbackLink(previewUrl);
		clearError();
		frameNavigationPending = true;
		setLoading(true, (config.labels && config.labels.loading) || 'Loading preview...');
		currentFrameUrl = getPreviewUrl(previewFrame.getAttribute('src') || '');

		if (!forceReload && currentFrameUrl === previewUrl && frameDocument) {
			frameNavigationPending = false;
			setLoading(false);
			return true;
		}

		previewFrame.setAttribute('src', previewUrl);
		return true;
	}

	function handlePreviewSwap(event) {
		var detail = event && event.detail ? event.detail : {};
		var url = detail.previewUrl || window.location.href;

		refresh();
		navigate(url, { force: true, preserveMapSearchPreview: false });
	}

	function bindPreviewSwapEvent() {
		if (previewSwapHandlerBound) {
			return;
		}

		document.addEventListener('ph:template_set_preview_swapped', handlePreviewSwap);
		previewSwapHandlerBound = true;
	}

	function init(nextConfig) {
		var root;
		var initialUrl;

		config = nextConfig || config || {};
		restoreState();
		bindPreviewSwapEvent();
		bindSearchFormReplacementEvent();
		root = getPreviewRoot();

		if (!root) {
			return false;
		}

		bindRoot(root);
		updateControls();

		if (!root.getAttribute('data-ph-template-editor-preview-initialized')) {
			root.setAttribute('data-ph-template-editor-preview-initialized', 'true');
			initialUrl = getInitialPreviewUrl();

			if (initialUrl) {
				navigate(initialUrl);
			}
		}

		return true;
	}

	function refresh() {
		var root = getPreviewRoot();

		if (!root) {
			return false;
		}

		bindRoot(root);
		updateControls();
		bindResizeHandling();
		scheduleFitUpdate();

		if (previewFrame && !previewFrame.getAttribute('src')) {
			navigate(getInitialPreviewUrl());
		}

		return true;
	}

	function destroy() {
		if (previewRoot && rootClickHandler) {
			previewRoot.removeEventListener('click', rootClickHandler);
		}

		if (previewFrame && frameLoadHandler) {
			previewFrame.removeEventListener('load', frameLoadHandler);
			previewFrame.removeEventListener('error', frameErrorHandler);
			previewFrame.removeAttribute('data-ph-template-editor-preview-bound');
		}

		disconnectResizeObserver();

		if (resizeHandlerBound) {
			window.removeEventListener('resize', scheduleFitUpdate);
			resizeHandlerBound = false;
		}

		if (previewSwapHandlerBound) {
			document.removeEventListener('ph:template_set_preview_swapped', handlePreviewSwap);
			previewSwapHandlerBound = false;
		}

		if (fitUpdateTimer) {
			if (window.cancelAnimationFrame) {
				window.cancelAnimationFrame(fitUpdateTimer);
			}
			window.clearTimeout(fitUpdateTimer);
			fitUpdateTimer = null;
		}

		if (searchFormReplacementHandlerBound && searchFormReplacementHandler) {
			document.removeEventListener('propertyhive_template_set_search_form_replaced', searchFormReplacementHandler);
			searchFormReplacementHandlerBound = false;
			searchFormReplacementHandler = null;
		}

		previewRoot = null;
		previewCanvas = null;
		previewFrame = null;
		frameDocument = null;
		rootInitialized = false;
		frameNavigationPending = false;
		lastFrameSubmitter = null;
		mirroringFrameControls = false;
	}

	modules.editorResponsivePreview = {
		buildPreviewUrl: getPreviewUrl,
		clearMapSearchPreviewFormat: clearMapSearchPreviewFormat,
		destroy: destroy,
		getDocument: function () {
			return frameDocument || getFrameDocument();
		},
		getMapSearchPreviewConfig: getMapSearchPreviewConfig,
		getState: function () {
			return { device: state.device, width: getDevice().width };
		},
		init: init,
		navigate: navigate,
		reconcileMapSearchPreview: clearMapSearchPreviewFormat,
		refresh: refresh,
		setError: setError,
		setLoading: setLoading,
		setMapSearchPreviewFormat: setMapSearchPreviewFormat
	};
}());
