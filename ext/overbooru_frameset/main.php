<?php
/*
* Name: Overbooru Frameset
* Author: Zach Hall <zach@sosguy.net> [http://seemslegit.com]
* License: GPLv2
* Description: Designed for the Overbooru: homepage.
*/

class OverbooruFrameset extends SimpleExtension {
	public function onPageRequest($event) {
		if($event->page_matches('frameset')) {
			global $page;
			$page->set_mode("data");
			$html = $this->page_html();
			$page->set_data($html);
		}
	}
	
	private function page_html() {
	$html = "
	<html><head> 
	<title>The Overbooru</title> 
	</head> 
	 
	<frameset cols='120,*'> 
	 
	<frame src='".make_link('menu/index')."'> 
	<frame src='".make_link('iotd/index')."' name='main'> 
	 
	</frameset></html>
	";
	return $html;
	}
}
?>