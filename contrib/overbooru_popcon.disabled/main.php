<?php
/**
* Name: Overbooru Popularity Contest (under construction)
* Author: Zach Hall <zach@sosguy.net>
* Link: http://seemslegit.com
* License: GPLv2
* Description: Excuse the Debian-esque name, this program tracks clicks on the Overbooru menu. Can display sites in a topsite style.
* 			   Boorudex component; designed for the Overbooru.
*/

class Popcon extends SimpleExtension {
	public function onInitExt($event) {
		global $config;
		$version = $config->get_int("popcon_version", 0);
		if($version < 1) {
			global $database, $config;
			$database->create_table("popcon", "
					id SCORE_AIPK,
					booru_id INTEGER NOT NULL,
					views INTEGER NOT NULL,
					date DATE
					");
			// speedup!
			$database->execute("ALTER TABLE  `popcon` ADD INDEX (  `booru_id` )");
			$database->execute("ALTER TABLE  `popcon` ADD INDEX (  `date` )");
			log_info("overbooru_popcon", "Installed tables for popularity contest extension.");
			$config->set_int("popcon_version", 1);
		}
		// Set default config:
		$config->set_default_int("popcon_min_views", 50); // Minimum views per day required to be considered "popular"
		$config->set_default_bool("popcon_track_admin_views", false); // Track admin views?
		$config->set_default_bool("popcon_track_anon_views", true); // Track anonymous views?
	}

	public function onPageRequest($event) {
		global $database, $page;
		if($event->page_matches("go")) {
			// The "go" page records a view, and forwards the user to their designated spot.
			if(!is_null($event->get_arg(0))) {
				$site = int_escape($event->get_arg(0));
				$site_info = $database->get_row("SELECT `booru_url` FROM boorus WHERE id=?", $site);
				if(!isset($site_info)) break;
				$site_url = $site_info['booru_url'];
				$siteup = $this->up_it($site);
				if(!$siteup == true) { echo "ERROR! Site failed to update. Check ID and try again.<br />"; break; }
				$page->set_mode("redirect");
				$page->set_redirect($site_url);
			}
			echo "ERROR! Invalid or no site ID. If you typed the ID by hand, try again. If not, it may be bug report time... <a href='mailto:zach@sosguy.net'>Contact the developer.</a>";
		}
		
		if($event->page_matches("popular_by_day")) {
			global $config, $page, $database;
			$views = $config->get_int("popcon_min_views",50);
			$match_date = html_escape($event->get_arg(1));
			$nice_date = strtotime($match_date);
			$date_txt = date("F d, Y", $nice_date);
			$next_day = date("Y-m-d", strtotime('+1 day', $nice_date));
			$prev_day = date("Y-m-d", strtotime('-1 day', $nice_date));
			
			$sites = $database->get_all("SELECT boorus.id, boorus.booru_name, boorus.booru_url, popcon.views IN (SELECT booru_id FROM popcon WHERE popcon.date = ? AND popcon.views >= $views ORDER BY popcon.views DESC)", array($match_date));
			if(!isset($GLOBALS['once_already'])) {
				$this->theme->display_datinator($date_txt, $prev_day, $next_day);
				$GLOBALS['once_already'] = true;
			}
			$this->theme->display_popular_by_day($sites);
		}
	}
	
	public function up_it($booru_id) {
		// This function increases pageview stats by 1 each time it's called!
		/**
		 * Process here: select a row from the database that matches today's date.
		 * 		FOUND? Get the number of views, increase it by one, update table with new value.
		 *		NOT FOUND? Insert a row for the site id, views = 1.
		 * Finished.
		 */
		global $config, $database, $user;
		$booru_id = int_escape($booru_id);
		$track_admin = $config->get_bool("img_stats_track_admin_views");	// Track admin views?
		$track_anon = $config->get_bool("img_stats_track_anon_views");		// Track anonymous views?
		$current_date = date("Y-m-d");
		
		if($user->is_admin() && $track_admin == false) { $track = false; }
		if($user->is_anonymous() && $track_anon == false) { $track = false; }

		$row = $database->get_row("SELECT * FROM popcon WHERE date = CURRENT_DATE() AND booru_id = ?", array($booru_id));
		if(isset($row)) {
			$id = $row['id'];
			$views = $row['views'];
		} else {
			$id = 0;
			$views = 0;
		}
		
		if(!isset($track)) {
			if(isset($row)) {
				$views++;
				$database->execute("UPDATE popcon SET views = ? WHERE id = ? LIMIT 1", array($views, $id));
			}
			else {
				$database->execute("INSERT INTO image_stats (id , booru_id, views, date) VALUES (?, ?, ?, CURRENT_DATE())", 
					array(NULL, $id, 1));
				$views = 1;
			}
		}
		return true;
	}
}
?>