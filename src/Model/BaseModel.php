<?php
/**
 * Base class for all Models
 *
 * @package Smolblog\Social
 */

// PHPCS currently does not support `mixed` as a type hint. Added in PHP 8.
//phpcs:disable Squiz.Commenting.FunctionComment.InvalidTypeHint
//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
//phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
//phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
//phpcs:disable PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection

namespace Smolblog\Social\Model;

/**
 * Base class for all Models
 */
abstract class BaseModel {
	/**
	 * Primary key from the database representing this model.
	 *
	 * @var int
	 */
	protected int $db_id = 0;

	/**
	 * Data from the database representing this model.
	 *
	 * @var array
	 */
	protected array $data = [];

	/**
	 * Array of formats for $data.
	 *
	 * @see https://developer.wordpress.org/reference/classes/wpdb/#placeholders
	 * @var array
	 */
	protected array $data_formats = [];

	/**
	 * True if the values in $data do not match the database
	 *
	 * @var boolean
	 */
	protected bool $is_dirty = true;

	/**
	 * True if the underlying table has `created` and `modified` date fields.
	 *
	 * @var boolean
	 */
	protected bool $has_audit_fields = false;

	/**
	 * Returns true if this instance is out-of-sync with the database
	 * because either it has not been linked to a database ID or it has
	 * been modified since loading from the database.
	 *
	 * @return boolean true if instance should be saved
	 */
	public function needs_save() : bool {
		return ( $this->is_dirty ) || ( ! $this->db_id );
	}

	/**
	 * Provides the full table name including WP prefix.
	 *
	 * @return string full table name for this instance
	 */
	abstract protected function full_table_name() : string;

	/**
	 * Standard getter; gets attribute from $data if it exists
	 *
	 * @param string $name Property to get.
	 * @return mixed|null Value of $data[$name] or null
	 */
	public function __get( string $name ) : mixed {
		if ( ! isset( $this->data[ $name ] ) ) {
			$trace = debug_backtrace();
			trigger_error(
				'Undefined property ' . $name .
				' accessed in ' . $trace[0]['file'] .
				' on line ' . $trace[0]['line'],
				E_USER_NOTICE
			);
			return null;
		}

		return $this->data[ $name ];
	}

	/**
	 * Standard setter. Sets given attribute to $data and marks
	 * instance as dirty.
	 *
	 * @param string $name Property to set.
	 * @param mixed  $value Value to set.
	 */
	public function __set( string $name, mixed $value ) : void {
		if ( ! isset( $this->data[ $name ] ) ) {
			$trace = debug_backtrace();
			trigger_error(
				'Undefined property ' . $name .
				' set in ' . $trace[0]['file'] .
				' on line ' . $trace[0]['line'],
				E_USER_NOTICE
			);
			return;
		}

		$this->data[ $name ] = $value;
		$this->is_dirty      = true;
	}

	/**
	 * Loads data from the database into this instance.
	 */
	public function load() : void {
		global $wpdb;
		$tablename = $this->full_table_name();

		$db_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $tablename WHERE `id` = %d", //phpcs:ignore
				$this->db_id
			),
			ARRAY_A
		);

		if ( $db_data ) {
			unset( $db_data['id'] );
			$this->data     = $db_data;
			$this->is_dirty = false;
		}
	}

	/**
	 * Loads data from this instance into the database.
	 */
	public function save() : void {
		if ( $this->db_id ) {
			$this->update();
		} else {
			$this->create();
		}
	}

	/**
	 * Update an existing database entry with $data
	 */
	protected function update() : void {
		global $wpdb;

		if ( $this->has_audit_fields ) {
			array_merge( $this->data, [ 'modified' => gmdate( DATE_RFC3339 ) ] );

			$num_args = count( $this->data );
			for ( $k = count( $this->data_formats ); $k < $num_args; $k++ ) {
				$this->data_formats[] = '%s';
			}
		}

		$wpdb->update(
			$this->full_table_name(),
			$this->data,
			[ 'id' => $this->db_id ],
			$this->data_formats,
			[ '%d' ]
		);
	}

	/**
	 * Create a new database entry with $data
	 */
	protected function create() : void {
		global $wpdb;

		if ( $this->has_audit_fields ) {
			array_merge(
				$this->data,
				[
					'created'  => gmdate( DATE_RFC3339 ),
					'modified' => gmdate( DATE_RFC3339 ),
				]
			);

			$num_args = count( $this->data );
			for ( $k = count( $this->data_formats ); $k < $num_args; $k++ ) {
				$this->data_formats[] = '%s';
			}
		}

		$wpdb->insert(
			$this->full_table_name(),
			$this->data,
			$this->data_formats
		);

		$this->db_id = $wpdb->insert_id;
	}

	/**
	 * Get $wpdb->print_error() as a string
	 *
	 * @return string
	 */
	public static function get_wpdb_error() : string {
		global $wpdb;

		ob_start();
		$wpdb->print_error();
		$db_error = ob_get_clean();
		ob_end_clean();

		return $db_error;
	}
}
