<?php
/*
* Name: Overbooru Banner Exchange
* Author: Zach Hall <zach@sosguy.net> [http://seemslegit.com]
* License: GPLv2
* Description: Designed for the Overbooru: making boorus more popular.
*/

class OverbooruBannerExchange extends SimpleExtension {
	public function onInitExt($event) {
		/**
		 * I love re-using this installer don't I...
		 */
		global $config;
		$version = $config->get_int("banner_version", 0);
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
			$database->create_table("banner_exchange", "
					booru_id SCORE_AIPK,
					owner_id INTEGER NOT NULL,
					booru_name TEXT NOT NULL,
					booru_url TEXT NOT NULL,
					booru_img TEXT NOT NULL,
					i_sent INTEGER NOT NULL DEFAULT '0',
					i_recv INTEGER NOT NULL DEFAULT '0',
					c_earn INTEGER NOT NULL DEFAULT '0',
					c_left DECIMAL(18,2) NOT NULL DEFAULT '0.00',
					clicks INTEGER NOT NULL DEFAULT '0',
					timestamp SCORE_DATETIME DEFAULT SCORE_NOW,
					approved ENUM('a', 'u', 'i') NOT NULL DEFAULT 'u'
					");
			// Insert sample data:
			$database->execute("INSERT INTO banner_exchange (booru_id, owner_id, booru_name, booru_url, booru_img, i_sent, i_recv, c_earn, c_left, clicks, timestamp, approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now(), ?)", 
					array(NULL, 2, "motivators", "http://seemslegit.com/", "http://seemslegit.com/favicon.ico", 0, 0, 0, 0, 0, "a"));
			log_info("overbooru_iotd", "Installed tables for the Overbooru Banner Exchange.");
			$config->set_int("banner_version", 1);
		}
		$config->set_default_int("banner_ratio", 100);
		$config->set_default_int("banner_width", 468);
		$config->set_default_int("banner_height", 60);
		
		$config->set_default_string("banner_default_name", "Overbooru Banner Exchange");
		$config->set_default_string("banner_default_url",  "http://pinochan.net/overbooru");
		$config->set_default_string("banner_default_img", "#CHANGEME");
	}
	
	public function onSetupBuilding($event) {
		$sb = new SetupBlock("Banner Exchange");
		$sb->add_label("Default banner:");
		$sb->add_text_option("banner_default_name", "Name: ");
		$sb->add_text_option("banner_default_url", "<br>URL: ");
		$sb->add_text_option("banner_default_img", "<br>Image URL: ");
		$sb->add_int_option("banner_default_clicks", "<br>Default Banner Click Count: ");
		$sb->add_label("Banner Specifications:");
		$sb->add_int_option("banner_width", "<br>Width: ");
		$sb->add_int_option("banner_height", "<br>Height: ");
		$sb->add_int_option("banner_ratio", "<br>Ratio (x/100): ");
		$event->panel->add_block($sb);
	}

