var initial_save_changes_value = '';

jQuery( function($){

    var $settings_form = $('#mainform');
    var $settings_wrap = $settings_form.closest('.ph-settings-redesign');
    var $development_tools = $('[data-ph-development-tools]');
    var $save_tray = $settings_form.find('[data-ph-save-tray]');
    var $save_button = $settings_form.find('[data-ph-save-button]');
    var $save_state = $settings_form.find('[data-ph-save-state]');
    var $save_toast = $settings_form.find('[data-ph-save-toast]');
    var $save_discard = $settings_form.find('[data-ph-save-discard]');
    var save_tray_initial_state = '';
    var save_tray_state_ready = false;
    var save_tray_dirty = false;
    var save_tray_submitting = false;
    var save_tray_check_timer;

    function ph_init_development_tools()
    {
        if ( !$development_tools.length )
        {
            return;
        }

        var $toggle = $development_tools.find('[data-ph-development-tools-toggle]');
        var $panel = $development_tools.find('[data-ph-development-tools-panel]');
        var $close = $development_tools.find('[data-ph-development-tools-close]');

        function ph_set_development_tools_open(is_open, restore_focus)
        {
            $development_tools.toggleClass('is-open', is_open);
            $toggle.attr('aria-expanded', is_open ? 'true' : 'false');
            $panel.prop('hidden', !is_open);

            if ( !is_open && restore_focus )
            {
                $toggle.trigger('focus');
            }
        }

        $toggle.on('click.phDevelopmentTools', function()
        {
            ph_set_development_tools_open($toggle.attr('aria-expanded') !== 'true', false);
        });

        $close.on('click.phDevelopmentTools', function()
        {
            ph_set_development_tools_open(false, true);
        });

        $(document).on('click.phDevelopmentTools', function(event)
        {
            if (
                $toggle.attr('aria-expanded') === 'true' &&
                !$(event.target).closest('[data-ph-development-tools]').length
            )
            {
                ph_set_development_tools_open(false, false);
            }
        });

        $(document).on('keydown.phDevelopmentTools', function(event)
        {
            if ( event.key === 'Escape' && $toggle.attr('aria-expanded') === 'true' )
            {
                event.preventDefault();
                ph_set_development_tools_open(false, true);
            }
        });
    }

    function ph_sync_development_tools_with_save_tray(is_visible)
    {
        $development_tools.toggleClass('ph-development-tools--save-tray-visible', is_visible);
    }

    function ph_get_save_tray_form_state()
    {
        var state = [];

        $settings_form.find(':input').not(':button, :submit, :reset').each(function()
        {
            if ( !this.name )
            {
                return;
            }

            var $field = $(this);
            var field_type = (this.type || this.tagName || '').toLowerCase();
            var field_value;

            if ( field_type == 'checkbox' || field_type == 'radio' )
            {
                field_value = (this.checked ? 'checked:' : 'unchecked:') + $field.val();
            }
            else if ( field_type == 'file' )
            {
                field_value = Array.prototype.map.call(this.files || [], function(file)
                {
                    return file.name + ':' + file.size + ':' + file.lastModified;
                });
            }
            else
            {
                field_value = $field.val();
            }

            state.push([this.name, field_type, field_value]);
        });

        if ( typeof window.tinymce !== 'undefined' && window.tinymce.editors )
        {
            Array.prototype.forEach.call(window.tinymce.editors, function(editor)
            {
                var editor_element = editor.getElement ? editor.getElement() : null;

                if ( editor_element && $.contains($settings_form[0], editor_element) )
                {
                    state.push(['tinymce:' + editor.id, 'editor', editor.getContent()]);
                }
            });
        }

        return JSON.stringify(state);
    }

    function ph_set_save_tray_dirty(is_dirty)
    {
        if ( !$save_tray.length || save_tray_submitting )
        {
            return;
        }

        var is_action_save = $save_tray.is('[data-ph-save-always-active="true"]') ||
            ( initial_save_changes_value && $save_button.val() !== initial_save_changes_value );
        var should_show = is_dirty || is_action_save;

        save_tray_dirty = is_dirty;
        $settings_wrap.toggleClass('ph-save-tray-visible', should_show);
        ph_sync_development_tools_with_save_tray(should_show);
        $save_tray.prop('inert', !should_show).attr('aria-hidden', should_show ? 'false' : 'true');
        $save_button.prop('disabled', !should_show);
        $save_discard.prop('disabled', !is_dirty);

        if ( is_dirty )
        {
            $save_state.text(propertyhive_admin_settings.unsaved_changes_text);
        }
        else if ( is_action_save )
        {
            $save_state.text(propertyhive_admin_settings.action_ready_text);
        }
    }

    function ph_check_save_tray_state()
    {
        if ( !save_tray_state_ready || save_tray_submitting )
        {
            return;
        }

        ph_set_save_tray_dirty(ph_get_save_tray_form_state() !== save_tray_initial_state);
    }

    function ph_schedule_save_tray_check()
    {
        window.clearTimeout(save_tray_check_timer);
        save_tray_check_timer = window.setTimeout(ph_check_save_tray_state, 0);
    }

    function ph_bind_save_tray_editor(editor)
    {
        if ( !editor || editor.__propertyhiveSaveTrayBound )
        {
            return;
        }

        var editor_element = editor.getElement ? editor.getElement() : null;
        if ( !editor_element || !$.contains($settings_form[0], editor_element) )
        {
            return;
        }

        editor.__propertyhiveSaveTrayBound = true;
        editor.on('input change undo redo', ph_schedule_save_tray_check);
    }

    function ph_show_saved_toast()
    {
        if ( !$save_toast.length )
        {
            return;
        }

        var $saved_notice = $('#message.updated').filter(function()
        {
            return $(this).text().indexOf(propertyhive_admin_settings.saved_message) !== -1;
        }).first();

        if ( !$saved_notice.length )
        {
            return;
        }

        $saved_notice.addClass('ph-save-notice-consumed').attr('aria-hidden', 'true');
        $save_toast.addClass('is-visible').attr('aria-hidden', 'false');

        window.setTimeout(function()
        {
            $save_toast.removeClass('is-visible').attr('aria-hidden', 'true');
        }, 1800);
    }

    function ph_init_save_tray()
    {
        if ( !$save_tray.length )
        {
            return;
        }

        save_tray_initial_state = ph_get_save_tray_form_state();
        save_tray_state_ready = true;
        ph_set_save_tray_dirty(false);

        $settings_form.on('input.phSaveTray change.phSaveTray', ':input', ph_schedule_save_tray_check);
        $settings_form.on('click.phSaveTray', '[data-ph-save-discard]', function()
        {
            if ( !save_tray_dirty )
            {
                return;
            }

            ph_set_save_tray_dirty(false);
            window.setTimeout(function()
            {
                window.location.replace(window.location.pathname + window.location.search + window.location.hash);
            }, 280);
        });

        $settings_form.on('ph-save-action-change.phSaveTray', ph_schedule_save_tray_check);

        if ( typeof window.MutationObserver !== 'undefined' )
        {
            var save_tray_observer = new MutationObserver(ph_schedule_save_tray_check);
            save_tray_observer.observe($settings_form[0], { childList: true, subtree: true });
        }

        $(document).on('tinymce-editor-init.phSaveTray', function(event, editor)
        {
            ph_bind_save_tray_editor(editor);
        });

        if ( typeof window.tinymce !== 'undefined' && window.tinymce.editors )
        {
            Array.prototype.forEach.call(window.tinymce.editors, ph_bind_save_tray_editor);
        }
    }

    // sortable custom field tables

    $('.ph_customfields.sortable-custom-field').each(function()
    { 
        var taxonomy = $(this).data('taxonomy');

        if ( taxonomy == '' )
        {
            return;
        }

        $(this).find('tbody').sortable({
            axis: 'y',
            update: function (event, ui) 
            {
                var data = $(this).sortable('serialize') + '&'+ $.param({ 'action': 'propertyhive_save_term_order', 'taxonomy': taxonomy, 'security': propertyhive_admin_settings.ajax_nonce });

                $.ajax({
                    data: data,
                    type: 'POST',
                    url: ajaxurl
                });
            }
        });
    });

    //

    initial_save_changes_value = (jQuery('[data-ph-save-button-label]').length > 0) ? jQuery('[data-ph-save-button-label]').first().text() : '';

    $('a#add_department').click(function(e)
    {
        e.preventDefault();

        var new_department_html = $('#active_department_template').html();

        new_department_html = new_department_html.replace(/template/g, 'phnew-' + $('#propertyhive_new_custom_departments').val());

        $('#active_departments').append(new_department_html);

        var new_custom_departments = $('#propertyhive_custom_departments').val();
        if ( new_custom_departments != '' )
        {
            new_custom_departments += ',';
        }
        new_custom_departments += 'phnew-' + $('#propertyhive_new_custom_departments').val();
        $('#propertyhive_custom_departments').val( new_custom_departments );

        $('#propertyhive_new_custom_departments').val( parseInt($('#propertyhive_new_custom_departments').val()) + 1 );
    });
    
    $(document).on('click', 'a.delete-department', function(e)
    {
        e.preventDefault();

        var confirmBox = confirm('Are you sure you wish to delete this department?');

        if (confirmBox)
        {
            var custom_department_key = $(this).attr('data-department');

            $('#propertyhive_active_department_fieldset_' + custom_department_key).remove();

            var new_custom_departments = '';
            var existing_new_custom_departments = $('#propertyhive_custom_departments').val().split(",");
            for ( var i in existing_new_custom_departments )
            {
                if ( existing_new_custom_departments[i] != custom_department_key )
                {
                    if ( new_custom_departments != '' )
                    {
                        new_custom_departments += ',';
                    }
                    new_custom_departments += existing_new_custom_departments[i];
                }
            }
            $('#propertyhive_custom_departments').val( new_custom_departments );
        }
    });

    $('input.colorpick').wpColorPicker();

    $('form').submit(function(event)
    {
        if ( this.id == 'mainform' && $(this).data('ph-save-tray-native-submit') )
        {
            return true;
        }

        // Check for confirm removal checkbox
        // and make sure it's ticked
        if ( $('input[type=\'checkbox\'][name=\'confirm_removal\']').length > 0 )
        {
            if ( !$('input[type=\'checkbox\'][name=\'confirm_removal\']').is( ":checked" ) )
            {
                alert( propertyhive_admin_settings.confirm_not_selected_warning );
                return false;
            }
        }

        // Make sure a department has been ticked
        if ( $('input[type=\'checkbox\'][name^=\'propertyhive_active_departments_\']').length > 0 )
        {
            var department_ticked = false;
            $('input[type=\'checkbox\'][name^=\'propertyhive_active_departments_\']').each(function()
            {
                if ( $(this).is( ":checked" ) )
                {
                    department_ticked = true;
                }
            });

            if ( !department_ticked )
            {
                alert( propertyhive_admin_settings.no_departments_selected_warning );
                return false;
            }

            // Make sure primary department is in the list of ticked departments
            var selected_primary_department = $("input[type=\'radio\'][name=\'propertyhive_primary_department\']:checked").val();
            selected_primary_department = selected_primary_department.replace("residential-", "");
            if ( !$('input[type=\'checkbox\'][name=\'propertyhive_active_departments_' + selected_primary_department + '\']').is( ":checked" ) )
            {
                alert( propertyhive_admin_settings.primary_department_not_active_warning );
                return false;
            }
        }

        // Validate disabled modules
        if ( $('input[type=\'checkbox\'][name^=\'propertyhive_module_disabled_\']').length > 0 )
        {
            if ( 
                $('input[type=\'checkbox\'][name=\'propertyhive_module_disabled_contacts\']').is( ":checked" ) &&
                (
                    !$('input[type=\'checkbox\'][name=\'propertyhive_module_disabled_viewings\']').is( ":checked" ) ||
                    !$('input[type=\'checkbox\'][name=\'propertyhive_module_disabled_offers_sales\']').is( ":checked" )
                )
            )
            {
                alert( 'The contacts module must be enabled in order to use the viewings, offers and sales modules' );
                return false;
            }
        };

        if ( $('select[name=\'propertyhive_default_country\']').length > 0 )
        {
            // Make sure default country is in list of selected countries
            var selected_countries = $('select[name=\'propertyhive_countries[]\']').val();
            if ( selected_countries == null )
            {
                alert( propertyhive_admin_settings.no_countries_selected );
                return false;
            }
            var default_country = $('select[name=\'propertyhive_default_country\']').val();
            var default_in_selected = false;
            for ( var i in selected_countries )
            {
                if ( default_country == selected_countries[i] )
                {
                    default_in_selected = true;
                }
            }
            if ( !default_in_selected )
            {
                alert( propertyhive_admin_settings.default_country_not_in_selected );
                return false;
            }
        }

        if ( this.id == 'mainform' && $save_tray.length )
        {
            event.preventDefault();

            save_tray_submitting = true;
            $settings_wrap
                .addClass('ph-save-tray-saving')
                .addClass('ph-save-tray-visible');
            ph_sync_development_tools_with_save_tray(true);
            $save_tray.prop('inert', false).attr('aria-hidden', 'false');
            $save_state.text(propertyhive_admin_settings.saving_text);
            $save_button.prop('disabled', true);
            $save_button.find('[data-ph-save-button-label]').text(propertyhive_admin_settings.saving_text);

            window.setTimeout(function()
            {
                $settings_wrap
                    .removeClass('ph-save-tray-visible')
                    .removeClass('ph-save-tray-saving');
                ph_sync_development_tools_with_save_tray(false);
            }, 300);

            window.setTimeout(function()
            {
                if ( !$settings_form.find('[data-ph-save-post-value]').length )
                {
                    $('<input>', {
                        type: 'hidden',
                        name: 'save',
                        value: $save_button.val() || initial_save_changes_value,
                        'data-ph-save-post-value': ''
                    }).appendTo($settings_form);
                }

                $settings_form.data('ph-save-tray-native-submit', true);

                if ( typeof $settings_form[0].requestSubmit === 'function' )
                {
                    $settings_form[0].requestSubmit();
                }
                else
                {
                    $settings_form[0].submit();
                }
            }, 560);

            return false;
        }

        // Disable submit button when form is being submitted to prevent double submissions
        $('p.submit input[type=\'submit\']').attr('disabled', 'disabled');
    });

    $('a.batch-delete').click(function()
    {
        var term_ids = new Array;

        $('input[name=\'term_id[]\']:checked').each(function()
        {
            term_ids.push( $(this).val() );
        });
        
        if ( term_ids.length > 0 )
        {
            window.location.href = propertyhive_admin_settings.admin_url + 'admin.php?page=ph-settings&tab=customfields&section=' + propertyhive_admin_settings.taxonomy_section + '-delete&id=' + term_ids.join("-");
        }

        return false;
    });

    $('.select_all').change(function()
    {
        if ( this.checked )
        {
            $('input[name=\'term_id[]\']').attr('checked', 'checked');

            // If at least one has been checked, enable Delete Selected button
            if ( $('input[name=\'term_id[]\']:checked').length > 0 )
            {
                $('a.batch-delete').attr('disabled', false);
            }
        }
        else
        {
            $('input[name=\'term_id[]\']').removeAttr('checked');

            // Disable Delete Selected button
            $('a.batch-delete').attr('disabled', 'disabled');
        }
    });

    $('input[name=\'term_id[]\']').change(function()
    {
        if ( $('input[name=\'term_id[]\']:checked').length > 0 )
        {
            $('a.batch-delete').attr('disabled', false);
        }
        else
        {
            $('a.batch-delete').attr('disabled', 'disabled');
        }

        // If we're unchecking a term, uncheck the Select All box
        if ( !this.checked )
        {
            $('.select_all').removeAttr('checked');
        }
    });

    jQuery('select[name=\'propertyhive_countries[]\']').change(function()
    {
        fill_search_form_currency_options();
    });
    fill_search_form_currency_options();

    jQuery('input[name^=\'propertyhive_active_departments\']').change(function()
    {
        toggle_department_specific_options();
    });
    toggle_department_specific_options();

    jQuery('[name=\'propertyhive_maps_provider\']').change(function()
    {
        ph_toggle_maps_provider_options();
    });
    ph_toggle_maps_provider_options();

    jQuery('[name=\'propertyhive_geocoding_provider\']').change(function()
    {
        ph_toggle_geocoding_provider_options();
    });
    ph_toggle_geocoding_provider_options();

    jQuery('[name=\'propertyhive_auto_incremental_reference_numbers\']').change(function()
    {
        ph_toggle_auto_incremental_reference_number_options();
    });
    ph_toggle_auto_incremental_reference_number_options();

    jQuery('.pro-feature-settings .pro-filters ul li a').click(function(e)
    {
        e.preventDefault();

        var data_filter = jQuery(this).data('filter');

        jQuery('.pro-feature-settings .pro-filters ul li').removeClass('active');
        jQuery(this).parent().addClass('active');

        if ( data_filter == '' )
        {
            jQuery('.pro-feature-settings .pro-features ul li').hide();
            jQuery('.pro-feature-settings .pro-features ul li').fadeIn('fast');
        }
        else
        {
            jQuery('.pro-feature-settings .pro-features ul li').hide();
            jQuery('.pro-feature-settings .pro-features ul li.' + data_filter).fadeIn('fast');
        }

        ph_resize_pro_features_list();
    });

    if ( jQuery('.pro-feature-settings .pro-filters').length > 0 )
    {
        jQuery('.pro-feature-settings .pro-filters ul li.active a').trigger('click');
    }

    jQuery('.pro-feature-settings input[name=\'active_plugins[]\']').change(function()
    {
        var parent_el = jQuery(this);
        var is_checked = parent_el.is(':checked');

        var slug = parent_el.val();

        jQuery(this).parent().next('.loading').show();
        jQuery(this).parent().hide();

        if ( is_checked )
        {
            // need to install/activate plugin
            jQuery.ajax({
                url : ajaxurl,
                method: 'POST',
                data : {
                    action: "propertyhive_activate_pro_feature", 
                    slug : slug, 
                    _ajax_nonce: propertyhive_admin_settings.ajax_nonce
                },
                dataType : "json",
                success: function(response) 
                {
                    if ( response.success === true )
                    {
                        if ( typeof response.data !== 'undefined' && typeof response.data.activateUrl !== 'undefined' )
                        {
                            jQuery.ajax({
                                url : response.data.activateUrl,
                                method: 'GET',
                                data : {},
                                success: function(response) 
                                {
                                    window.location.href = propertyhive_admin_settings.features_settings_url + '&successmessage=1' + ( (jQuery('.pro-filters li.active a').data('filter') != '') ? '&profilter=' + jQuery('.pro-filters li.active a').data('filter') : '' );
                                }
                            });
                        }
                        else
                        {
                            window.location.href = propertyhive_admin_settings.features_settings_url + '&successmessage=1' + ( (jQuery('.pro-filters li.active a').data('filter') != '') ? '&profilter=' + jQuery('.pro-filters li.active a').data('filter') : '' );
                        }
                    }
                    else
                    {
                        parent_el.prop('checked', false);
                        parent_el.parent().next('.loading').hide();
                        parent_el.parent().show();
                        alert(response.data.errorMessage);
                    }
                }
            });
        }
        else
        {
            // need to deactivate plugin
            jQuery.ajax({
                url : ajaxurl,
                method: 'POST',
                data : {
                    action: "propertyhive_deactivate_pro_feature", 
                    slug : slug, 
                    _ajax_nonce: propertyhive_admin_settings.ajax_nonce
                },
                dataType : "json",
                success: function(response) 
                {
                    if ( response.success === true )
                    {
                        window.location.href = propertyhive_admin_settings.features_settings_url + '&successmessage=2' + ( (jQuery('.pro-filters li.active a').data('filter') != '') ? '&profilter=' + jQuery('.pro-filters li.active a').data('filter') : '' );
                    }
                    else
                    {
                        parent_el.prop('checked', 'checked');
                        parent_el.parent().next('.loading').hide();
                        parent_el.parent().show();
                        alert(response.data.errorMessage);
                    }
                }
            });
        }
    });

    if ( jQuery('[name=\'propertyhive_license_type\']').length > 0 )
    {
        ph_toggle_license_key_settings();

        jQuery('[name=\'propertyhive_license_type\']').change(function()
        {
            ph_toggle_license_key_settings();
        });
    }

    ph_resize_pro_features_list();
    ph_show_saved_toast();
    ph_init_development_tools();

    window.setTimeout(ph_init_save_tray, 120);
});

