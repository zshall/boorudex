<?php
class OverbooruCatsTheme extends Themelet {
	/**
	 * Overbooru Menu Frontend
	 */
    public function show_index($main_cats, $sub_cats, $boorus) {
		global $page;
		$html = $this->generate_index($main_cats, $sub_cats, $boorus);
		$page->set_mode("data");
		$page->set_data($html);
    }
    private function generate_index($main_cats, $sub_cats, $boorus) {
		$html = "";
		$cats_html = "";
		for($i=0;$i<count($main_cats);$i++) {
			$main_html = "";
			for($k=0;$k<count($boorus);$k++) {
				if($boorus[$k]['cat_id'] == $main_cats[$i]['id']) {
					$boorus_for_cat[] = $boorus[$k];
				}
			}
			if(isset($boorus_for_cat)) {
			$main_html .= $this->generate_cat($main_cats[$i]['id'],
											 $main_cats[$i]['cat_name'],
											 $boorus_for_cat, false);
			unset($boorus_for_cat); $end_html = "</div></div>";} else { $id = $main_cats[$i]['id']; $main_html .= $this->toggle_html($id) . "<div class='container'><div id='c$id-toggle' class='header_main'>" . $main_cats[$i]['cat_name'] . "</div><div id='c$id'>";
											$end_html = "</div></div>"; }

			$sub_html = "";
			for($j=0;$j<count($sub_cats);$j++) {
				if($sub_cats[$j]['cat_parent_id'] == $main_cats[$i]['id']) {
					for($k=0;$k<count($boorus);$k++) {
						if($boorus[$k]['cat_id'] == $sub_cats[$j]['id']) {
							$boorus_for_cat[] = $boorus[$k];
						}
					}
					if(!isset($boorus_for_cat)) break;
					$sub_html .= $this->generate_cat($sub_cats[$j]['id'],
													 $sub_cats[$j]['cat_name'],
													 $boorus_for_cat, true);
					unset($boorus_for_cat);
				}
			}
			$cats_html .= $main_html . $sub_html . $end_html;
		}
		$title = "Overbooru Menu";
		
		/* Disclaimer Redirect */
		$disclaimer = "<script><!--
			$(document).ready(function() {
				if(!$.cookie(\"disclaimer\")) {
					$.cookie(\"disclaimer\", 'true', {path: '/'});
					window.self.location.replace('".make_link('menu/disclaimer')."');
				}
			});
			//--></script>
		";
		
		$html .= "<html><head><title>$title</title>
		<link rel='stylesheet' href='/ext/overbooru_cats/style.css' type='text/css'>
		<script src='/lib/jquery-1.3.2.min.js' type='text/javascript'></script> 
		<script src='/lib/jquery.auto-complete.pack.js' type='text/javascript'></script> 
		<script src='/lib/jquery.cookie.js' type='text/javascript'></script> 
		<script src='/lib/jquery.form-defaults.js' type='text/javascript'></script> 
		<script src='/lib/jquery.tablesorter.min.js' type='text/javascript'></script> 
		</head>
		<body>
		
		$disclaimer
		
		<div class='container'><span style='font-size:13px; font-weight:bold;'>The Overbooru</span><br />
		<a href='".make_link("news")."' target='main'>News</a><br />
		<a href='".make_link("iotd/index")."' target='main'>IOTD</a><br />
		<a href='http://twitter.com/overbooru' target='main'>Twitter</a><br />
		<a href='".make_link("forum")."' target='main'>Forum</a><br />
		<a href='".make_link("menu/submit")."' target='main'>Submit</a><br />
		<a href='".make_link("booru_exchange")."' target='main'>Booru Exchange</a><br /><br /></div>
		" . $cats_html . "</body></html>";
		return $html;
    }
	private function generate_cat($cat_id, $cat_name, $boorus, $sub_style) {
		$html = "";
		$cat_list = "";
		if(count($boorus) == 0) return;
		for($i=0;$i<count($boorus);$i++) {
			$cat_list .= "<a href='".$boorus[$i]["booru_url"]."' target='main'>".$boorus[$i]["booru_name"]."</a><br />";
		}
		if($sub_style == true) { $header = 'header_main'; $end_html = "</div></div>"; } else { $header = 'header_main'; $end_html = ""; }
		$cat_title = $this->toggle_html($cat_id) . "<div class='container'><div id='c$cat_id-toggle' class='$header'>$cat_name</div>";
		$html = $cat_title . "<div id='c$cat_id' class='content'>" . $cat_list . $end_html;
		
		return $html;
	}
	private function toggle_html($i) {
		$html =	"<script><!--
			$(document).ready(function() {
				if($.cookie(\"c$i-hidden\")) {
					$(\"#c$i-toggle\").click(function() {
						$(\"#c$i\").slideToggle(\"slow\", function() {
							if($(\"#c$i\").is(\":hidden\")) {
								$.cookie(\"c$i-hidden\", 'true', {path: '/'});
							}
							else {
								$.cookie(\"c$i-hidden\", 'false', {path: '/'});
							}
						});
					});
					if($.cookie(\"c$i-hidden\") == 'true') {
						$(\"#c$i\").hide();
					}
				} else {
					$.cookie(\"c$i-hidden\", 'true', {path: '/'});
					$(\"#c$i\").hide();
					$(\"#c$i-toggle\").click(function() {
						$(\"#c$i\").slideToggle(\"slow\", function() {
							if($(\"#c$i\").is(\":hidden\")) {
								$.cookie(\"c$i-hidden\", 'true', {path: '/'});
							}
							else {
								$.cookie(\"c$i-hidden\", 'false', {path: '/'});
							}
						});
					});
					if($.cookie(\"c$i-hidden\") == 'true') {
						$(\"#c$i\").hide();
					}
				}
			});
			//--></script>";
		return $html;
	}
	/**
	 * Moderation Page
	 */
    public function show_manage($main_cats, $sub_cats, $boorus_u, $boorus_a, $mode) {
		global $page;
		$html = $this->generate_manage($main_cats, $sub_cats, $boorus_u, $boorus_a, $mode);
		$page->set_title("Booru Manager");
		$page->add_block(new block(NULL, $html, "main", 5));
    }

    private function generate_manage($main_cats, $sub_cats, $boorus_u, $boorus_a, $mode) {

        $html = "";
        $headerB =  "
            <tr>
			<th>Weight</th>
			<th>Name</th>
			<th>URL</th>
            <th>Submitted by</th>
            <th>Date</th>
			<th>Category</th>
            <th>Action</th>
            </tr>";
		
        $headerC =  "
            <tr>
			<th>Weight</th>
			<th>Name</th>
			<th>Parent </th>
            <th>Visible</th>
            <th>Action</th>
            </tr>";

        $tableBU_rows = "";
		$tableBA_rows = "";
		$tableC_rows = "";
		
		/**
		 * Boorus
		 */
		if($mode == "boorus") {
			for ($i = 0 ; $i < count($boorus_u) ; $i++)
			{
				$id = $boorus_u[$i]['id'];
				$name = $boorus_u[$i]['booru_name'];
				$url = $boorus_u[$i]['booru_url'];
				$owner = User::by_id($boorus_u[$i]['owner_id']);
				$timestamp = $boorus_u[$i]['timestamp'];
				$cat = $boorus_u[$i]['cat_id'];
				$weight = $boorus_u[$i]['weight'];
	
				if(!$this->is_odd($i)) {$tr_class = "odd";}
				if($this->is_odd($i)) {$tr_class = "even";}
	
				$tableBU_rows .=
					"<tr class='{$tr_class}'>
					<td>$weight</td>
					<td>$name</td>
					<td><a href='$url'>$url</a></td>
					<td>{$owner->name}</td>
					<td>$timestamp</td>
					
					<td>
					<form name='approve$id' method='post' action='".make_link("menu/b_approve")."'>
					<input type='hidden' name='id' value='$id' />
					<input type='submit' style='width: 100%;' value='Approve' />
					</form>
					</td>
					<td>
					<form name='remove$id' method='post' action='".make_link("menu/b_remove")."'>
					<input type='hidden' name='id' value='$id' />
					<input type='submit' style='width: 100%;' value='Remove' />
					</form>
					</td>
					</tr>";
			}
	
			for ($i = 0 ; $i < count($boorus_a) ; $i++)
			{
	
				$id = $boorus_a[$i]['id'];
				$name = $boorus_a[$i]['booru_name'];
				$url = $boorus_a[$i]['booru_url'];
				$owner = User::by_id($boorus_a[$i]['owner_id']);
				$timestamp = $boorus_a[$i]['timestamp'];
				$cat = $boorus_a[$i]['cat_id'];
				$weight = $boorus_a[$i]['weight'];
				unset($cat_choices);
				$cat_choices = "";
				for($j=0;$j<count($main_cats);$j++) {
					if($cat == $main_cats[$j]['id']) {$selected = " selected='yes'";} else {$selected = "";}
					$cat_choices .= "<option value='".$main_cats[$j]['id']."' $selected>".$main_cats[$j]['cat_name']."</option>";
					for($k=0;$k<count($sub_cats);$k++) {
						if($sub_cats[$k]['cat_parent_id'] == $main_cats[$j]['id']) {
							$cat_option_name =  $main_cats[$j]['cat_name'] . "->" . $sub_cats[$k]['cat_name'];
							if($cat == $sub_cats[$k]['id']) {$selected = " selected='yes'";} else {$selected = "";}
							$cat_choices .= "<option value='".$sub_cats[$k]['id']."' $selected>".$cat_option_name."</option>";
						}
					}
				}
				$cat_choice_menu = "<select name='cat_id'>".$cat_choices."</select>";
	
				if(!$this->is_odd($i)) {$tr_class = "odd";}
				if($this->is_odd($i)) {$tr_class = "even";}
	
				$tableBA_rows .=
					"<tr class='{$tr_class}'>
					
					<form name='change$id' method='post' action='".make_link("menu/b_change")."'>
					<td><input type='text' style='width:20px;' name='weight' value='$weight' /></td>
					<td><input type='text' name='booru_name' value='$name' /></td>
					<td><input type='text' name='booru_url' value='$url' /></td>
					<td>{$owner->name}</td>
					<td>$timestamp</td>
					<td>$cat_choice_menu</td>
					
					<td>
					<input type='hidden' name='id' value='$id' />
					<input type='submit' style='width: 100%;' value='Change' />
					</form>
					
					<form name='disapprove$id' method='post' action='".make_link("menu/b_disapprove")."'>
					<input type='hidden' name='id' value='$id' />
					<input type='submit' style='width: 100%;' value='Disapprove' />
					</form>
					
					<form name='remove$id' method='post' action='".make_link("menu/b_remove")."'>
					<input type='hidden' name='id' value='$id' />
					<input type='submit' style='width: 100%;' value='Remove' />
					</form>
					
	
					</td>
					</tr>";
			}
		} else {
			/**
			 * Categories
			 */
			for ($i = 0 ; $i < count($main_cats) ; $i++)
			{
				$id = $main_cats[$i]['id'];
				$name = $main_cats[$i]['cat_name'];
				$parent_id = $main_cats[$i]['cat_parent_id'];
				$visible = $main_cats[$i]['visible'];
				$weight = $main_cats[$i]['weight'];
	
				if($visible == "Y") {$checked = "checked";} else {$checked = "";}
	
				if(!$this->is_odd($i)) {$tr_class = "odd";}
				if($this->is_odd($i)) {$tr_class = "even";}

				$tableC_rows .=
					"<tr class='{$tr_class}'>
					<form name='change$id' method='post' action='".make_link("menu/c_change")."'>
					<td><input type='text' style='width:20px;' name='weight' value='$weight' /></td>
					<td><input type='text' name='cat_name' value='$name' /></td>
					<td><input type='hidden' name='cat_parent_id' value='0' /> Parent Category</td>
					<td><input type='checkbox' name='visible' value='Y' $checked></td>
					
					<td>
					<input type='hidden' name='id' value='$id' />
					<input type='submit' style='width: 100%;' value='Change' />
					</form>
					
					<form name='remove$id' method='post' action='".make_link("menu/c_remove")."'>
					<input type='hidden' name='id' value='$id' />
					<input type='submit' style='width: 100%;' value='Remove' />
					</form>
					</td>
					</tr>";
				
				$tableS_rows = "";
				for($j=0;$j<count($sub_cats);$j++) {
					if(!$this->is_odd($j)) {$tr_class = "odd";}
					if($this->is_odd($j)) {$tr_class = "even";}
					if($sub_cats[$j]['cat_parent_id'] == $id) {
						$jd = $sub_cats[$j]['id'];
						$name = $sub_cats[$j]['cat_name'];
						$parent_id = $sub_cats[$j]['cat_parent_id'];
						$visible = $sub_cats[$j]['visible'];
						$weight = $sub_cats[$j]['weight'];
			
						if($visible == "Y") {$checked = "checked";} else {$checked = "";}
						
						$cat_choices = "";
						if($parent_id == "") { $selected = "selected='yes'"; } else { $selected = ""; }
						$cat_choices .= "<option value='' $selected>No Parent</option>";
						for($k=0;$k<count($main_cats);$k++) {
							if($parent_id == $main_cats[$k]['id']) {$selected = " selected='yes'";} else {$selected = "";}
							$cat_choices .= "<option value='".$main_cats[$k]['id']."' $selected>".$main_cats[$k]['cat_name']."</option>";
						}
						$cat_choice_menu = "<select name='parent_id'>".$cat_choices."</select>";

						$tableS_rows .= "
							<tr class='{$tr_class}'>
							<form name='change$id' method='post' action='".make_link("menu/c_change")."'>
							<td><input type='text' style='width:20px;' name='weight' value='$weight' /></td>
							<td><input type='text' name='cat_name' value='$name' /></td>
							<td>$cat_choice_menu</td>
							<td><input type='checkbox' name='visible' value='Y' $checked></td>
							
							<td>
							<input type='hidden' name='id' value='$id' />
							<input type='submit' style='width: 100%;' value='Change' />
							</form>
							
							<form name='remove$id' method='post' action='".make_link("menu/c_remove")."'>
							<input type='hidden' name='id' value='$id' />
							<input type='submit' style='width: 100%;' value='Remove' />
							</form>
							</td>
							</tr>
						";
					}
				}
				if($tableS_rows != "") {
					$tableS =  "<table id='c_subcats$id' class='zebra' align='right' style='left:50px;'>
									$tableS_rows
								</table>";
					$tableC_rows .= "<tr><td colspan='5'>$tableS</td></tr>";
				}
			}
			$cat_choices = "";
			$cat_choices .= "<option value='' selected='yes'>No Parent</option>";
			for($j=0;$j<count($main_cats);$j++) {
				$cat_choices .= "<option value='".$main_cats[$j]['id']."'>".$main_cats[$j]['cat_name']."</option>";
			}
			$cat_choice_menu = "<select name='parent_id'>".$cat_choices."</select>";
			$addC = "<tr><td colspan='5'>Add New Category:</td></tr>
				<form name='add' method='post' action='".make_link("menu/c_add")."'>
				<tr>
					<td><input type='text' style='width:20px;' name='weight' value='50' /></td>
					<td><input type='text' name='cat_name' /></td>
					<td>$cat_choice_menu</td>
					<td><input type='checkbox' name='visible' value='Y' checked></td>
					<td><input type='submit' style='width: 100%;' value='Add' /></td>
				</tr>
				</form>
			";
		}
		if($mode == "boorus") { $boorus = "
				<h1>Boorus</h1>
				<h2>Awaiting Approval:</h2>
                <table id='b_unapproved' class='zebra'>
                <thead>$headerB</thead>
                <tbody>$tableBU_rows</tbody>
                </table>
                <br />

				<h2>Approved:</h2>
                <table id='b_approved' class='zebra'>
                <thead>$headerB</thead>
                <tbody>$tableBA_rows</tbody>
                </table>
                <br />
		"; } else { $boorus = ""; }
		if($mode == "cats") {
				 $cats = "
				<h1>Categories</h1>
                <table id='categories' class='zebra'>
                <thead>$headerC</thead>
                <tbody>$tableC_rows</tbody>
				<tfoot>$addC</tfoot>
                </table>
				<br />
				<br />
		"; } else { $cats = ""; }
        $html = "$cats
				$boorus

                <br />
                <b>Help:</b><br />
                <blockquote>All functions related to booru moderation can be performed on this page. <b>Categories</b> can be added and removed at will, and boorus can be assigned to categories. <b>Unapproved</b> boorus are basically in a moderation queue, and won't be shown unless they are <b>approved</b>, at which time you can change the title, url, and favicon URLs and finally assign the booru to a category to complete the process.<br /><br /><b>Heavier</b> categories will sink to the bottom of the menu.</blockquote>
				";

        return $html;
    }
    
	private function is_odd($number) {
		return $number & 1; // 0 = even, 1 = odd
    }

	private function get_domain($Address) { 
	   $parseUrl = parse_url(trim($Address)); 
	   return trim($parseUrl['host'] ? $parseUrl['host'] : array_shift(explode('/', $parseUrl['path'], 2))); 
	}
	
	/**
	 * Submit Page
	 */
	public function show_submit($thanks, $main_cats, $sub_cats) {
		global $page;
		if($thanks == false) {$html = $this->generate_submit($main_cats, $sub_cats);} else {$html = $this->generate_thanks();}
		$page->set_title("Submit a booru");
		$page->add_block(new block(NULL, $html, "main", 5));
	}
	
	private function generate_submit($main_cats, $sub_cats) {
		global $user;
		
		$cat_choices = "";
		for($j=0;$j<count($main_cats);$j++) {
			$cat_choices .= "<option value='".$main_cats[$j]['id']."'>".$main_cats[$j]['cat_name']."</option>";
			for($k=0;$k<count($sub_cats);$k++) {
				if($sub_cats[$k]['cat_parent_id'] == $main_cats[$j]['id']) {
					$cat_option_name =  $main_cats[$j]['cat_name'] . "->" . $sub_cats[$k]['cat_name'];
					$cat_choices .= "<option value='".$sub_cats[$k]['id']."'>".$cat_option_name."</option>";
				}
			}
		}
		$cat_choice_menu = "<select name='cat_id'>".$cat_choices."</select>";
		
		$html = "<h2>Submit a booru!</h2>";
		$html .= "<table class='zebra' id='b_submit'>
					<form name='submit_booru' method='post' action='".make_link("menu/b_add")."'>
					<tr><td>Booru Name:</td><td><input type='text' name='booru_name' value='' /></td></tr>
					<tr><td>Booru URL:</td><td><input type='text' name='booru_url' value='' /></td></tr>
					<tr><td>Category (or best fit):</td><td>$cat_choice_menu</td></tr>";
		if($user->is_admin()) { $html .= "<td>Admin Control: <input type='checkbox' name='approved' value='Y' checked> Approved</td>"; }
		$html .= "<td><input type='submit' value='Submit for review' /></td>
					</form>
					</table>";
		return $html;
	}
	
	private function generate_thanks() {
		global $user;
		$html = "<h2>Thanks!</h2>
				Your submission is being reviewed. To prevent spam, you may only submit 3 boorus for approval at a time. If approved, your submissions will appear in the side navigation bar and you'll be able to submit more!";
		return $html;
	}
	
	/**
	 * Disclaimer Page
	 */
	public function show_disclaimer() {
		global $page;
		$html = "<html><head><title>Disclaimer</title>
		<link rel='stylesheet' href='/ext/overbooru_cats/style.css' type='text/css'>
		<script src='/lib/jquery-1.3.2.min.js' type='text/javascript'></script> 
		<script src='/lib/jquery.auto-complete.pack.js' type='text/javascript'></script> 
		<script src='/lib/jquery.cookie.js' type='text/javascript'></script> 
		<script src='/lib/jquery.form-defaults.js' type='text/javascript'></script> 
		<script src='/lib/jquery.tablesorter.min.js' type='text/javascript'></script> 
		</head>
		<body>
			<br /><br /><br />
			<b>The Overbooru</b>
			<p style='font-size:0.7em;'> 
			<b>Disclaimer</b> 
			<br> 
			 
			This page is merely an index of other sites. The Overbooru is not
			responsible for and does not host any images found here. Please report
			illegal files to the admin of the respective board.<br> 
			<br> 
			<a href='".make_link('menu/index')."'>(continue)</a> 
			 
			</p>
		</body>
		</html>
		";
		$page->set_mode("data");
		$page->set_data($html);
	}
}