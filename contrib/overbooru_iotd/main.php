<?php
/*
* Name: Overbooru IOTD
* Author: Zach Hall <zach@sosguy.net> [http://seemslegit.com]
* License: GPLv2
* Description: Displays a space for an image of the day, includes a place to queue up images, and an RSS feed.
*/

class OverbooruIOTD extends SimpleExtension {
	public function onInitExt($event) {
		/* Now... how should I go about doing this? Let's start with a plan...
		DATABASE STUFF:
		We'll have a table of images... id, image_url, owner_id, image_source, timestamp.
		*/
		/**
		 * I love re-using this installer don't I...
		 */
		global $config;
		$version = $config->get_int("iotd_version", 0);
		/**
		 * If this version is less than "1", it's time to install.
		 *
		 * REMINDER: If I change the database tables, I must change up version by 1.
		 */
		if($version < 1) {
			/**
			 * Installer
			 */
			global $database, $config, $user;
			$database->create_table("iotd", "
					id SCORE_AIPK,
					image_url TEXT NOT NULL,
					image_source TEXT NOT NULL,
					owner_id INTEGER NOT NULL,
					timestamp SCORE_DATETIME DEFAULT SCORE_NOW,
					approved SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N
					");
			// Insert sample data:
			$database->execute("INSERT INTO iotd (id, image_url, image_source, owner_id, timestamp, approved) VALUES (?, ?, ?, ?, now(), ?)", 
					array(NULL, "http://localhost/favicon.ico", "http://localhost/", $user->id , "Y"));
			log_info("overbooru_iotd", "Installed tables for the Overbooru IOTD.");
			$config->set_int("iotd_version", 1);
		}
		$config->set_default_string("iotd_news", "Insert some update here.");
	}
	
	public function onPageRequest($event) {
		global $page;
		if($event->page_matches("iotd")) {
			switch($event->get_arg(0)) {
				case "index":
					/**
					 * Pagination always helps.
					 */
					if(is_null($event->get_arg(1))||$event->get_arg(1)<=0) {
						$current_page = 1;
					} else {
						$current_page = $event->get_arg(1);
					}
					
					$start = $current_page - 1;
					
					$image_to_display = $database->get_row("SELECT *
															FROM iotd
															WHERE approved = ?
															ORDER BY id DESC
															LIMIT ? OFFSET ?",
															array("Y", 1, $start));
					
					$total_pages = ceil(($database->db->GetOne("SELECT COUNT(*) FROM iotd")));
					$extension["title"] = $title = $config->get_string('iotd_title');
					$extension["permalinks"] = true;
					$this->theme->show_index($image_to_display, $current_page, $total_pages, $extension);
					break;
				case "submit":
					$this->theme->show_submit();
					break;
				case "moderate":
					$this->theme->show_moderate();
					break;
				case default:
					$page->set_mode("redirect");
					$page->set_redirect(make_link());
					break;
			}
		}
	}
}