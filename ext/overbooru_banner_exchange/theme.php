<?php
class OverbooruBannerExchangeTheme extends Themelet {
	/**
	 * Banner Exchange Frontend
	 */
    public function display_index($page, $info) {
		$html = $this->get_index_html($info);
		$page->set_title("Overbooru Banner Exchange!");
		$page->add_block(new Block(NULL, $html, "main", 5));
	}
	public function display_signup($page, $user, $info, $width, $height) {
		$html = $this->get_signup_html($user, $info, $width, $height);
		$page->set_title("Add your booru");
		$page->add_block(new Block(NULL, $html, "main", 5));
	}
	public function display_account($page, $info, $width, $height) {
		$html = $this->get_account_html($info, $width, $height);
		$page->set_title("Banner exchange account info");
		$page->add_block(new Block(NULL, $html, "main", 5));
	}
	public function display_html_code($page, $info, $width, $height) {
		$html = $this->get_html_code_html($info, $width, $height);
		$page->set_title("Banner exchange HTML code");
		$page->add_block(new Block(NULL, $html, "main", 5));
	}
	public function display_stats($page, $info) {
		$html = $this->get_stats_html($info);
		$page->set_title("Banner exchange statistics");
		$page->add_block(new Block(NULL, $html, "main", 5));
	}
	/**
	 * Banner Exchange Backend
	 */
	public function display_list($page, $sites_a, $sites_u) {
		$html_u = $this->get_manage_sites_html($sites_u, "Unapproved Boorus");
		$html_a = $this->get_manage_sites_html($sites_a, "Approved Boorus");
		$page->set_title("Manage banner exchange sites");
		$page->add_block(new Block(NULL, $html_u, "main", 5));
		$page->add_block(new Block(NULL, $html_a, "main", 6));
	}
	public function display_ignored_sites($page, $sites_i) {
		$html = $this->get_manage_sites_html($sites_i, "Ignored Boorus");
		$page->set_title("Manage banner exchange sites");
		$page->add_block(new Block(NULL, $html, "main", 5));
	}
	public function display_stats_reset($page, $database) {
		$html = $this->get_stats_reset_html();
		$page->set_title("Reset banner exchange stats");
		$page->add_block(new Block(NULL, $html, "main", 5));
	}
	
	/**
	 * Banner Exchange IFRAME
	 */
	public function display_banner($page, $booru_to_display, $width, $height) {
		$html = $this->get_banner_html($booru_to_display, $width, $height);
		$page->set_mode("data");
		$page->set_data($html);
	}
	
