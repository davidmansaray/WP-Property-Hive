<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Property match email open tracking.
 *
 * @class       PH_Email_Tracking
 * @package     PropertyHive/Classes
 * @category    Class
 * @author      PropertyHive
 */
class PH_Email_Tracking {

	/**
	 * A transparent one-pixel GIF.
	 *
	 * @var string
	 */
	const PIXEL_BASE64 = 'R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';

	/**
	 * Tracking schema revision. This is independent of the plugin release version.
	 */
	const SCHEMA_REVISION = 'initial';

	/**
	 * Per-site option recording the completed tracking schema revision.
	 */
	const SCHEMA_REVISION_OPTION = 'propertyhive_email_open_tracking_schema_revision';

	/**
	 * Whether the hooks have already been registered.
	 *
	 * @var bool
	 */
	private static $initialised = false;

	/**
	 * Cached schema-readiness result for this request.
	 *
	 * @var bool|null
	 */
	private static $schema_ready = null;

	/**
	 * Request-local cache of open summaries, keyed by email ID.
	 *
	 * @var array
	 */
	private static $open_summary_cache = array();

	/**
	 * Register the public tracking endpoint.
	 */
	public static function init() {
		if ( self::$initialised ) {
			return;
		}

		self::$initialised = true;
		add_action( 'init', array( __CLASS__, 'handle_pixel_request' ), 0 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_install_schema' ), 6 );
		add_action( 'propertyhive_process_email_log', array( __CLASS__, 'maybe_install_schema' ), 1 );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_show_schema_notice' ) );
		add_action( 'before_delete_post', array( __CLASS__, 'delete_contact_tracking_data' ), 10, 2 );
		add_action( 'switch_blog', array( __CLASS__, 'reset_blog_caches' ), 10, 3 );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_personal_data_eraser' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_personal_data_exporter' ) );
	}

	/**
	 * Clear table-specific request caches when switching sites in multisite.
	 *
	 * @param int    $new_blog_id  New site ID.
	 * @param int    $prev_blog_id Previous site ID.
	 * @param string $context      Switch or restore context.
	 */
	public static function reset_blog_caches( $new_blog_id = 0, $prev_blog_id = 0, $context = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		self::$schema_ready       = null;
		self::$open_summary_cache = array();
	}

	/**
	 * Create the tracking-only schema for existing installations.
	 *
	 * This deliberately has its own completion marker so the feature does not
	 * depend on changing the plugin's release or database version numbers.
	 */
	public static function maybe_install_schema() {
		global $wpdb;

		if ( ! self::current_context_can_install_schema() ) {
			return;
		}

		self::$schema_ready = null;

		if ( self::SCHEMA_REVISION === get_option( self::SCHEMA_REVISION_OPTION, '' ) ) {
			self::$schema_ready = true;
			return;
		}

		$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';
		$email_table = $wpdb->prefix . 'ph_email_log';
		$email_table_exists = $email_table === $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $email_table ) )
		);

		if ( ! $email_table_exists ) {
			delete_option( self::SCHEMA_REVISION_OPTION );

			return;
		}

		if ( ! self::email_log_tracking_column_exists() ) {
			$wpdb->query( "ALTER TABLE {$email_table} ADD COLUMN open_tracking_enabled tinyint(1) unsigned NOT NULL DEFAULT 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta(
			"CREATE TABLE {$wpdb->prefix}ph_email_open_summary (
				email_id bigint(20) unsigned NOT NULL,
				first_opened_at datetime NOT NULL,
				last_opened_at datetime NOT NULL,
				request_count bigint(20) unsigned NOT NULL DEFAULT 1,
				first_user_agent varchar(255) NOT NULL DEFAULT '',
				last_user_agent varchar(255) NOT NULL DEFAULT '',
				PRIMARY KEY  (email_id),
				KEY last_opened_at (last_opened_at)
			) {$collate};"
		);

		self::$schema_ready = null;

		if ( self::schema_objects_exist() ) {
			update_option( self::SCHEMA_REVISION_OPTION, self::SCHEMA_REVISION, false );
			self::$schema_ready = true;
		} else {
			delete_option( self::SCHEMA_REVISION_OPTION );
		}
	}

	/**
	 * Check whether the current request is allowed to run the schema install.
	 *
	 * Cron and WP-CLI requests have no logged-in user, so sites managed
	 * without wp-admin visits can still complete the tracking upgrade.
	 *
	 * @return bool
	 */
	private static function current_context_can_install_schema() {
		if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return true;
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Check whether the email-log table already has the tracking column.
	 *
	 * @return bool
	 */
	private static function email_log_tracking_column_exists() {
		global $wpdb;

		return null !== $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = %s',
				$wpdb->prefix . 'ph_email_log',
				'open_tracking_enabled'
			)
		);
	}

	/**
	 * Warn administrators when tracking is enabled but cannot record opens.
	 */
	public static function maybe_show_schema_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! self::is_enabled() || self::is_schema_ready() ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Property Hive: email open tracking is enabled, but the database changes it needs could not be applied, so opens are not being recorded. Deactivating and reactivating Property Hive may resolve this.', 'propertyhive' ) . '</p></div>';
	}

	/**
	 * Register erasure of the new open-tracking diagnostics.
	 *
	 * @param array $erasers Registered WordPress personal-data erasers.
	 * @return array
	 */
	public static function register_personal_data_eraser( $erasers ) {
		$erasers['propertyhive-email-open-tracking'] = array(
			'eraser_friendly_name' => __( 'Property Hive email open tracking', 'propertyhive' ),
			'callback'             => array( __CLASS__, 'erase_personal_data' ),
		);

		return $erasers;
	}

	/**
	 * Disable pixels and remove open diagnostics for an email address.
	 *
	 * @param string $email_address Email address being erased.
	 * @param int    $page          WordPress eraser page (all matching summaries are bounded and removed at once).
	 * @return array|WP_Error
	 */
	public static function erase_personal_data( $email_address, $page = 1 ) {
		global $wpdb;

		$email_address = strtolower( sanitize_email( $email_address ) );
		$page          = max( 1, absint( $page ) );

		if ( '' === $email_address ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		if ( ! self::is_schema_ready() ) {
			return new WP_Error(
				'propertyhive_email_open_tracking_schema_unavailable',
				__( 'Property Hive could not access the email open tracking data.', 'propertyhive' )
			);
		}

		$email_table   = $wpdb->prefix . 'ph_email_log';
		$summary_table = $wpdb->prefix . 'ph_email_open_summary';
		$limit         = 500;

		$email_ids = self::get_email_ids_for_address( $email_address, $limit, ( $page - 1 ) * $limit );

		if ( false === $email_ids ) {
			return new WP_Error(
				'propertyhive_email_open_tracking_lookup_failed',
				__( 'Property Hive could not find the email open tracking data to remove.', 'propertyhive' )
			);
		}

		if ( empty( $email_ids ) ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$id_placeholders = implode( ', ', array_fill( 0, count( $email_ids ), '%d' ) );

		$disabled = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$email_table} SET open_tracking_enabled = 0 WHERE email_id IN ({$id_placeholders}) AND open_tracking_enabled = 1",
				$email_ids
			)
		);

		if ( false === $disabled ) {
			return new WP_Error(
				'propertyhive_email_open_tracking_disable_failed',
				__( 'Property Hive could not disable all matching email tracking pixels.', 'propertyhive' )
			);
		}

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$summary_table} WHERE email_id IN ({$id_placeholders})",
				$email_ids
			)
		);

		if ( false === $deleted ) {
			return new WP_Error(
				'propertyhive_email_open_tracking_delete_failed',
				__( 'Property Hive could not remove all matching email open tracking diagnostics.', 'propertyhive' )
			);
		}

		self::$open_summary_cache = array();

		return array(
			'items_removed'  => $disabled > 0 || $deleted > 0,
			'items_retained' => true,
			'messages'       => array( __( 'Historical mailout records remain subject to the existing Property Hive email-log retention policy.', 'propertyhive' ) ),
			'done'           => count( $email_ids ) < $limit,
		);
	}

	/**
	 * Register export of the open-tracking diagnostics.
	 *
	 * @param array $exporters Registered WordPress personal-data exporters.
	 * @return array
	 */
	public static function register_personal_data_exporter( $exporters ) {
		$exporters['propertyhive-email-open-tracking'] = array(
			'exporter_friendly_name' => __( 'Property Hive email open tracking', 'propertyhive' ),
			'callback'               => array( __CLASS__, 'export_personal_data' ),
		);

		return $exporters;
	}

	/**
	 * Export the stored open diagnostics for an email address.
	 *
	 * @param string $email_address Email address being exported.
	 * @param int    $page          WordPress exporter page.
	 * @return array
	 */
	public static function export_personal_data( $email_address, $page = 1 ) {
		global $wpdb;

		$email_address = strtolower( sanitize_email( $email_address ) );
		$page          = max( 1, absint( $page ) );

		if ( '' === $email_address || ! self::is_schema_ready() ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$limit     = 500;
		$email_ids = self::get_email_ids_for_address( $email_address, $limit, ( $page - 1 ) * $limit );

		if ( false === $email_ids || empty( $email_ids ) ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$id_placeholders = implode( ', ', array_fill( 0, count( $email_ids ), '%d' ) );

		$summaries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT email_id, first_opened_at, last_opened_at, request_count, first_user_agent, last_user_agent FROM {$wpdb->prefix}ph_email_open_summary WHERE email_id IN ({$id_placeholders}) ORDER BY email_id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$email_ids
			),
			ARRAY_A
		);

		$export_items = array();

		if ( is_array( $summaries ) ) {
			foreach ( $summaries as $summary ) {
				$data = array(
					array(
						'name'  => __( 'First opened at (UTC)', 'propertyhive' ),
						'value' => $summary['first_opened_at'],
					),
					array(
						'name'  => __( 'Last opened at (UTC)', 'propertyhive' ),
						'value' => $summary['last_opened_at'],
					),
					array(
						'name'  => __( 'Open request count', 'propertyhive' ),
						'value' => (int) $summary['request_count'],
					),
				);

				if ( '' !== $summary['first_user_agent'] ) {
					$data[] = array(
						'name'  => __( 'First user agent', 'propertyhive' ),
						'value' => $summary['first_user_agent'],
					);
				}

				if ( '' !== $summary['last_user_agent'] ) {
					$data[] = array(
						'name'  => __( 'Last user agent', 'propertyhive' ),
						'value' => $summary['last_user_agent'],
					);
				}

				$export_items[] = array(
					'group_id'    => 'propertyhive-email-open-tracking',
					'group_label' => __( 'Email open tracking', 'propertyhive' ),
					'item_id'     => 'propertyhive-email-open-' . (int) $summary['email_id'],
					'data'        => $data,
				);
			}
		}

		return array(
			'data' => $export_items,
			'done' => count( $email_ids ) < $limit,
		);
	}

	/**
	 * Find email-log IDs addressed to an email address, in stable ID order.
	 *
	 * Recipient columns hold comma or semicolon separated lists, so the
	 * lookup normalises each list before matching.
	 *
	 * @param string $email_address Lower-cased email address.
	 * @param int    $limit         Maximum rows to return.
	 * @param int    $offset        Rows to skip.
	 * @return array|false Array of IDs, or false on query failure.
	 */
	private static function get_email_ids_for_address( $email_address, $limit, $offset ) {
		global $wpdb;

		$email_table = $wpdb->prefix . 'ph_email_log';

		$email_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT email_id FROM {$email_table} WHERE FIND_IN_SET(%s, REPLACE(REPLACE(LOWER(to_email_address), ' ', ''), ';', ',')) > 0 OR FIND_IN_SET(%s, REPLACE(REPLACE(LOWER(cc_email_address), ' ', ''), ';', ',')) > 0 OR FIND_IN_SET(%s, REPLACE(REPLACE(LOWER(bcc_email_address), ' ', ''), ';', ',')) > 0 ORDER BY email_id ASC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$email_address,
				$email_address,
				$email_address,
				$limit,
				$offset
			)
		);

		if ( '' !== $wpdb->last_error || ! is_array( $email_ids ) ) {
			return false;
		}

		$email_ids = array_map( 'absint', $email_ids );

		return array_values( array_filter( $email_ids ) );
	}

	/**
	 * Check whether email open tracking is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$enabled = 'yes' === get_option( 'propertyhive_track_email_opens', 'no' );

		return (bool) apply_filters( 'propertyhive_email_open_tracking_enabled', $enabled );
	}

	/**
	 * Return the validated tracking-data retention period.
	 *
	 * @return int
	 */
	public static function get_retention_days() {
		$retention_days = absint( apply_filters( 'propertyhive_email_open_tracking_retention_days', 365 ) );

		return $retention_days > 0 ? $retention_days : 365;
	}

	/**
	 * Check whether an email with no summary is older than the retention window.
	 *
	 * The email queue stores send times in UTC because WordPress sets PHP's
	 * default timezone to UTC.
	 *
	 * @param string $sent_at Email log send time.
	 * @return bool
	 */
	public static function is_summary_retention_expired( $sent_at ) {
		if ( ! is_string( $sent_at ) || ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $sent_at ) ) {
			return false;
		}

		$sent_date = date_create_from_format( '!Y-m-d H:i:s', $sent_at, new DateTimeZone( 'UTC' ) );

		if ( false === $sent_date || $sent_date->format( 'Y-m-d H:i:s' ) !== $sent_at ) {
			return false;
		}

		$retention_seconds = self::get_retention_days() * DAY_IN_SECONDS;

		return $sent_date->getTimestamp() <= time() - $retention_seconds;
	}

	/**
	 * Check that the database upgrade needed by tracking has completed.
	 *
	 * The completion marker is only written after a successful structural
	 * verification, so it is trusted here without re-querying
	 * INFORMATION_SCHEMA on every request. The structure is re-verified
	 * periodically by maybe_install_schema().
	 *
	 * @return bool
	 */
	public static function is_schema_ready() {
		if ( null !== self::$schema_ready ) {
			return self::$schema_ready;
		}

		self::$schema_ready = self::SCHEMA_REVISION === get_option( self::SCHEMA_REVISION_OPTION, '' );

		return self::$schema_ready;
	}

	/**
	 * Verify the tracking table and email-log flag exist after installation.
	 *
	 * dbDelta output is trusted beyond existence, as it is for the plugin's
	 * other tables; installation failure is surfaced by the admin notice.
	 *
	 * @return bool
	 */
	private static function schema_objects_exist() {
		global $wpdb;

		$summary_table = $wpdb->prefix . 'ph_email_open_summary';

		$summary_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $summary_table )
			)
		);

		return $summary_table === $summary_exists && self::email_log_tracking_column_exists();
	}

	/**
	 * Create a URL-safe, signed token for an email log row.
	 *
	 * @param int $email_id Email log ID.
	 * @return string
	 */
	public static function get_token( $email_id ) {
		$email_id = absint( $email_id );

		if ( 0 === $email_id ) {
			return '';
		}

		$signature = hash_hmac( 'sha256', 'email_open|' . get_current_blog_id() . '|' . $email_id, wp_salt( 'auth' ) );
		$payload   = $email_id . '|' . $signature;

		return rtrim( strtr( base64_encode( $payload ), '+/', '-_' ), '=' );
	}

	/**
	 * Add one tracking pixel to an HTML message.
	 *
	 * @param string $content  Email content.
	 * @param int    $email_id Email log ID.
	 * @return string
	 */
	public static function inject_tracking_pixel( $content, $email_id ) {
		$content  = (string) $content;
		$email_id = absint( $email_id );

		if ( ! self::is_enabled() || 0 === $email_id || false !== stripos( $content, 'data-propertyhive-email-open=' ) ) {
			return $content;
		}

		$token = self::get_token( $email_id );

		if ( '' === $token ) {
			return $content;
		}

		$url = add_query_arg( 'ph_email_open', $token, site_url( '/' ) );
		$pixel = '<img data-propertyhive-email-open="1" src="' . esc_url( $url ) . '" width="1" height="1" alt="" style="display:block;width:1px;height:1px;border:0;margin:0;padding:0;" />';

		$body_position = strripos( $content, '</body>' );

		if ( false === $body_position ) {
			return $content . $pixel;
		}

		return substr( $content, 0, $body_position ) . $pixel . substr( $content, $body_position );
	}

	/**
	 * Handle a public tracking-pixel request.
	 */
	public static function handle_pixel_request() {
		if ( ! isset( $_GET['ph_email_open'] ) ) {
			return;
		}

		self::send_pixel_headers();

		if ( ! self::is_enabled() || ! self::is_schema_ready() || ! is_scalar( $_GET['ph_email_open'] ) ) {
			self::serve_pixel();
		}

		$token    = sanitize_text_field( wp_unslash( $_GET['ph_email_open'] ) );
		$email_id = self::decode_token( $token );

		if ( false === $email_id ) {
			self::serve_pixel();
		}

		global $wpdb;

		$email_log = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT email_id, send_at FROM {$wpdb->prefix}ph_email_log WHERE email_id = %d AND open_tracking_enabled = 1 LIMIT 1",
				$email_id
			)
		);

		if (
			! is_object( $email_log )
			|| (int) $email_log->email_id !== $email_id
			|| self::is_summary_retention_expired( $email_log->send_at )
		) {
			self::serve_pixel();
		}

		$user_agent = '';
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && is_scalar( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$user_agent = substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 );
		}

		self::record_request( $email_id, $user_agent );
		self::serve_pixel();
	}

	/**
	 * Return the open summary for an email.
	 *
	 * @param int $email_id Email log ID.
	 * @return array|null
	 */
	public static function get_open_summary( $email_id ) {
		global $wpdb;

		$email_id = absint( $email_id );

		if ( 0 === $email_id || ! self::is_schema_ready() ) {
			return null;
		}

		if ( array_key_exists( $email_id, self::$open_summary_cache ) ) {
			return self::$open_summary_cache[ $email_id ];
		}

		$summary = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT email_id, first_opened_at, last_opened_at, request_count, first_user_agent, last_user_agent FROM {$wpdb->prefix}ph_email_open_summary WHERE email_id = %d",
				$email_id
			),
			ARRAY_A
		);

		if ( ! is_array( $summary ) ) {
			self::$open_summary_cache[ $email_id ] = null;

			return null;
		}

		$summary['email_id']      = (int) $summary['email_id'];
		$summary['request_count'] = (int) $summary['request_count'];

		self::$open_summary_cache[ $email_id ] = $summary;

		return $summary;
	}

	/**
	 * Delete expired and orphaned tracking summaries.
	 */
	public static function cleanup() {
		global $wpdb;

		if ( ! self::is_schema_ready() ) {
			return;
		}

		$retention_days = self::get_retention_days();

		$summary_table = $wpdb->prefix . 'ph_email_open_summary';
		$email_table   = $wpdb->prefix . 'ph_email_log';

		// Expired mailouts must not be able to recreate deleted tracking data.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$email_table} SET open_tracking_enabled = 0 WHERE open_tracking_enabled = 1 AND send_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)",
				$retention_days
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$summary_table} WHERE last_opened_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)",
				$retention_days
			)
		);

		$wpdb->query(
			"DELETE summary FROM {$summary_table} summary LEFT JOIN {$email_table} email_log ON summary.email_id = email_log.email_id WHERE email_log.email_id IS NULL"
		);

		self::$open_summary_cache = array();
	}

	/**
	 * Remove tracking diagnostics when a contact is permanently deleted.
	 *
	 * The historical email log is deliberately retained by its existing policy.
	 *
	 * @param int          $post_id Post ID.
	 * @param WP_Post|null $post    Post being deleted.
	 */
	public static function delete_contact_tracking_data( $post_id, $post = null ) {
		global $wpdb;

		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $post_id );
		}

		if ( ! $post instanceof WP_Post || 'contact' !== $post->post_type || ! self::is_schema_ready() ) {
			return;
		}

		$summary_table = $wpdb->prefix . 'ph_email_open_summary';
		$email_table   = $wpdb->prefix . 'ph_email_log';

		// Disable the old pixel before removing its diagnostics so a later
		// request cannot recreate data for a deleted contact.
		$wpdb->update(
			$email_table,
			array( 'open_tracking_enabled' => 0 ),
			array( 'contact_id' => $post->ID ),
			array( '%d' ),
			array( '%d' )
		);

		$wpdb->query(
			$wpdb->prepare(
				"DELETE summary FROM {$summary_table} summary INNER JOIN {$email_table} email_log ON summary.email_id = email_log.email_id WHERE email_log.contact_id = %d",
				$post->ID
			)
		);

		self::$open_summary_cache = array();
	}

	/**
	 * Decode and verify a tracking token.
	 *
	 * @param string $token Encoded token.
	 * @return int|false
	 */
	private static function decode_token( $token ) {
		if ( ! is_string( $token ) || '' === $token || strlen( $token ) > 512 || ! preg_match( '/^[A-Za-z0-9_-]+$/', $token ) ) {
			return false;
		}

		$padding = strlen( $token ) % 4;
		if ( 0 !== $padding ) {
			$token .= str_repeat( '=', 4 - $padding );
		}

		$decoded = base64_decode( strtr( $token, '-_', '+/' ), true );

		if ( false === $decoded ) {
			return false;
		}

		$parts = explode( '|', $decoded );

		if ( 2 !== count( $parts ) || ! preg_match( '/^[1-9]\d*$/', $parts[0] ) || ! preg_match( '/^[a-f0-9]{64}$/', $parts[1] ) ) {
			return false;
		}

		$email_id = absint( $parts[0] );

		$expected_signature = hash_hmac( 'sha256', 'email_open|' . get_current_blog_id() . '|' . $email_id, wp_salt( 'auth' ) );

		if ( ! hash_equals( $expected_signature, $parts[1] ) ) {
			return false;
		}

		return $email_id;
	}

	/**
	 * Atomically create or update the bounded summary for an email.
	 *
	 * @param int    $email_id    Email log ID.
	 * @param string $user_agent User agent.
	 */
	private static function record_request( $email_id, $user_agent ) {
		global $wpdb;

		$opened_at      = current_time( 'mysql', true );
		$table_name     = $wpdb->prefix . 'ph_email_open_summary';
		$email_table    = $wpdb->prefix . 'ph_email_log';
		$retention_days = self::get_retention_days();

		$inserted = $wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$table_name} (email_id, first_opened_at, last_opened_at, request_count, first_user_agent, last_user_agent) SELECT email_id, %s, %s, 1, %s, %s FROM {$email_table} WHERE email_id = %d AND open_tracking_enabled = 1 AND send_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)",
				$opened_at,
				$opened_at,
				$user_agent,
				$user_agent,
				$email_id,
				$retention_days
			)
		);

		if ( 0 === $inserted ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table_name} open_summary INNER JOIN {$email_table} email_log ON open_summary.email_id = email_log.email_id SET open_summary.last_opened_at = %s, open_summary.last_user_agent = %s, open_summary.request_count = open_summary.request_count + 1 WHERE open_summary.email_id = %d AND email_log.open_tracking_enabled = 1 AND email_log.send_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)",
					$opened_at,
					$user_agent,
					$email_id,
					$retention_days
				)
			);
		}

		unset( self::$open_summary_cache[ $email_id ] );
	}

	/**
	 * Send the tracking pixel response headers.
	 */
	private static function send_pixel_headers() {
		$pixel = base64_decode( self::PIXEL_BASE64, true );

		if ( headers_sent() ) {
			return;
		}

		nocache_headers();
		header( 'Content-Type: image/gif' );
		header( 'Content-Length: ' . strlen( $pixel ) );
		header( 'X-Robots-Tag: noindex' );
	}

	/**
	 * Output the fixed tracking pixel and stop request processing.
	 */
	private static function serve_pixel() {
		echo base64_decode( self::PIXEL_BASE64, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}
