<?php
/*
 * Name: News
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Show a short amount of text in /news/ (Modified by Zach)
 * Documentation:
 *  Any HTML is allowed
 */

class News extends SimpleExtension {
	public function onPageRequest($event) {
		global $config, $page;
		if($event->page_matches("news") {
			if(strlen($config->get_string("news_text")) > 0) {
				$this->theme->display_news($page, $config->get_string("news_text"));
			}
		}
	}

	public function onSetupBuilding($event) {
		$sb = new SetupBlock("News");
		$sb->add_longtext_option("news_text");
		$event->panel->add_block($sb);
	}
}
?>
