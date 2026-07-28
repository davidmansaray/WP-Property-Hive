(function () {
	'use strict';

	var modules = window.phTemplateSetModules = window.phTemplateSetModules || {};

	function setActive(surface, homeId) {
		if (!surface) {
			return;
		}

		surface.querySelectorAll('.ph-template-map-pin, ul.properties > li.ph-template-card').forEach(function (item) {
			var pinId = item.getAttribute('data-pin');
			var home = item.querySelector('[data-home]');
			var itemId = pinId || (home ? home.getAttribute('data-home') : '');
			item.classList.toggle('is-active', itemId === homeId);
		});
	}

	function init(scope) {
		(scope || document).querySelectorAll('.ph-search-template-map-led-search-results').forEach(function (surface) {
			if (surface.phTemplateMapRailReady) {
				return;
			}

			if (!surface.querySelector('.ph-template-map-panel')) {
				return;
			}

			surface.phTemplateMapRailReady = true;
			surface.querySelectorAll('ul.properties > li.ph-template-card').forEach(function (card) {
				var home = card.querySelector('[data-home]');
				var homeId = home ? home.getAttribute('data-home') : '';

				if (!homeId) {
					return;
				}

				card.setAttribute('tabindex', '-1');

				['mouseenter', 'focusin'].forEach(function (eventName) {
					card.addEventListener(eventName, function () {
						setActive(surface, homeId);
					});
				});

				['mouseleave', 'focusout'].forEach(function (eventName) {
					card.addEventListener(eventName, function () {
						window.setTimeout(function () {
							if (!card.matches(':hover') && !card.contains(document.activeElement)) {
								setActive(surface, '');
							}
						}, 0);
					});
				});
			});

			surface.querySelectorAll('.ph-template-map-pin[data-pin]').forEach(function (pin) {
				pin.addEventListener('mouseenter', function () {
					setActive(surface, pin.getAttribute('data-pin'));
				});
				pin.addEventListener('mouseleave', function () {
					setActive(surface, '');
				});
				pin.addEventListener('click', function () {
					var card = Array.prototype.slice.call(surface.querySelectorAll('ul.properties > li.ph-template-card')).find(function (candidate) {
						var home = candidate.querySelector('[data-home]');
						return home && home.getAttribute('data-home') === pin.getAttribute('data-pin');
					});

					if (card) {
						card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
						card.focus({ preventScroll: true });
					}
				});
			});
		});
	}

	modules.searchMapRail = { init: init };
}());
