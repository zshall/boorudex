<?php
/*
* Name: Overbooru Categories Menu
* Author: Zach Hall <zach@sosguy.net> [http://seemslegit.com]
* License: GPLv2
* Description: Designed for the Overbooru: category and item system.
*/

class OverbooruCats extends SimpleExtension {
	public function onInitExt($event) {
		/* Now... how should I go about doing this? Let's start with a plan...
		DATABASE STUFF:
		We'll have a table of images... id, image_url, owner_id, image_source, timestamp.
		*/
		/**
		 * I love re-using this installer don't I...
		 */
		global $config;
		$version = $config->get_int("cat_version", 0);
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
			$database->create_table("categories", "
					id SCORE_AIPK,
					cat_name TEXT NOT NULL,
					cat_parent_id INTEGER,
					visible SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
					weight INTEGER NOT NULL
					");
			$database->create_table("boorus", "
					id SCORE_AIPK,
					booru_name TEXT NOT NULL,
					booru_url TEXT NOT NULL,
					booru_img TEXT,
					owner_id INTEGER NOT NULL,
					cat_id INTEGER NOT NULL,
					timestamp SCORE_DATETIME DEFAULT SCORE_NOW,
					approved SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N
					");
			// Insert sample data:
			$database->execute("INSERT INTO categories (id, cat_name, cat_parent_id, visible, weight) VALUES (?, ?, ?, ?, ?)", 
					array(NULL, "cat1", NULL, "Y", 50));
			$database->execute("INSERT INTO categories (id, cat_name, cat_parent_id, visible, weight) VALUES (?, ?, ?, ?, ?)", 
					array(NULL, "cat2", NULL, "Y", 50));
			$database->execute("INSERT INTO categories (id, cat_name, cat_parent_id, visible, weight) VALUES (?, ?, ?, ?, ?)", 
					array(NULL, "cat3", NULL, "N", 50));
			$database->execute("INSERT INTO categories (id, cat_name, cat_parent_id, visible, weight) VALUES (?, ?, ?, ?, ?)", 
					array(NULL, "cat4", 1, "Y", 50));
			$database->execute("INSERT INTO boorus (id, booru_name, booru_url, booru_img, owner_id, cat_id, timestamp, approved) VALUES (?, ?, ?, ?, ?, ?, now(), ?)", 
					array(NULL, "motivators", "http://seemslegit.com/", "http://seemslegit.com/favicon.ico", 2 , 1, "Y"));
			$database->execute("INSERT INTO boorus (id, booru_name, booru_url, booru_img, owner_id, cat_id, timestamp, approved) VALUES (?, ?, ?, ?, ?, ?, now(), ?)", 
					array(NULL, "danbooru", "http://danbooru.donmai.us/", "http://danbooru.donmai.us/favicon.ico", 2 , 1, "N"));
			log_info("overbooru_iotd", "Installed tables for the Overbooru Category Menu.");
			$config->set_int("cat_version", 1);
			$config->set_default_string("iotd_news", "Insert some update here.");
		}
		if($version < 2) {
			global $database, $config, $user;
        	$database->execute("ALTER TABLE boorus ADD COLUMN weight INTEGER NOT NULL", array());
			$config->set_int("cat_version", 2);
			$database->execute("UPDATE `boorus` SET `weight` = ?;", array(50));
		}
	}

	public function onPageRequest($event) {
		global $page, $database, $config, $user;
		if($event->page_matches("menu")) {
			switch($event->get_arg(0)) {
				case "index":
					
					$main_cats = $database->get_all("SELECT *
												FROM categories
												WHERE cat_parent_id = 0
												ORDER BY weight ASC");
												
					$sub_cats = $database->get_all("SELECT *
												FROM categories
												WHERE cat_parent_id IS NOT NULL
												ORDER BY weight ASC");
					
					$boorus = $database->get_all("SELECT *
												 FROM boorus
												 WHERE approved = ?
												 ORDER BY weight ASC, booru_name ASC",
												 array("Y"));

					$this->theme->show_index($main_cats, $sub_cats, $boorus);
					break;
				case "disclaimer":
					$this->theme->show_disclaimer();
					break;
				case "submit":
					if(!$user->is_anonymous()) {
						$main_cats = $database->get_all("SELECT *
													FROM categories
													WHERE cat_parent_id = 0
													ORDER BY weight ASC");
													
						$sub_cats = $database->get_all("SELECT *
													FROM categories
													WHERE cat_parent_id IS NOT NULL
													ORDER BY weight ASC");
						
						$thanks = $database->get_all("SELECT approved
													  FROM boorus
													  WHERE approved = ?
													  AND owner_id = ?
													  LIMIT 3", array("N", $user->id));
						
						if(count($thanks)>=3) { $thanks = true; } else { $thanks = false; }
						$this->theme->show_submit($thanks, $main_cats, $sub_cats);
					} else {
						$page->set_mode("redirect");
						$page->set_redirect(make_link("user_admin/login"));
					}
					break;
				case "manage":
					if($user->is_admin()) {
						if($event->get_arg(1) == "") { $mode = "boorus"; } 
						else { $mode = html_escape($event->get_arg(1)); }
						if($mode != "cats" && $mode != "boorus") { $mode = "boorus"; }

						$main_cats = $database->get_all("SELECT *
													FROM categories
													WHERE cat_parent_id = 0
													ORDER BY weight ASC");
													
						$sub_cats = $database->get_all("SELECT *
													FROM categories
													WHERE cat_parent_id IS NOT NULL
													ORDER BY weight ASC");
						
						$boorus_u = $database->get_all("SELECT *
														FROM boorus
														WHERE approved = ?
														ORDER BY booru_name ASC", array("N"));
						
						$boorus_a = $database->get_all("SELECT *
														FROM boorus
														WHERE approved = ?
														ORDER BY weight ASC, booru_name ASC", array("Y"));
						
						$this->theme->show_manage($main_cats, $sub_cats, $boorus_u, $boorus_a, $mode);
					} else { $this->theme->display_permission_denied($page); }
					break;
				case "b_add":
					if(!$user->is_anonymous()) {
							$name = html_escape($_POST['booru_name']);
							$url = html_escape($_POST['booru_url']);
							$cat_id = int_escape($_POST['cat_id']);
							
							if(!isset($_POST['approved'])) {$approved = "N";} else {$approved = "Y";}
							
							$database->execute("INSERT INTO boorus (id, booru_name, booru_url, owner_id,
												cat_id, timestamp, approved, weight) VALUES (?, ?, ?, ?, ?, now(), ?, ?)", 
												array(NULL, $name,
															$url,
															$user->id,
															$cat_id,
															$approved, 50));

                            log_info("overbooru_menu", "Added booru #$id");
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("menu/submit/"));
					} else { $this->theme->display_permission_denied($page); }
					break;
				case "b_approve":
					if($user->is_admin()) {
                            $id = int_escape($_POST['id']);
                            if(!isset($_POST['id'])) { die("No ID!"); }
                            $database->Execute("UPDATE `boorus` SET `approved` = ? WHERE `boorus`.`id` = ? LIMIT 1;", array("Y", $id));
                            log_info("overbooru_menu", "Approved booru #$id");
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("menu/manage/boorus"));
					} else { $this->theme->display_permission_denied($page); }
					break;
				case "b_disapprove":
					if($user->is_admin()) {
                            $id = int_escape($_POST['id']);
                            if(!isset($_POST['id'])) { die("No ID!"); }
                            $database->Execute("UPDATE `boorus` SET `approved` = ? WHERE `boorus`.`id` = ? LIMIT 1;", array("N", $id));
                            log_info("overbooru_menu", "Approved booru #$id");
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("menu/manage/boorus"));
					} else { $this->theme->display_permission_denied($page); }
					break;
				case "b_change":
					if($user->is_admin()) {
                            $id = int_escape($_POST['id']);
							$name = html_escape($_POST['booru_name']);
							$url = html_escape($_POST['booru_url']);
							$cat_id = int_escape($_POST['cat_id']);
							$weight = int_escape($_POST['weight']);
                            if(!isset($_POST['id'])) { die("No ID!"); }
							if($_POST['weight'] == "") { $weight = 50; }
                            $database->Execute("UPDATE `boorus` SET `booru_name` = ?, `booru_url` = ?, `cat_id` = ?, `weight` = ? WHERE `boorus`.`id` = ? LIMIT 1;", array($name, $url, $cat_id, $weight, $id));
                            log_info("overbooru_menu", "Changed booru #$id");
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("menu/manage/boorus"));
					} else { $this->theme->display_permission_denied($page); }
					break;
				case "b_remove":
					if($user->is_admin()) {
						$id = int_escape($_POST['id']);
						if(!isset($id)) { die("No ID!"); }
						$database->Execute("DELETE FROM boorus WHERE id=?", array($id));
						log_info("overbooru_menu", "Removed booru #$id");
						$page->set_mode("redirect");
						$page->set_redirect(make_link("menu/manage/boorus"));
					} else { $this->theme->display_permission_denied($page); }
					break;
				case "c_add":
					if($user->is_admin()) {
							$name = html_escape($_POST['cat_name']);
							$parent_id = int_escape($_POST['parent_id']);
							$weight = int_escape($_POST['weight']);
							if(isset($_POST['visible'])) { $visible = "Y"; } else { $visible = "N"; }
							if($_POST['weight'] == "") { $weight = 50; }
							$database->execute("INSERT INTO categories (id, cat_name, cat_parent_id, visible, weight) VALUES (?, ?, ?, ?, ?)", 
								array(NULL, $name, $parent_id, $visible, $weight));
                            log_info("overbooru_menu", "Added category #$id");
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("menu/manage/cats"));
					} else { $this->theme->display_permission_denied($page); }
					break;
				case "c_change":
					if($user->is_admin()) {
                            $id = int_escape($_POST['id']);
							$name = html_escape($_POST['cat_name']);
							$parent_id = int_escape($_POST['parent_id']);
							$weight = int_escape($_POST['weight']);
							if(isset($_POST['visible'])) { $visible = "Y"; } else { $visible = "N"; }
                            if(!isset($_POST['id'])) { die("No ID!"); }
                            $database->Execute("UPDATE `categories` SET `cat_name` = ?, `cat_parent_id` = ?, `visible` = ?, `weight` = ? WHERE `categories`.`id` = ? LIMIT 1;", array($name, $parent_id, $visible, $weight, $id));
                            log_info("overbooru_menu", "Changed category #$id");
                            $page->set_mode("redirect");
                            $page->set_redirect(make_link("menu/manage/cats"));
					} else { $this->theme->display_permission_denied($page); }
					break;
				case "c_remove":
					if($user->is_admin()) {
						$id = int_escape($_POST['id']);
						if(!isset($id)) { die("No ID!"); }
						$database->Execute("DELETE FROM categories WHERE id=?", array($id));
						log_info("overbooru_menu", "Removed category #$id");
						$page->set_mode("redirect");
						$page->set_redirect(make_link("menu/manage/cats"));
					} else { $this->theme->display_permission_denied($page); }
					break;
			}
		}
	}
	
    public function onUserBlockBuilding($event) {
        global $user;
		//$event->add_link("Booru Menu", make_link("menu/index"));
		$event->add_link("Submit a Booru", make_link("menu/submit"));
        if($user->is_admin()) {
                $event->add_link("Manage Boorus", make_link("menu/manage/boorus"));
				$event->add_link("Manage Categories", make_link("menu/manage/cats"));
        }
    }
}