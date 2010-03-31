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
					array(NULL, "http://localhost/favicon.ico", "http://localhost/", 2 , "Y"));
			log_info("overbooru_iotd", "Installed tables for the Overbooru IOTD.");
			$config->set_int("iotd_version", 1);
		}
		$config->set_default_string("iotd_news", "Insert some update here.");
	}
	
	public function onPageRequest($event) {
		global $page, $database, $config, $user;
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
															AND image_url != ''
															ORDER BY timestamp DESC
															LIMIT ? OFFSET ?",
															array("Y", 1, $start));
					
					$total_pages = ceil(($database->db->GetOne("SELECT COUNT(*) FROM iotd WHERE approved = ? AND image_url != ''", "Y")));
					$extension["title"] = $title = $config->get_string('iotd_title');
					$extension["permalinks"] = true;
					$this->theme->show_index($image_to_display, $current_page, $total_pages, $extension);
					break;
				case "submit":
					if(!$user->is_anonymous()) {
						$thanks = $database->get_all("SELECT approved
													  FROM iotd
													  WHERE approved = ?
													  AND owner_id = ?
													  LIMIT 3", array("N", $user->id));
						
						$thanks2 = $database->get_all("SELECT approved
													  FROM iotd
													  WHERE approved = ?
													  AND owner_id = ?
													  LIMIT 3", array("Y", $user->id));
						
						if(count($thanks)>=3 && count($thanks2)<3) { $thanks = true; } else { $thanks = false; }
						$this->theme->show_submit($thanks);
					} else {
						$page->set_mode("redirect");
						$page->set_redirect(make_link("user_admin/login"));
					}
					break;
				case "manage":
					if($user->is_admin()) {
						$images_u = $database->get_all("SELECT *
														FROM iotd
														WHERE approved = ?
														ORDER BY id DESC", array("N"));
						$images_a = $database->get_all("SELECT *
														FROM iotd
														WHERE approved = ?
														ORDER BY id DESC", array("Y"));
						$this->theme->show_manage($images_u, $images_a);
					} else { $this->theme->display_permission_denied($page); }
					break;
				case "approve":
					if($user->is_admin()) {
                            $id = int_escape($_POST['id']);
                            if(!isset($_POST['id'])) { die("No ID!"); }
                            $database->Execute("UPDATE `iotd` SET `approved` = ? WHERE `iotd`.`id` = ? LIMIT 1;", array("Y", $id));
                            log_info("overbooru_menu", "Approved booru #$id");
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("iotd/manage"));
					} else { $this->theme->display_permission_denied($page); }
					break;
				case "disapprove":
					if($user->is_admin()) {
                            $id = int_escape($_POST['id']);
                            if(!isset($_POST['id'])) { die("No ID!"); }
                            $database->Execute("UPDATE `iotd` SET `approved` = ? WHERE `iotd`.`id` = ? LIMIT 1;", array("N", $id));
                            log_info("overbooru_menu", "Approved booru #$id");
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("iotd/manage"));
					} else { $this->theme->display_permission_denied($page); }
					break;
				case "change":
					if($user->is_admin()) {
                            $id = int_escape($_POST['id']);
							$image_url = html_escape($_POST['image_url']);
                            if(!isset($_POST['id'])) { die("No ID!"); }
                            $database->Execute("UPDATE `iotd` SET `image_url` = ? WHERE `id` = ? LIMIT 1;", array($image_url, $id));
                            log_info("overbooru_iotd", "Changed image #$id");
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("iotd/manage"));
					} else { $this->theme->display_permission_denied($page); }
					break;
				case "add":
					if(!$user->is_anonymous()) {
                            
							$image_source = html_escape($_POST['image_source']);
							$image_url = html_escape($_POST['image_url']);
							
							if(!isset($_POST['approved'])) {$approved = "N";} else {$approved = "Y";}
                            
							$database->execute("INSERT INTO iotd (id, image_url, image_source, owner_id, timestamp, approved) VALUES (?, ?, ?, ?, now(), ?)", 
								array(NULL, $image_url, $image_source, $user->id , $approved));

                            log_info("overbooru_iotd", "Added new image");
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("iotd/submit"));
					} else { $this->theme->display_permission_denied($page); }
					break;
				case "remove":
					if($user->is_admin()) {
						$id = int_escape($_POST['id']);
						if(!isset($id)) { die("No ID!"); }
						$database->Execute("DELETE FROM iotd WHERE id=?", array($id));
						log_info("overbooru_iotd", "Removed image #$id");
						$page->set_mode("redirect");
						$page->set_redirect(make_link("iotd/manage"));
					} else { $this->theme->display_permission_denied($page); }
					break;
			}
		}
	}
	
    public function onUserBlockBuilding($event) {
        global $user;
		$event->add_link("Submit IOTD", make_link("iotd/submit"));
        if($user->is_admin()) {
                $event->add_link("Manage IOTD", make_link("iotd/manage"));
        }
    }
}