	// GET functions:
	private function get_index_html($info) {
		if(is_null($info['booru_id'])) { 
			$signup = "<p><b><a href='".$this->goto_place("signup")."'>Get Started</a></b></p>";
			$stats = "";
			$account = "";
			$html_code = "";
		} else {
			$signup = "";
			$stats = "<p><a href='".$this->goto_place("stats")."' style='color:green;font-size:1.2em;'>View statistics</a>";
			$account = "<p><a href='".$this->goto_place("account")."'>Edit banner</a>";
			$html_code = "<p><a href='".$this->goto_place("html_code")."'>Get embed code</a>";
		}
		return "
			<h2>Booru Exchange!</h2>
			<p>There are many ways of getting additional traffic if you are a *chan site, but none existed for boorus... until now!</p>
			$signup $stats $account $html_code
		";
	}
	private function get_signup_html($user, $info, $width, $height) {
		if(!isset($info['booru_id'])) {
			if($user->is_admin()) {
				$admin = "
					<tr>
						<td>Booru Approved</td>
						<td><input name='approved' type='checkbox' value='a' checked /></td>
					</tr>
				";
			} else {
				$admin = "";
			}
			return "
				<h2>Banner Exchange Signup Form</h2>
				<form action='".$this->goto_place("action/create")."' method='post' name='signup'>
				<input name='owner_id' type='hidden' value='".$user->id."' />
				<table>
					<tr>
						<td>Booru Name</td>
						<td><input name='booru_name' type='text' maxlength='64' /></td>
					</tr>
					<tr>
						<td>Booru URL</td>
						<td><input name='booru_url' type='text' maxlength='255' /></td>
					</tr>
					<tr>
						<td>Booru Image URL ($width x $height px) <span title='DO NOT use image upload sites such as imgur for banner hosting unless they specifically state you can in their Terms of Service. Imgur, imageshack, photobucket all have policies against it.'>[!]</span></td>
						<td><input name='booru_img' type='text' maxlength='255' /></td>
					</tr>
					$admin
					<tr>
						<td colspan='2'><input name='submit' type='submit' value='Sign Up' /></td>
					</tr>
				</table>
				</form>
			";
		} else {
			return "
				<p>You are already managing a site! To prevent spam, there is a limit of 1 site per user, since most users only run one booru.<br />
				If you do run more than one booru, <a href='mailto:zach@sosguy.net'>send me an email</a> and I'll be happy to let you manage more sites!</p>
			";
		}
	}
	private function get_account_html($info, $width, $height) {
		return "
			<h2>Banner Exchange Account Information</h2>
			<form action='".$this->goto_place("action/edit")."' method='post' name='edit'>
			<input name='booru_id' type='hidden' value='".$info['booru_id']."' />
			<table>
				<tr>
					<td>Booru Name</td>
					<td><input name='booru_name' type='text' maxlength='64' value='".$info['booru_name']."' /></td>
				</tr>
				<tr>
					<td>Booru URL</td>
					<td><input name='booru_url' type='text' maxlength='255' value='".$info['booru_url']."' /></td>
				</tr>
				<tr>
					<td>Booru Image URL ($width x $height px) <span title='DO NOT use image upload sites such as imgur for banner hosting unless they specifically state you can in their Terms of Service. Imgur, imageshack, photobucket all have policies against it.'>[!]</span></td>
					<td><input name='booru_img' type='text' maxlength='255' value='".$info['booru_img']."' /></td>
				</tr>
				<tr>
					<td colspan='2'>
						<input name='submit' type='submit' value='Edit Account Information' />
					</td>
				</tr>
			</table>
			</form>
			<a href='".$this->goto_place("index")."'>Return</a>
		";
	}
	private function get_html_code_html($info, $width, $height) {
		return "
			<h2>Banner Exchange HTML Code</h2>
			<table>
				<tr>
					<td>
						<textarea name='html_code' style='width:300px;' rows='5'>
<iframe src='http://".$_SERVER['HTTP_HOST'].$this->goto_place("/display/".$info['booru_id'])."' width=$width height=$height marginwidth=0 marginheight=0 hspace=0 vspace=0 frameborder=0 scrolling='no'></IFRAME>
						</textarea>
					</td>
				</tr>
			</table>
			<br />
			<a href='".$this->goto_place("index")."'>Return</a>
		";
	}
	private function get_stats_html($info) {
		if($info['i_recv'] > 0) {
			$clickthrough_ratio = number_format(round((($info['clicks'] / $info['i_recv']) * 100), 2),2);
		} else {
			$clickthrough_ratio = "0.00";
		}
		return "
			<h2>Banner Exchange Stats</h2>
			<table>
				<tr>
					<td>
						<span style='text-size:2em; font-weight:bold; color:green;'>Your banner's impressions: ".$info['i_recv']."</span><br />
						<span style='text-size:2em; font-weight:bold; color:green;'>Clickthrough percent: ".$clickthrough_ratio."%</span><br />
						<span style='text-size:1.5em; font-weight:bold; color:red;'>Remaining credits: ".$info['c_left']."</span>
					</td>
				</tr>
				<tr>
					<td>Total clickthroughs: ".$info['clicks']."</td>
				</tr>
				<tr>
					<td>Times you've shown a banner: ".$info['i_sent']."</td>
				</tr>
				<tr>
					<td>Total Credits Earned: ".$info['c_earn']."</td>
				</tr>
			</table>
		";
	}
	
