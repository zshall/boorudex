<?php
class Handle404Test extends SCoreWebTestCase {
	function test404Handler() {
		$this->get_page('not/a/page');
		$this->assert_response(404);
		$this->assert_title('404');
		$this->assert_text("No handler could be found for the page 'not/a/page'");
	}
}
?>