	public function onPageRequest($event) {
		global $page, $database, $config, $user;
		
		$ratio = $config->get_int("banner_ratio") / 100;
		$width = $config->get_int("banner_width");
		$height = $config->get_int("banner_height");
		
		if($event->page_matches("booru_exchange")) {
			switch($event->get_arg(0)) {
				// Stuff the user can do and see:
				case "signup":
					if(!$user->is_anonymous()) {
						$info = $this->get_info_by_user($user, $database);
						$this->theme->display_signup($page, $user, $info, $width, $height);
					} else { $page->set_mode("redirect"); $page->set_redirect(make_link("user_admin/login")); }
					break;
				case "account":
					$info = $this->get_info_by_user($user, $database);
					if(isset($info['booru_id'])) {
						$this->theme->display_account($page, $info, $width, $height);
					} else { $this->goto_place("index",$page); }
					break;
				case "html_code":
					$info = $this->get_info_by_user($user, $database);
					if(isset($info['booru_id'])) {
						$this->theme->display_html_code($page, $info, $width, $height);
					} else { $this->goto_place("index",$page); }
					break;
				case "stats":
					$info = $this->get_info_by_user($user, $database);
					if(isset($info['booru_id'])) {
						$this->theme->display_stats($page, $info);
					} else { $this->goto_place("index",$page); }
					break;
				// Stuff the admin can do:
				case "admin":
					switch($event->get_arg(1)) {
						case "list":
							if($user->is_admin()) {
								$sites_a = $this->get_sites_a($database);
								$sites_u = $this->get_sites_u($database);
								$this->theme->display_list($page, $sites_a, $sites_u);
							}
							break;
						case "edit":
							if($user->is_admin()) {
								$check = $this->is_booru(int_escape($event->get_arg(2)), $database);
								if(!is_null($event->get_arg(2)) && $check == true) {
									$info = $this->get_info_by_id($event->get_arg(2), $database);
									$this->theme->display_account($page, $info, $width, $height);
								}
							}
							break;
						case "ignored_sites":
							if($user->is_admin()) {
								$sites_i = $this->get_sites_i($database);
								$this->theme->display_ignored_sites($page, $sites_i);
							}
							break;
						case "stats_reset":
							if($user->is_admin()) {
								$this->theme->display_stats_reset($page, $database);
							}
							break;
					}
					break;
				// Stuff that can be done:
				case "action":
					if(isset($_POST['owner_id'])) { $owner_id 	= $_POST['owner_id']; }
					if(isset($_POST['booru_id'])) { $booru_id 	= $_POST['booru_id']; }
					if(isset($_POST['booru_name'])) { $booru_name = $_POST['booru_name']; }
					if(isset($_POST['booru_url'])) { $booru_url 	= $_POST['booru_url']; }
					if(isset($_POST['booru_img'])) { $booru_img 	= $_POST['booru_img']; }
					if(isset($_POST['approved'])) { $approved 	= $_POST['approved']; }
					
					if($event->get_arg(1)=="create") {
						$info = $this->get_info_by_user($user, $database);
						if(is_null($info['booru_id'])) {
							$this->create($owner_id, $booru_name, $booru_url, $booru_img, $approved, $database);
							$this->goto_place("html_code", $page);
						}
					} else {
						if($user->is_admin() || $user->id == $owner_id) {
							switch($event->get_arg(1)) {
								case "edit":
									$this->edit($booru_id, $booru_name, $booru_url, $booru_img, $database);
									if($user->is_admin()) { $this->goto_place("admin/edit/".$booru_id,$page); } else { $this->goto_place("account",$page); }
									break;
								case "remove":
									$this->remove($booru_id, $database);
									$this->goto_place("admin/list",$page);
									break;
							}
						}
						if($user->is_admin()) {
							switch($event->get_arg(1)) {
								case "approve":
									$this->approve($booru_id, $database);
									$this->goto_place("admin/list",$page);
									break;
								case "unapprove":
									$this->unapprove($booru_id, $database);
									$this->goto_place("admin/list",$page);
									break;
								case "ignore":
									$this->ignore($booru_id, $database);
									$this->goto_place("admin/list",$page);
									break;
								case "stats_reset":
									if($user->is_admin()) {
										$this->reset_stats($database);
										$this->goto_place("admin/list",$page);
									}
									break;
							}
						}
					}
					break;

				// Finally, display the banner:
				case "display":
					if(is_null($event->get_arg(1))) { die("No user ID"); }
					$check = $this->is_booru(int_escape($event->get_arg(1)), $database);
					if(isset($_SERVER['HTTP_REFERER'])) {
						$info = $this->get_info_by_id($event->get_arg(1), $database);
						if($this->find_domain($info['booru_url']) == $this->find_domain($_SERVER['HTTP_REFERER'])) { $log_this = true; } else { $log_this = false; }
					} else {
						$log_this = false;
					}
					if(!is_null($event->get_arg(1)) && $check == true) {
						$booru_id_exclude = $event->get_arg(1);
						$booru_to_display = $this->get_info_by_random($booru_id_exclude, $database);
						if(is_null($booru_to_display['booru_id'])) {
							$booru_to_display['booru_id'] = "default";
							$booru_to_display['booru_name'] = $config->get_string("banner_default_name");
							$booru_to_display['booru_url'] = $config->get_string("banner_default_url");
							$booru_to_display['booru_img'] = $config->get_string("banner_default_img");
							if($log_this == true) $this->earn_impression($booru_id_exclude, $ratio, $config, $database);
						} else {
							if($log_this == true) $this->balance_stats($booru_id_exclude, $booru_to_display, $ratio, $database);
							else $this->spend_impression($booru_to_display, $ratio, $config, $database);
						}
						$this->theme->display_banner($page, $booru_to_display, $width, $height);
					}
					break;
				
				// Log a click:
				case "go":
					if($event->get_arg(1)=="default") {
						$config->set_int("banner_default_clicks", $config->get_int("banner_default_clicks") + 1);
						$page->set_mode("redirect");
						$page->set_redirect($config->get_string("banner_default_url"));
					} else {
						$check = $this->is_booru(int_escape($event->get_arg(1)), $database);
						if(!is_null($event->get_arg(1)) && $check == true) {
							$info  = $this->get_info_by_id($event->get_arg(1), $database);
							$this->log_click($event->get_arg(1), $database);
							$page->set_mode("redirect");
							$page->set_redirect($info['booru_url']);
						}
					}
					break;
				
				// If all else fails, display the index:
				case "index":
				default:
					$info = $this->get_info_by_user($user, $database);
					$this->theme->display_index($page, $info);
					break;
			}
		}
	}
	
