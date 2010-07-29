<?php
class PopconTheme extends Themelet {
	public function display_views($views, $total) {
		global $page;
		$page->add_block(new Block(NULL, "$views views today, $total total", "left", 40));
	}

	public function display_datinator($txt, $prev, $next) {
		global $page;
		$html = "<h2 style='text-align:center;'><a href='".make_link('popular_by_day%3D'.$prev)."'>&laquo;</a> $txt <a href='".make_link('popular_by_day%3D'.$next)."'>&raquo;</a></h2>";
		$page->set_title("Exploring $txt");
		$page->add_block(new Block(NULL, $html, "main", 0));
	}
	
	public function display_popular_by_day($sites) {
		/**
		 * Parts of the $sites variable:
		 * 		boorus.id, boorus.booru_name, boorus.booru_url, popcon.views
		 */
		print_r($sites);
	}
}
?>