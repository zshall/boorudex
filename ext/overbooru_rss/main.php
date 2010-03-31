<?php
/*
 * Name: Overbooru RSS
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Modified for the Overbooru by Zach Hall <zach@sosguy.net>
 */

class RSS_IOTD extends SimpleExtension {
	public function onPageRequest($event) {
		global $config, $page, $database;
		$title = $config->get_string('title');

		$page->add_header("<link rel=\"alternate\" type=\"application/rss+xml\" ".
			"title=\"$title\" href=\"".make_link("rss/iotd")."\" />");

		global $config, $database, $page;
		if($event->page_matches("rss/iotd")) {
			$page->set_mode("data");
			$page->set_type("application/rss+xml");

			$iotdq = $database->get_all("
					SELECT
					iotd.id as id,
					iotd.image_source as image_source,
					UNIX_TIMESTAMP(timestamp) AS posted_timestamp
					FROM iotd
					WHERE approved = ?
					ORDER BY iotd.id DESC
					LIMIT 10
					", "Y");

			$data = "";
			foreach($iotdq as $iotd) {
				$id = $iotd['id'];
				$image_source = $iotd['image_source'];
				
				$posted = date(DATE_RSS, $iotd['posted_timestamp']);
				$clean_date = date("m/d/y", strtotime($posted));
				$data .= "
					<item>
						<title>$clean_date - $image_source</title>
						<link>$image_source</link>
						<guid isPermaLink=\"false\">$id</guid>
						<pubDate>$posted</pubDate>
						<description>$clean_date - $image_source</description>
					</item>
				";
			}

			$title = $config->get_string('title');
			$base_href = make_http($config->get_string('base_href'));
			$version = $config->get_string('version');
			$xml = <<<EOD
<?xml version="1.0" encoding="utf-8" ?>
<rss version="2.0">
	<channel>
		<title>$title</title>
		<description>Latest IOTD Images</description>
		<link>$base_href</link>
		<generator>$version</generator>
		<copyright>(c) 2010 Overbooru</copyright>
		$data
	</channel>
</rss>
EOD;
			$page->set_data($xml);
		}
	}
}
?>
