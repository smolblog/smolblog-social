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
	 * Fire off an asynchronous job identified by $name with $args
	 *
	 * @param string $name Name of job.
	 * @param array  $args Arguments to pass to $callback.
	 */
	public function enqueue_single_job( string $name, array $args = [] ) {
		as_enqueue_async_action( $name, $args, 'smolblog' );
	}

	/**
	 * Schedule a job to run regularly.
	 *
	 * @param integer $timestamp UNIX timestamp of the first run.
	 * @param integer $interval Time in seconds between jobs.
	 * @param string  $name Name of job.
	 * @param array   $args Arguments to pass to $callback. Default [].
	 * @param boolean $reschedule If the job exists, pass true to reschedule. Default false.
	 */
	public function schedule_recurring_job( int $timestamp, int $interval, string $name, array $args = [], bool $reschedule = false ) {
		$next = wp_next_scheduled( $name, $args );
		if ( false !== $next ) {
			if ( ! $reschedule ) {
				return;
			}

			wp_unschedule_event( $next, $name, $args );
		}

		wp_schedule_event( $timestamp, 'twicedaily', $name, $args );
	}

	/**
	 * Register a job to be scheduled later
	 *
	 * @param string   $name Name of job.
	 * @param callable $callback Function to call when job is executed.
	 * @param integer  $num_args Number of arguments accepted by the job.
	 */
	public function register_job( string $name, callable $callback, int $num_args ) {
		add_action( $name, $callback, 1, $num_args );
	}
}