jQuery(window).on( "resize", function() 
{
    ph_resize_pro_features_list();
});

function ph_resize_pro_features_list()
{
    if ( jQuery('.pro-features').length > 0 )
    {
        jQuery('.pro-features ul li .inner').css('height', 'auto');

        var max_height = 0;
        jQuery('.pro-features ul li .inner').each(function()
        {
            if ( jQuery(this).parent().css('display') != 'none' && jQuery(this).height() > max_height )
            {
                max_height = jQuery(this).height();
            }
        });

        jQuery('.pro-features ul li .inner').css('height', max_height + 'px');
        jQuery('.pro-features ul li').css('visibility', 'visible');
    }
}

function ph_toggle_license_key_settings()
{
    if ( jQuery('[name=\'propertyhive_license_type\']:checked').val() == 'old' )
    {
        jQuery('#row_pro_license_key_info').hide();
        jQuery('#row_pro_license_key_display').hide();
        jQuery('#row_propertyhive_pro_license_key').hide();
        jQuery('#row_license_key_info').show();
        jQuery('#row_propertyhive_license_key').show();
        ph_set_settings_save_button_text(initial_save_changes_value);
    }
    else
    {
        jQuery('#row_pro_license_key_info').show();
        jQuery('#row_propertyhive_pro_license_key').show();
        jQuery('#row_pro_license_key_display').show();
        jQuery('#row_license_key_info').hide();
        jQuery('#row_propertyhive_license_key').hide();

        if ( propertyhive_admin_settings.valid_pro_license_key )
        {
            ph_set_settings_save_button_text('Deactivate key');
        }
        else
        {
            ph_set_settings_save_button_text('Activate key');
        }
    }
}