    public function onUserBlockBuilding($event) {
        global $user, $database, $page;
		$event->add_link("Booru Exchange", make_link("booru_exchange/index"));
        if($user->is_admin()) {
				$new_boorus = $database->get_row("SELECT COUNT(*) FROM banner_exchange WHERE approved = ?", array("u"));
				if($new_boorus['COUNT(*)'] > 0) {$c = " (".$new_boorus['COUNT(*)'].")";} else {$c = "";}
                $event->add_link("Manage Banners$c", $this->linkto_place("admin/list", $page));
        }
    }
	
	// GET functions
	private function get_info_by_user($user, $database) {
		$info = $database->get_row("SELECT *
									FROM banner_exchange
									WHERE owner_id = ?
									LIMIT 1
									", array($user->id));
		return $info;
	}
	private function get_info_by_id($booru_id, $database) {
		$info = $database->get_row("SELECT *
									FROM banner_exchange
									WHERE booru_id = ?
									LIMIT 1
									", array($booru_id));
		return $info;
	}
	private function get_info_by_random($booru_id_exclude, $database) {
		$info = $database->get_row("SELECT banner_exchange.*,
									MD5(RAND()) AS random_int
									FROM banner_exchange
									WHERE approved = ?
									AND c_left >= 1
									AND NOT(booru_id = ?)
									ORDER BY random_int
									LIMIT 1
									", array("a", $booru_id_exclude));
		return $info;
	}
	private function get_sites_a($database) {
		$boorus_a = $database->get_all("SELECT *
										FROM banner_exchange
										WHERE approved = ?
										ORDER BY booru_name ASC", array("a"));
		return $boorus_a;
	}
	private function get_sites_u($database) {
		$boorus_u = $database->get_all("SELECT *
										FROM banner_exchange
										WHERE approved = ?
										ORDER BY booru_name ASC", array("u"));
		return $boorus_u;
	}
	private function get_sites_i($database) {
		$boorus_i = $database->get_all("SELECT *
										FROM banner_exchange
										WHERE approved = ?
										ORDER BY booru_name ASC", array("i"));
		return $boorus_i;
	}
	
	// Administrative functions
	private function check($owner_id, $booru_name, $booru_url, $booru_img) {
		assert(is_numeric($owner_id));
		assert(is_string($booru_name));
		assert(is_string($booru_url));
		assert(is_string($booru_img));
		
		$clean['owner_id'] 		= int_escape($owner_id);
		$clean['booru_name'] 	= html_escape($booru_name);
		
		return $clean;
	}
	private function create($owner_id, $booru_name, $booru_url, $booru_img, $approved=null, $database) {
		$clean 		= $this->check($owner_id, $booru_name, $booru_url, $booru_img);
		$owner_id 	= $clean['owner_id'];
		$booru_name = $clean['booru_name'];
		
		if(is_null($approved)) {$approved = "u";} else {$approved = "a";}
							
		$database->execute("INSERT INTO banner_exchange (booru_id, owner_id, booru_name, booru_url, booru_img, i_sent, i_recv, c_earn, c_left, timestamp, approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, now(), ?)", array(NULL, $owner_id, $booru_name, $booru_url, $booru_img, 0, 0, 0, 0, $approved));

		log_info("overbooru_banner_exchange", "Added booru: $booru_name");
	}
	private function edit($booru_id, $booru_name, $booru_url, $booru_img, $database) {
		$clean 		= $this->check($booru_id, $booru_name, $booru_url, $booru_img);
		$owner_id 	= $clean['owner_id'];
		$booru_name = $clean['booru_name'];
		$database->Execute("UPDATE `banner_exchange` SET `booru_name` = ?, `booru_url` = ?, `booru_img` = ? WHERE `banner_exchange`.`booru_id` = ? LIMIT 1;", array($booru_name, $booru_url, $booru_img, $booru_id));
	}
	private function approve($booru_id, $database) {
		assert(is_numeric($booru_id));
		$booru_id = int_escape($booru_id);
		$database->Execute("UPDATE `banner_exchange` SET `approved` = ? WHERE `banner_exchange`.`booru_id` = ? LIMIT 1;", array("a", $booru_id));
		log_info("overbooru_banner_exchange", "Approved booru #$booru_id");
	}
	private function unapprove($booru_id, $database) {
		assert(is_numeric($booru_id));
		$booru_id = int_escape($booru_id);
		$database->Execute("UPDATE `banner_exchange` SET `approved` = ? WHERE `banner_exchange`.`booru_id` = ? LIMIT 1;", array("u", $booru_id));
		log_info("overbooru_banner_exchange", "Unapproved booru #$booru_id");
	}
	private function ignore($booru_id, $database) {
		assert(is_numeric($booru_id));
		$booru_id = int_escape($booru_id);
		$database->Execute("UPDATE `banner_exchange` SET `approved` = ? WHERE `banner_exchange`.`booru_id` = ? LIMIT 1;", array("i", $booru_id));
		log_info("overbooru_banner_exchange", "Ignored booru #$booru_id");
	}
	private function remove($booru_id, $database) {
		assert(is_numeric($booru_id));
		$booru_id = int_escape($booru_id);
		$database->Execute("DELETE FROM `banner_exchange` WHERE `booru_id` = ?", array($booru_id));
		log_info("overbooru_banner_exchange", "Removed booru #$booru_id");
	}
	private function reset_stats($database) {
		$database->Execute("UPDATE banner_exchange SET i_sent = 0, i_recv  = 0, c_left = 0, c_earn = 0, clicks = 0");
		log_info("overbooru_banner_exchange", "All stats have been reset to 0.");
	}
	
	// Stat logging functions
	private function balance_stats($booru_id_exclude, $booru_to_display, $ratio, $database) {
		$database->Execute("UPDATE banner_exchange SET i_sent = i_sent + 1, c_earn = (i_sent*$ratio), c_left = (c_left+$ratio) WHERE booru_id = ?",array($booru_id_exclude));
		$database->Execute("UPDATE banner_exchange SET c_left = c_left-1, i_recv = i_recv + 1 WHERE booru_id = ?",array($booru_to_display['booru_id']));
	}
	private function earn_impression($booru_id, $ratio, $config, $database) {
		$database->Execute("Update banner_exchange SET i_sent = i_sent + 1, c_earn = (i_sent*$ratio), c_left = (c_left+$ratio) WHERE booru_id = ?",array($booru_id));
	}
	private function spend_impression($booru_to_display, $ratio, $config, $database) {
		$database->Execute("UPDATE banner_exchange SET c_left = c_left-1, i_recv = i_recv + 1 WHERE booru_id = ?",array($booru_to_display['booru_id']));
	}
	private function log_click($booru_id, $database) {
		$database->Execute("UPDATE banner_exchange SET clicks = clicks+1 WHERE booru_id = ?",array($booru_id));
	}
	
	// Booru validation function
	private function is_booru($booru_id, $database) {
		$info = $this->get_info_by_id($booru_id, $database);
		if(!is_null($info['booru_id'])) {return true;} else {return false;}
	}
	
	// GOTO functions
	private function goto_place($place, $page) {
		$page->set_mode("redirect");
		$page->set_redirect(make_link("booru_exchange/".$place));
	}
	private function linkto_place($place) {
		return make_link("booru_exchange/".$place);
	}
	
	// Miscellaneous Functions
	private function find_domain($url) {
		preg_match ("/^(http:\/\/|https:\/\/)?([^\/]+)/i", $url, $matches);
		$host = $matches[2];  
		preg_match ("/[^\.\/]+\.[^\.\/]+$/", $host, $matches);
		return strtolower("{$matches[0]}"); 
	}
	
}
?>