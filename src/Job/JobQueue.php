<?php
/**
 * Handler for scheduling jobs
 *
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\Job;

/**
 * Handler for scheduling jobs
 */
class JobQueue {
	/**
	 * Fire off an asynchronous job that calls $callback with $args
	 *
	 * @param callable $callback Function to execute.
	 * @param array    $args Arguments to pass to $callback.
	 * @param string   $name Optional name for the action to display in admin.
	 */
	public function enqueue_single_job( callable $callback, array $args = [], string $name = null ) {
		$action_name = ( $name ?? 'smolblog_job_queue' ) . '_' . time();
		add_action( $action_name, $callback, 1, count( $args ) );
		as_enqueue_async_action( $action_name, $args, 'smolblog' );
	}
}