function ph_set_settings_save_button_text(button_text)
{
    jQuery('[data-ph-save-button-label]').text(button_text);
    jQuery('[data-ph-save-button]').val(button_text);
    jQuery('#mainform').trigger('ph-save-action-change');
}

function ph_toggle_maps_provider_options()
{
    jQuery('#row_propertyhive_google_maps_api_key').hide();
    jQuery('#row_propertyhive_mapbox_api_key').hide();

    if ( jQuery('[name=\'propertyhive_maps_provider\']:checked').val() == 'mapbox' )
    {
        jQuery('#row_propertyhive_mapbox_api_key').show();
    }
    if ( jQuery('[name=\'propertyhive_maps_provider\']:checked').val() == '' )
    {
        jQuery('#row_propertyhive_google_maps_api_key').show();
    }
}

function ph_toggle_geocoding_provider_options()
{
    jQuery('#row_propertyhive_google_maps_geocoding_api_key').hide();
    jQuery('#row_propertyhive_mapbox_geocoding_api_key').hide();
    jQuery('#row_propertyhive_osm_html').hide();

    if ( jQuery('[name=\'propertyhive_geocoding_provider\']:checked').val() == 'mapbox' )
    {
        jQuery('#row_propertyhive_mapbox_geocoding_api_key').show();
    }
    if ( jQuery('[name=\'propertyhive_geocoding_provider\']:checked').val() == 'osm' )
    {
        jQuery('#row_propertyhive_osm_html').show();
    }
    if ( jQuery('[name=\'propertyhive_geocoding_provider\']:checked').val() == '' )
    {
        jQuery('#row_propertyhive_google_maps_geocoding_api_key').show();
    }
}

