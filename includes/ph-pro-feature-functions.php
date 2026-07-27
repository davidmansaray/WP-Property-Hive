<?php

function get_ph_pro_features( $force = false )
{
    $features = get_transient( 'propertyhive_features' );
    $force    = true === $force;

    if ( false === $features || $force )
    {
        $features = array();

        $response = wp_remote_get(
            'https://wp-property-hive.com/add-ons-json.php',
            array(
                'timeout' => 10
            )
        );

        if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) )
        {
            $add_ons = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( JSON_ERROR_NONE === json_last_error() && is_array( $add_ons ) )
            {
                foreach ( $add_ons as $add_on )
                {
                    if (
                        ! is_array( $add_on ) ||
                        ! isset( $add_on['wordpress_plugin_file'], $add_on['name'] ) ||
                        ! is_string( $add_on['wordpress_plugin_file'] ) ||
                        '' === trim( $add_on['wordpress_plugin_file'] ) ||
                        ! is_string( $add_on['name'] ) ||
                        '' === trim( $add_on['name'] ) ||
                        ( isset( $add_on['plans'] ) && ! is_array( $add_on['plans'] ) )
                    )
                    {
                        continue;
                    }

                    $features[] = $add_on;
                }
            }
        }

        // Cache failures and invalid catalogues briefly so an outage cannot make
        // every permission check wait for the same remote request.
        set_transient(
            'propertyhive_features',
            $features,
            empty( $features ) ? 5 * MINUTE_IN_SECONDS : DAY_IN_SECONDS
        );
    }

    $features = apply_filters( 'propertyhive_pro_features', $features );

    return is_array( $features ) ? $features : array();
}

function get_ph_pro_feature( $requested_feature )
{
	$features = get_ph_pro_features();

	foreach ( $features as $feature )
	{
        if (
            ! is_array( $feature ) ||
            ! isset( $feature['wordpress_plugin_file'] ) ||
            ! is_string( $feature['wordpress_plugin_file'] )
        )
        {
            continue;
        }

        $slug = explode("/", $feature['wordpress_plugin_file']);
        $slug = $slug[0];

		if ( $slug == $requested_feature )
		{
			return $feature;
		}
	}

	return false;
}
