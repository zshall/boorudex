<?php
class SiteDescriptionTest extends SCoreWebTestCase {
	function testSiteDescription() {
		$this->log_in_as_admin();
		$this->get_page('setup');
		$this->assert_title("SCore Setup");
		$this->set_field("_config_site_description", "A SCore testbed");
		$this->set_field("_config_site_keywords", "foo,bar,baz");
		$raw = $this->click("Save Settings");

		$header = '<meta name="description" content="A SCore testbed">';
		$this->assertTrue(strpos($raw, $header) > 0);
		$this->assertTrue(strpos($raw, "foo") > 0);

		$this->log_out();
	}
}
?>
