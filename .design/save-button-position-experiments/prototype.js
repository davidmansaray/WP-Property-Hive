(function () {
    'use strict';

    var root = document.getElementById('prototype');
    if (!root) {
        return;
    }

    var variant = document.body.dataset.variant || 'title-row';
    var concepts = {
        'title-row': {
            number: '01',
            title: 'Refined title row',
            note: 'Familiar placement with tighter status and help grouping'
        },
        'sticky-toolbar': {
            number: '02',
            title: 'Sticky section toolbar',
            note: 'Save remains visible directly beneath the settings navigation'
        },
        'floating-rail': {
            number: '03',
            title: 'Floating action rail',
            note: 'A compact desktop utility that follows the viewport'
        },
        'bottom-dock': {
            number: '04',
            title: 'Bottom action dock',
            note: 'Persistent and close to the controls being edited'
        },
        'dirty-tray': {
            number: '05',
            title: 'Unsaved-changes tray',
            note: 'Appears only when the page has something to save'
        }
    };
    var concept = concepts[variant];

    var icon = function (name) {
        var icons = {
            gear: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.8 2.7h4.4l.7 2.1 1.6.9 2.1-.5 2.2 3.8-1.5 1.6v1.8l1.5 1.6-2.2 3.8-2.1-.5-1.6.9-.7 2.1H9.8l-.7-2.1-1.6-.9-2.1.5L3.2 14l1.5-1.6v-1.8L3.2 9l2.2-3.8 2.1.5 1.6-.9.7-2.1Z"/><circle cx="12" cy="11.5" r="3.2"/></svg>',
            office: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V6l7-3v18M11 9h9v12M2 21h20"/><path d="M7 8h1M7 12h1M7 16h1M15 12h1M15 16h1"/></svg>',
            sliders: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h5M12 6h9M3 12h10M17 12h4M3 18h3M10 18h11"/><circle cx="10" cy="6" r="2"/><circle cx="15" cy="12" r="2"/><circle cx="8" cy="18" r="2"/></svg>',
            window: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/></svg>',
            mail: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>',
            star: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z"/></svg>',
            wrench: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.5 5.5a5 5 0 0 0-6.6 6.6L3 17l4 4 4.9-4.9a5 5 0 0 0 6.6-6.6l-3 3-3-3 3-3Z"/></svg>',
            layers: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 16l9 5 9-5"/></svg>',
            puzzle: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6a2.5 2.5 0 1 1 4 0h6v6a2.5 2.5 0 1 1 0 4v6h-6a2.5 2.5 0 1 0-4 0H4v-6a2.5 2.5 0 1 1 0-4V4Z"/></svg>',
            check: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="m8.5 12 2.2 2.3 4.8-5"/></svg>',
            help: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.6 9a2.7 2.7 0 0 1 5.2 1c0 2-2.8 2.3-2.8 4"/><path d="M12 18h.01"/></svg>',
            close: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 7 10 10M17 7 7 17"/></svg>'
        };
        return icons[name];
    };

    var navItems = [
        ['gear', 'General', 'Core settings'],
        ['office', 'Offices', 'Manage offices'],
        ['sliders', 'Field Manager', 'Customise fields'],
        ['window', 'Frontend', 'Design templates'],
        ['mail', 'Emails', 'Email templates'],
        ['star', 'Features', 'Extra features'],
        ['wrench', 'License', 'Your license'],
        ['layers', 'Demo Data', 'Import sample data'],
        ['puzzle', 'Add-ons', 'Extension settings']
    ];

    var navMarkup = navItems.map(function (item, index) {
        return '<button class="primary-nav__item' + (index === 0 ? ' is-active' : '') + '" type="button">' +
            '<span class="primary-nav__icon">' + icon(item[0]) + '</span>' +
            '<span><strong>' + item[1] + (index === 8 ? ' <em>6</em>' : '') + '</strong><small>' + item[2] + '</small></span>' +
            (index === 8 ? '<span class="nav-chevron">⌄</span>' : '') +
        '</button>';
    }).join('');

    var saveButton = function (extraClass) {
        return '<button class="save-button ' + (extraClass || '') + '" type="button" data-save disabled>' +
            '<span class="save-button__icon">' + icon('check') + '</span>' +
            '<span class="save-button__label">Save changes</span>' +
        '</button>';
    };

    var pageHeaderActions = variant === 'title-row'
        ? '<div class="page-head__utility"><span class="save-state" data-state>All changes saved</span>' + saveButton() + '<button class="help-button" type="button" aria-label="Help">' + icon('help') + '</button></div>'
        : '<button class="help-button help-button--solo" type="button" aria-label="Help">' + icon('help') + '</button>';

    var stickyToolbar = variant === 'sticky-toolbar'
        ? '<div class="sticky-toolbar"><div><span class="toolbar-kicker">Settings</span><strong>General</strong><span class="save-state" data-state>All changes saved</span></div><div class="toolbar-actions"><button class="text-button" type="button" data-discard>Discard</button>' + saveButton() + '</div></div>'
        : '';

    var floatingRail = variant === 'floating-rail'
        ? '<aside class="action-rail" aria-label="Page actions"><span class="action-rail__status" data-state>Saved</span>' + saveButton('save-button--rail') + '<button class="rail-help" type="button">' + icon('help') + '<span>Help</span></button></aside>'
        : '';

    var bottomDock = variant === 'bottom-dock'
        ? '<div class="bottom-dock"><div class="bottom-dock__status"><span class="status-orb"></span><div><strong data-state>All changes saved</strong><small>Your settings are up to date</small></div></div><div class="bottom-dock__actions"><button class="text-button" type="button" data-discard>Discard</button>' + saveButton() + '</div></div>'
        : '';

    var dirtyTray = variant === 'dirty-tray'
        ? '<div class="dirty-tray" aria-hidden="true" aria-live="polite" inert><div class="dirty-tray__status"><span class="status-orb"></span><div><strong data-state>Unsaved changes</strong><small>Review your changes before leaving this page.</small></div></div><div class="dirty-tray__actions"><button class="text-button" type="button" data-discard>Discard</button>' + saveButton() + '</div></div>'
        : '';

    root.innerHTML =
        '<div class="prototype-shell">' +
            '<div class="wp-rail"><span>W</span><i></i></div>' +
            '<div class="concept-label"><a href="index.html">← All concepts</a><span>Concept ' + concept.number + '</span><strong>' + concept.title + '</strong><small>' + concept.note + '</small></div>' +
            '<div class="admin-page">' +
                '<section class="dev-banner">' +
                    '<div><strong>Development setup tools</strong><p>This site is marked as <code>local</code>, so administrators can restart the Property Hive setup wizard for testing.</p><button type="button">Restart setup wizard</button></div>' +
                    '<div class="screen-tools"><button type="button">Help⌄</button><button type="button">Recently Viewed⌄</button></div>' +
                '</section>' +
                '<nav class="primary-nav" aria-label="Settings categories">' + navMarkup + '</nav>' +
                stickyToolbar +
                '<main class="settings">' +
                    '<nav class="subnav" aria-label="General settings sections"><strong>General</strong><span>|</span><a href="#">Modules</a><span>|</span><a href="#">Map</a><span>|</span><a href="#">Media</a><span>|</span><a href="#">International</a><span>|</span><a href="#">GDPR</a><span>|</span><a href="#">CAPTCHA</a><span>|</span><a href="#">Text Substitution</a><span>|</span><a href="#">Miscellaneous</a></nav>' +
                    '<header class="page-head"><div><h1>General</h1><p>Configure the core Property Hive settings for your site.</p></div>' + pageHeaderActions + '</header>' +
                    '<form class="settings-form">' +
                        '<section class="form-section">' +
                            '<h2>General Options</h2>' +
                            '<div class="field-row"><div class="field-label">Active Departments</div><div class="field-control check-stack">' +
                                '<label><input type="checkbox" checked><span>Residential Sales</span></label>' +
                                '<label><input type="checkbox" checked><span>Residential Lettings</span></label>' +
                                '<label><input type="checkbox"><span>Commercial</span></label>' +
                                '<button class="add-link" type="button">+ Add Department</button>' +
                            '</div></div>' +
                            '<div class="field-row"><div class="field-label">Primary Department</div><div class="field-control radio-stack">' +
                                '<label><input type="radio" name="department" checked><span>Residential Sales</span></label>' +
                                '<label><input type="radio" name="department"><span>Residential Lettings</span></label>' +
                                '<label><input type="radio" name="department"><span>Commercial</span></label>' +
                            '</div></div>' +
                            '<div class="field-row field-row--inline"><label class="field-label" for="search-page">Property Search Results Page</label><div class="field-control inline-control">' +
                                '<select id="search-page"><option>Property Search</option><option>Available Properties</option><option>Search Results</option></select>' +
                                '<span>This sets the page of your property search results</span>' +
                            '</div></div>' +
                            '<div class="field-row"><label class="field-label" for="fees">Lettings Fees (Residential)</label><div class="field-control"><textarea id="fees" rows="5" placeholder="Enter any fees that should be displayed on residential lettings…"></textarea></div></div>' +
                        '</section>' +
                        '<section class="form-section form-section--secondary">' +
                            '<h2>Display Preferences</h2>' +
                            '<div class="field-row field-row--inline"><label class="field-label" for="currency">Default Currency</label><div class="field-control inline-control"><select id="currency"><option>GBP — Pound sterling</option><option>EUR — Euro</option><option>USD — US dollar</option></select><span>Used when no property-level currency is set</span></div></div>' +
                            '<div class="field-row"><div class="field-label">Property availability</div><div class="field-control check-stack"><label><input type="checkbox" checked><span>Hide unavailable properties from search</span></label><label><input type="checkbox"><span>Include Sold STC properties</span></label></div></div>' +
                        '</section>' +
                    '</form>' +
                '</main>' +
            '</div>' +
            floatingRail + bottomDock + dirtyTray +
            '<div class="save-toast" role="status">' + icon('check') + '<span>Settings saved</span></div>' +
        '</div>';

    var form = root.querySelector('.settings-form');
    var saveButtons = Array.prototype.slice.call(root.querySelectorAll('[data-save]'));
    var stateLabels = Array.prototype.slice.call(root.querySelectorAll('[data-state]'));
    var dirtyTrayElement = root.querySelector('.dirty-tray');
    var dirty = false;
    var saveTimer;

    function updateState(state) {
        stateLabels.forEach(function (label) {
            if (state === 'dirty') {
                label.textContent = variant === 'floating-rail' ? 'Unsaved' : 'Unsaved changes';
            } else if (state === 'saving') {
                label.textContent = 'Saving…';
            } else {
                label.textContent = variant === 'floating-rail' ? 'Saved' : 'All changes saved';
            }
        });
    }

    function setDirty(nextDirty) {
        dirty = nextDirty;
        document.body.classList.toggle('is-dirty', dirty);
        saveButtons.forEach(function (button) {
            button.disabled = !dirty;
        });
        if (dirtyTrayElement) {
            dirtyTrayElement.inert = !dirty;
            dirtyTrayElement.setAttribute('aria-hidden', dirty ? 'false' : 'true');
        }
        updateState(dirty ? 'dirty' : 'saved');
    }

    form.addEventListener('input', function () {
        setDirty(true);
    });
    form.addEventListener('change', function () {
        setDirty(true);
    });

    root.addEventListener('click', function (event) {
        var save = event.target.closest('[data-save]');
        var discard = event.target.closest('[data-discard]');

        if (save && dirty) {
            window.clearTimeout(saveTimer);
            document.body.classList.add('is-saving');
            saveButtons.forEach(function (button) {
                button.disabled = true;
                button.querySelector('.save-button__label').textContent = 'Saving…';
            });
            updateState('saving');

            saveTimer = window.setTimeout(function () {
                document.body.classList.remove('is-saving');
                setDirty(false);
                saveButtons.forEach(function (button) {
                    button.querySelector('.save-button__label').textContent = 'Save changes';
                });
                var toast = root.querySelector('.save-toast');
                toast.classList.add('is-visible');
                window.setTimeout(function () {
                    toast.classList.remove('is-visible');
                }, 1800);
            }, 650);
        }

        if (discard) {
            form.reset();
            setDirty(false);
        }
    });

    setDirty(false);
}());