function ph_toggle_auto_incremental_reference_number_options()
{
    if ( jQuery('[name=\'propertyhive_auto_incremental_reference_numbers\']').is(":checked") )
    {
        jQuery('#row_propertyhive_auto_incremental_next').show();
    }
    else
    {
        jQuery('#row_propertyhive_auto_incremental_next').hide();
    }
}

function fill_search_form_currency_options()
{
    var selected_countries = jQuery('select[name=\'propertyhive_countries[]\']').val();
    var selected_currency = jQuery('#propertyhive_search_form_currency').val();

    var new_currency_options = new Array();

    for ( var i in selected_countries)
    {
        var country = countries[selected_countries[i]];

        new_currency_options.push( country.currency_code );
    }

    jQuery('#propertyhive_search_form_currency').find('option').remove();
    if ( new_currency_options.length > 0 )
    {
        new_currency_options = jQuery.unique( new_currency_options );

        /*new_currency_options.sort(function(a, b) {
            var a1 = a.new_currency_options, b1 = b.new_currency_options;
            if(a1 == b1) return 0;
            return a1 > b1 ? 1 : -1;
        });*/

        for ( var i in new_currency_options)
        {
            jQuery('#propertyhive_search_form_currency').append('<option value="' + new_currency_options[i] + '">' + new_currency_options[i] + '</option>');
        }
        jQuery('#propertyhive_search_form_currency').val(selected_currency);

        if ( new_currency_options.length > 1 )
        {
            jQuery('#propertyhive_search_form_currency').parent().parent().show();
        }
        else
        {
            jQuery('#propertyhive_search_form_currency').parent().parent().hide();
        }

        if ( jQuery("#propertyhive_search_form_currency :selected").length == 0 )
        {
            jQuery("#propertyhive_search_form_currency").val( jQuery("#propertyhive_search_form_currency option:first").val() );

        }
    }
}

function toggle_department_specific_options()
{
    jQuery('#row_propertyhive_lettings_fees').hide();
    jQuery('#row_propertyhive_lettings_fees_commercial').hide();
    jQuery('#row_propertyhive_lettings_fees_display_search_results').hide();

    if (jQuery('#propertyhive_active_departments_lettings').prop('checked') == true)
    {
        jQuery('#row_propertyhive_lettings_fees').show();
        jQuery('#row_propertyhive_lettings_fees_display_search_results').show();
    }
    if (jQuery('#propertyhive_active_departments_commercial').prop('checked') == true)
    {
        jQuery('#row_propertyhive_lettings_fees_commercial').show();
        jQuery('#row_propertyhive_lettings_fees_display_search_results').show();
    }
}
