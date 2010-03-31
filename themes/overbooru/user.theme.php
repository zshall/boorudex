<?php

class CustomUserPageTheme extends UserPageTheme {
	public function display_login_page($page) {
		global $config;
		$page->set_title("Login");
		$page->set_heading("Login");
		$html = "
			<form action='".make_link("user_admin/login")."' method='POST'>
				<table summary='Login Form'>
					<tr>
						<td width='70'><label for='user'>Name</label></td>
						<td width='70'><input id='user' type='text' name='user'></td>
					</tr>
					<tr>
						<td><label for='pass'>Password</label></td>
						<td><input id='pass' type='password' name='pass'></td>
					</tr>
					<tr><td colspan='2'><input type='submit' value='Log In'></td></tr>
				</table>
			</form>
		";
		if($config->get_bool("login_signup_enabled")) {
			$html .= "<small><a href='".make_link("user_admin/create")."'>Create Account</a></small>";
		}
		$page->add_block(new Block("Login", $html, "main", 90));
	}

	public function display_user_links($page, $user, $parts) {
		// no block in this theme
	}
	public function display_login_block(Page $page) {
		// no block in this theme
	}

	public function display_user_block($page, $user, $parts) {
		$h_name = html_escape($user->name);
		$html = "<span style='font-size:0.7em;'>";
		$blocked = array("Pools", "Pool Changes", "Alias Editor", "My Profile");
		foreach($parts as $part) {
			if(in_array($part["name"], $blocked)) continue;
			$html .= "[<a href='{$part["link"]}'>{$part["name"]}</a>] ";
		}
		$html .= "</span>";
		$page->add_block(new Block(NULL,$html, "subheading", 90));
	}


	public function display_ip_list($page, $comments) {
	}

	public function display_user_page(User $duser, $stats) {
		global $page;
		parent::display_user_page($duser, $stats);
	}

}
?>
