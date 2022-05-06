<?php
/**
 * Handler for a periodic refresh of blogs
 *
 * @package Smolblog\Social
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 */

namespace Smolblog\Social\Import;
use Smolblog\Social\Utilities;
use Smolblog\Social\Model\SocialAccount;
use Smolblog\Social\Model\AccountBlogLink;
use Smolblog\Social\Job\JobQueue;

class TimedRefresh {
	public function run() : void {
		$links = Utilities::get_social_links_for_import();
		$queue = new JobQueue();

		foreach ( $links as $link ) {
			$account = new SocialAccount( $link->social_id );
			if ( $account->needs_save() ) {
				continue;
			}

			switch_to_blog( $link->blog_id );

			$params = [ $link->social_id ];
			if ( $link->additional_info ) {
				$params[] = $link->additional_info;
			}

			$queue->enqueue_single_job(
				'smolblog_import_' . $account->type,
				$params
			);

			restore_current_blog();
		}
	}
}