	private function get_manage_sites_html($sites, $title) {
		// booru_id, owner_id, booru_name, booru_url, booru_img, i_sent, i_recv, i_earn, c_left, timestamp, approved
		if(count($sites)==0) { return NULL; }
		$html = "
			<h2>$title</h2>
			<table>
				<tr>
					<th colspan='5'>General Info</th>
					<th colspan='2'>Impressions</th>
					<th colspan='2'>Credits</th>
					<th>Other</th>
				</tr>
				<tr>
					<th>ID</th>
					<th>Owner</th>
					<th>Name</th>
					<th>URL</th>
					<th>Image</th>
					<th>Sent</th>
					<th>Received</th>
					<th>Earned</th>
					<th>Remaining</th>
					<th>Joined</th>
					<th><span color='green'>Action</span></th>
				</tr>
		";
		
		$ti = 0; // total sent
		$to = 0; // total recieved
		$te = 0; // total earned
		$tr = 0; // total remaining
		
		for ($i = 0 ; $i < count($sites) ; $i++) {
			$user_in_question = User::by_id($sites[$i]['owner_id']);
			$owner_name = $user_in_question->name;
			//$join_date = date("m-d-y", $sites[$i]['timestamp']);
			
			$ti = $ti + $sites[$i]['i_sent'];
			$to = $to + $sites[$i]['i_recv'];
			$te = $te + $sites[$i]['c_earn'];
			$tr = $tr + $sites[$i]['c_left'];
			
			switch($sites[$i]['approved']) {
				case "a":
					$forms = $this->get_unapprove($sites[$i]['booru_id']);
					$forms .= $this->get_ignore($sites[$i]['booru_id']);
					$forms .= $this->get_remove($sites[$i]['booru_id']);
					break;
				case "u":
					$forms = $this->get_approve($sites[$i]['booru_id']);
					$forms .= $this->get_ignore($sites[$i]['booru_id']);
					$forms .= $this->get_remove($sites[$i]['booru_id']);
					break;
				case "i":
					$forms = $this->get_approve($sites[$i]['booru_id']);
					$forms .= $this->get_unapprove($sites[$i]['booru_id']);
					$forms .= $this->get_remove($sites[$i]['booru_id']);
					break;
				default:
					die("Unknown approval status: ".$sites[$i]['approved']." in ".$sites[$i]['booru_name'].".");
			}
			
			$html .= "
				<tr>
					<td>".$sites[$i]['booru_id']."</td>
					<td>".$owner_name."</td>
					<td>".$sites[$i]['booru_name']."</td>
					<td><a href='".$sites[$i]['booru_url']."'>URL</a></td>
					<td><a href='".$sites[$i]['booru_img']."'>URL</a></td>
					<td>".$sites[$i]['i_sent']."</td>
					<td>".$sites[$i]['i_recv']."</td>
					<td>".$sites[$i]['c_earn']."</td>
					<td>".$sites[$i]['c_left']."</td>
					<td>".$sites[$i]['timestamp']."</td>
					<td>
						<a href='".$this->goto_place("admin/edit/".$sites[$i]['booru_id'])."' title='Edit'>E</a> 
						$forms
					</td>
				</tr>
			";
		}
		
		$html .= "
			<tr>
				<th colspan='5'>Totals:</th>
				<td>".$ti."</td>
				<td>".$to."</td>
				<td>".$te."</td>
				<td>".$tr."</td>
			</tr>
		";
		
		$html .= "
			</table><br />
			<a href='".$this->goto_place("admin/ignored_sites")."'>Ignored Sites</a><br />
			<a href='".$this->goto_place("admin/stats_reset")."'>Reset Statistics</a>
		";
		return $html;
	}
	private function get_approve($booru_id) {
		return "
			<form action='".$this->goto_place("action/approve")."' method='post' name='approve' style='display:inline;'>
				<input name='booru_id' type='hidden' value='".$booru_id."' />
				<input name='submit' type='submit' style='color:#0000FF; background-color:#EEEEEE; border-style:none;' value='A' title='Approve' /> 
			</form>
		";
	}
	private function get_unapprove($booru_id) {
		return "
			<form action='".$this->goto_place("action/unapprove")."' method='post' name='unapprove' style='display:inline;'>
				<input name='booru_id' type='hidden' value='".$booru_id."' />
				<input name='submit' type='submit' style='color:#0000FF; background-color:#EEEEEE; border-style:none;' value='U' title='Unapprove' /> 
			</form>
		";
	}
	private function get_ignore($booru_id) {
		return "
			<form action='".$this->goto_place("action/ignore")."' method='post' name='ignore' style='display:inline;'>
				<input name='booru_id' type='hidden' value='".$booru_id."' />
				<input name='submit' type='submit' style='color:#0000FF; background-color:#EEEEEE; border-style:none;' value='I' title='Ignore' /> 
			</form>
		";
	}
	private function get_remove($booru_id) {
		return "
			<form action='".$this->goto_place("action/remove")."' method='post' name='remove' style='display:inline;'>
				<input name='booru_id' type='hidden' value='".$booru_id."' />
				<input name='submit' type='submit' style='color:#0000FF; background-color:#EEEEEE; border-style:none;' value='R' title='Remove' /> 
			</form>
		";
	}
	private function get_stats_reset_html() {
		return "
			<h2>Banner Exchange Stats Reset</h2>
			<p>All statistics will be reset. This cannot be undone.</p>
			<p><a href='javascript:history.go(-1)'>Go Back</a> | <a href='".$this->goto_place('action/stats_reset')."'>Continue</a></p>
		";
	}
	
	private function get_banner_html($booru_to_display, $width, $height) {
		return "
			<html>
				<table border=0 cellpadding=0 cellspacing=0 width=$width>
					<tr>
						<td width=468 valign='top' align='left'>
							<a target='_top' href='http://".$_SERVER['HTTP_HOST']."/".$this->goto_place("go/".$booru_to_display['booru_id'])."'>
								<img border=0 src='".$booru_to_display['booru_img']."' width=$width height=$height alt='".$booru_to_display['booru_name']."'>
							</a>
						</td>
					</tr>
				</table>
			</html>
		";
	}
	
	// GOTO function:
	private function goto_place($place) {
		return make_link("booru_exchange/".$place);
	}
	
}
?>