(function () {
	'use strict';

	var modules = window.phTemplateSetModules = window.phTemplateSetModules || {};

	function removePrefixedClass(element, prefix) {
		Array.prototype.slice.call(element.classList).forEach(function (className) {
			if (className.indexOf(prefix) === 0) {
				element.classList.remove(className);
			}
		});
	}

	function setBodyOption(prefix, value) {
		removePrefixedClass(document.body, prefix);
		document.body.classList.add(prefix + value);
	}

	function setSearchOption(prefix, value) {
		setBodyOption(prefix, value);

		document.querySelectorAll('.ph-template-search').forEach(function (search) {
			removePrefixedClass(search, prefix);
			search.classList.add(prefix + value);
		});
	}

	function setBodyToggle(showClass, hideClass, enabled) {
		document.body.classList.toggle(showClass, enabled);
		document.body.classList.toggle(hideClass, !enabled);
	}

	function isEnabledValue(value, control) {
		if (control && control.type === 'checkbox') {
			return control.checked;
		}

		return ['yes', '1', 'true', 'on'].indexOf(String(value || '').toLowerCase()) !== -1;
	}

	function setPreviewElementHidden(element, hidden) {
		var originalAriaHidden;

		if (!element) {
			return;
		}

		if (hidden) {
			if (!element.hasAttribute('data-ph-template-editor-original-hidden')) {
				element.setAttribute('data-ph-template-editor-original-hidden', element.hidden ? 'true' : 'false');
				originalAriaHidden = element.getAttribute('aria-hidden');
				element.setAttribute('data-ph-template-editor-original-aria-hidden', originalAriaHidden === null ? '' : originalAriaHidden);
			}

			element.hidden = true;
			element.setAttribute('aria-hidden', 'true');
			return;
		}

		element.hidden = element.getAttribute('data-ph-template-editor-original-hidden') === 'true';
		originalAriaHidden = element.getAttribute('data-ph-template-editor-original-aria-hidden');

		if (originalAriaHidden) {
			element.setAttribute('aria-hidden', originalAriaHidden);
		} else {
			element.removeAttribute('aria-hidden');
		}

		element.removeAttribute('data-ph-template-editor-original-hidden');
		element.removeAttribute('data-ph-template-editor-original-aria-hidden');
	}

	function getPositiveIntegerList(value) {
		var values = String(value || '').split(',').map(function (item) {
			return parseInt(item.trim(), 10);
		}).filter(function (item) {
			return item > 0;
		});

		return values.filter(function (item, index) {
			return values.indexOf(item) === index;
		});
	}

	function getSearchPromoPosition(promo) {
		var position = 0;
		var sibling = promo ? promo.previousElementSibling : null;

		while (sibling) {
			if (sibling.matches && sibling.matches('li') && !sibling.matches('.ph-template-search-promo, li.promo')) {
				position += 1;
			}
			sibling = sibling.previousElementSibling;
		}

		return position;
	}

	function applySearchPromoPositions(value) {
		var positions = getPositiveIntegerList(value);

		document.querySelectorAll('.ph-template-search li.ph-template-search-promo, .ph-template-search li.promo').forEach(function (promo) {
			var position = getSearchPromoPosition(promo);

			if (position > 0) {
				setPreviewElementHidden(promo, positions.length > 0 && positions.indexOf(position) === -1);
			}
		});
	}

	function setLinkLabel(link, value) {
		var label = String(value || '').trim() || 'Print Details';
		var textNode = Array.prototype.slice.call(link.childNodes).filter(function (node) {
			return node.nodeType === 3 && node.nodeValue.trim();
		})[0];

		if (textNode) {
			textNode.nodeValue = label;
		} else {
			link.textContent = label;
		}
	}

	function applyPrintableBrochureLinkText(value) {
		document.querySelectorAll('.action-printable-brochure a, [data-ph-template-printable-brochure]').forEach(function (link) {
			setLinkLabel(link, value);
		});
	}

	function applyRecommendedCount(value) {
		var limit = parseInt(value, 10) || 3;

		document.querySelectorAll('[data-ph-recommended-properties]').forEach(function (section) {
			Array.prototype.slice.call(section.querySelectorAll('[data-ph-recommended-card]')).forEach(function (card, index) {
				card.hidden = index >= limit;
			});
		});
	}

	function resetGalleryPanel(panelName) {
		if (modules.gallery && typeof modules.gallery.resetPanel === 'function') {
			modules.gallery.resetPanel(panelName);
		}
	}

	function getSelectedOption(control) {
		if (!control || typeof control.selectedIndex !== 'number' || control.selectedIndex < 0) {
			return null;
		}

		return control.options[control.selectedIndex] || null;
	}

	function isTemplatePreviewControl(control) {
		return !!control && (control.name === 'template_set_detail_template' || control.name === 'template_set_search_template');
	}

	function getTemplatePreviewUrl(control) {
		var selectedOption;

		if (!isTemplatePreviewControl(control)) {
			return '';
		}

		selectedOption = getSelectedOption(control);

		return selectedOption ? selectedOption.getAttribute('data-ph-template-preview-url') : '';
	}

	function maybeNavigateTemplatePreview(control) {
		var previewUrl = getTemplatePreviewUrl(control);

		if (!previewUrl || previewUrl === window.location.href) {
			return false;
		}

		window.location.href = previewUrl;
		return true;
	}

	function updateSegmentedControl(input) {
		var group = input.closest('.ph-template-editor-segmented');

		if (!group) {
			return;
		}

		group.querySelectorAll('label').forEach(function (label) {
			var labelInput = label.querySelector('input');
			label.classList.toggle('is-active', !!labelInput && labelInput.checked);
		});
	}


	var editorControlHandlers = {
		template_set_gallery_layout: function (value, control) {
			if (modules.gallery && typeof modules.gallery.setVariant === 'function') {
				modules.gallery.setVariant(value, false);
			}
			updateSegmentedControl(control);
		},
		template_set_button_style: function (value) {
			setBodyOption('ph-template-buttons-', value);
		},
		template_set_search_layout: function (value) {
			setSearchOption('ph-search-view-', value);
			setSearchOption('ph-search-layout-', value);
		},
		template_set_search_card_size: function (value) {
			setSearchOption('ph-search-card-size-', value);
		},
		template_set_search_grid_columns: function (value) {
			setSearchOption('ph-search-grid-columns-', value);
		},
		template_set_image_style: function (value) {
			setBodyOption('ph-template-images-', value);
		},
		template_set_contact_card_style: function (value) {
			setBodyOption('ph-template-contact-card-', value);
		},
		template_set_show_branch: function (value) {
			setBodyToggle('ph-template-show-branch', 'ph-template-hide-branch', value === 'yes');
		},
		template_set_show_badges: function (value) {
			setBodyToggle('ph-template-show-badges', 'ph-template-hide-badges', value === 'yes');
		},
		template_set_show_mobile_cta: function (value) {
			setBodyToggle('ph-template-show-mobile-cta', 'ph-template-hide-mobile-cta', value === 'yes');
		},
		template_set_show_floorplans: function (value) {
			setBodyToggle('ph-template-show-floorplans', 'ph-template-hide-floorplans', value === 'yes');

			if (value !== 'yes') {
				resetGalleryPanel('floorplan');
			}
		},
		template_set_show_virtual_tours: function (value) {
			setBodyToggle('ph-template-show-virtual-tours', 'ph-template-hide-virtual-tours', value === 'yes');

			if (value !== 'yes') {
				resetGalleryPanel('virtual-tour');
			}
		},
		template_set_show_recommended: function (value) {
			setBodyToggle('ph-template-show-recommended', 'ph-template-hide-recommended', value === 'yes');
		},
		template_set_recommended_count: function (value) {
			setBodyOption('ph-template-recommended-count-', value);
			applyRecommendedCount(value);
		},
		template_set_recommended_layout: function (value) {
			setBodyOption('ph-template-recommended-layout-', value);
		},
		template_set_recommended_image_size: function (value) {
			setBodyOption('ph-template-recommended-images-', value);
		},
		template_set_portal_show_costs: function (value) {
			setBodyToggle('ph-template-show-portal-costs', 'ph-template-hide-portal-costs', value === 'yes');
		},
		template_set_cinema_card_position: function (value) {
			setBodyOption('ph-template-cinema-card-', value);
		},
		template_set_editorial_show_brief: function (value) {
			setBodyToggle('ph-template-show-editorial-brief', 'ph-template-hide-editorial-brief', value === 'yes');
		},
		'ph_template_set_addons[map_search][format]': function (value) {
			var format = ['view', 'split'].indexOf(String(value || '')) !== -1 ? String(value) : 'none';

			setBodyOption('ph-template-map-search-format-', format);
		},
		'ph_template_set_addons[radial_search][current_location_enabled]': function (value, control) {
			setBodyToggle('ph-template-show-radial-current-location', 'ph-template-hide-radial-current-location', isEnabledValue(value, control));
		},
		'ph_template_set_addons[search_results_promos][positions]': function (value) {
			applySearchPromoPositions(value);
		},
		'ph_template_set_addons[printable_brochures][display]': function (value) {
			var display = ['if_none', 'no'].indexOf(String(value || '')) !== -1 ? String(value) : 'always';

			setBodyOption('ph-template-printable-brochures-display-', display);
		},
		'ph_template_set_addons[printable_brochures][link_text]': function (value) {
			applyPrintableBrochureLinkText(value);
		}
	};

	function applyEditorControl(control) {
		var handler;
		var value;

		if (!control || !control.name) {
			return;
		}

		if (control.type === 'radio' && !control.checked) {
			return;
		}

		handler = editorControlHandlers[control.name];
		if (!handler) {
			return;
		}

		value = control.type === 'checkbox' ? (control.checked ? 'yes' : '') : control.value;
		handler(value, control);
	}

	modules.editorPreview = {
		applyControl: applyEditorControl,
		getTemplatePreviewUrl: getTemplatePreviewUrl,
		isTemplatePreviewControl: isTemplatePreviewControl,
		maybeNavigateTemplatePreview: maybeNavigateTemplatePreview
	};
}());
