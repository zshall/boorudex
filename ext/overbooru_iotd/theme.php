<?php
class OverbooruIOTDTheme extends Themelet {
    public function show_manage($images_u, $images_a) {
        global $page;
        $html = $this->get_html_for_iotd_editor($images_u, $images_a);
        $page->set_title("IOTD Manager");
        $page->set_heading("IOTD Manager");
        $page->add_block(new Block("Welcome to the IOTD Manager!", $html, "main", 10));
        $page->add_block(new Block("Navigation", "<a href='".make_link()."'>Index</a>", "left", 0));
    }
    public function show_index($image, $page_number, $total_pages, $extension) {
        global $page, $config;
        /**
         * Displaying the page:
         */
		if($total_pages == 0) {
			$page->set_title("No images");
			$page->set_heading("No images");
			$page->add_block(new Block("No images", "No images to display. Come back later!", "main", 10));
		} else {
        	$title = "Overbooru IOTD";
        
        	$page->set_title($title);
        	$page->set_heading($title);
        	$this->generate_index($image);
        	$this->display_paginator($page, "iotd/index", null, $page_number, $total_pages);
		}
    }
    public function show_submit($thanks) {
        global $page;
        /**
         * Displaying the page:
         */
        
        $title = "Overbooru IOTD - Submit";
        if($thanks == true) { $html = $this->generate_thanks(); } else { $html = $this->generate_submit(); }
        $page->set_title($title);
        $page->set_heading($title);
        $page->add_block(new block(NULL, $html, "main", 5));
    }
	private function generate_submit() {
		global $user;
		$html = "<h2>Submit an IOTD!</h2>";
		$html .= "<table class='zebra' id='b_submit'>
					<form name='submit_iotd' method='post' action='".make_link("iotd/add")."'>
					<tr><td>Image source:</td><td><input type='text' name='image_source' value='' /></td></tr>
					<tr>";
		if($user->is_admin()) { $html .= "<td>Admin Control: Mirror URL</td><td><input type='text' name='image_url' value='' /></td></tr><tr><td><input type='checkbox' name='approved' value='Y' checked> Approved</td>"; }
		$html .= "<td><input type='submit' value='Submit for review' /></td></tr>
					</form>
					</table>";
		return $html;
	}
	private function generate_thanks() {
		global $user;
		$html = "<h2>Thanks!</h2>
				Your submissions are being reviewed. To prevent spam, your first 3 images must be approved before this limit is removed. If approved, your images will appear in the Images of the Day index and you'll be verified as a trusted contributor!";
		return $html;
	}
    private function is_odd($number) {
            return $number & 1; // 0 = even, 1 = odd
    }
    private function get_html_for_iotd_editor($images_u, $images_a) {
        /**
         * Long function name, but at least I won't confuse it with something else ^_^
         */

        $html = "";
        $submission_header =  "
            <tr>
            <th>Submitted by</th>
            <th>Date</th>
            <th>Image</th>
            <th>Action</th>
            </tr>";
			
        $approved_header =  "
            <tr>
            <th>Submitted by</th>
            <th>Date</th>
            <th>Source Link</th>
			<th>Direct Image URL</th>
            <th>Action</th>
            </tr>";

        $table1_rows = "";
		$table2_rows = "";
        for ($i = 0 ; $i < count($images_u) ; $i++)
        {
            /**
             * Add table rows
             */
            $id = $images_u[$i]['id'];
            $owner = User::by_id($images_u[$i]['owner_id']);
            $timestamp = $images_u[$i]['timestamp'];
			//$image_url = $images_u[$i]['image_url'];
            $image_source = html_escape($images_u[$i]['image_source']);

            if(!$this->is_odd($i)) {$tr_class = "odd";}
            if($this->is_odd($i)) {$tr_class = "even";}
            // Add the new table row(s)
            $table1_rows .=
                "<tr class='{$tr_class}'>
                <td>{$owner->name}</td>
                <td>$timestamp</td>
                <td><a href='$image_source'>Link</a></td>

				<td><form name='approve$id' method='post' action='".make_link("iotd/approve")."'>
                <input type='hidden' name='id' value='$id' />
                <input type='submit' style='width: 100%;' value='Approve' />
                </form></td>
				
                <td><form name='remove$id' method='post' action='".make_link("iotd/remove")."'>
                <input type='hidden' name='id' value='$id' />
                <input type='submit' style='width: 100%;' value='Remove' />
                </form>
				

                </td>
                </tr>";
        }

        for ($i = 0 ; $i < count($images_a) ; $i++)
        {
            /**
             * Add table rows
             */
            $id = $images_a[$i]['id'];
            $owner = User::by_id($images_a[$i]['owner_id']);
            $timestamp = $images_a[$i]['timestamp'];
			$image_url = $images_a[$i]['image_url'];
            $image_source = html_escape($images_a[$i]['image_source']);

            if(!$this->is_odd($i)) {$tr_class = "odd";}
            if($this->is_odd($i)) {$tr_class = "even";}
            // Add the new table row(s)
            $table2_rows .=
                "<tr class='{$tr_class}'>
                <td>{$owner->name}</td>
                <td>$timestamp</td>
                <td><a href='$image_source'>Link</a></td>
				<form name='change$id' method='post' action='".make_link("iotd/change")."'>
				<td><input type='hidden' name='id' value='$id' />
                <input type='text' name='image_url' style='width: 100%;' value='$image_url' />
				
                <td><input type='submit' style='width: 100%;' value='Change Link' /></form>
				
				<form name='approve$id' method='post' action='".make_link("iotd/disapprove")."'>
                <input type='hidden' name='id' value='$id' />
                <input type='submit' style='width: 100%;' value='Disapprove' />
                </form>
				
				<form name='remove$id' method='post' action='".make_link("iotd/remove")."'>
                <input type='hidden' name='id' value='$id' />
                <input type='submit' style='width: 100%;' value='Remove' />
                </form>
				

                </td>
                </tr>";
        }


        $html = "<h2>Awaiting Approval:</h2>
                <table id='iotd_unapproved' class='zebra'>
                <thead>$submission_header</thead>
                <tbody>$table1_rows</tbody>
                </table>
                <br />

				<h2>Approved:</h2>
                <table id='iotd_approved' class='zebra'>
                <thead>$approved_header</thead>
                <tbody>$table2_rows</tbody>
                </table>
                <br />

                <br />
                <b>Help:</b><br />
                <blockquote>Change and remove IOTD entries on this page!</blockquote>";

        return $html;
    }
    private function generate_image($id, $user, $timestamp, $image_url, $image_source) {
            global $page, $config;
            $clean_date = date("m/d/y", strtotime($timestamp));
            
		   $body = "<div class='iotd-body'><span style='font-size:1.1em; font-weight:bold;'>Random Image of the Day</span><br /><br />
		   		<a href='$image_source'><img src='$image_url'/></a><br /><br />
                <span class='iotd-header'>Submitted by {$user->name} on $clean_date<br /><br />[<a href='".make_link('rss/iotd')."'>RSS Feed</a>] [<a href='".make_link('iotd/submit')."'>Suggest an image</a>]</span>
                </div>
            ";
            
            $page->add_block(new Block(NULL, $body, "main", 5));
    }
    private function generate_index($image) {
        global $page, $config;
            /**
             * Show image
             */
			$user = User::by_id($image['owner_id']);
            $this->generate_image($image['id'],
								 $user,
								 $image['timestamp'],
								 $image['image_url'],
								 $image['image_source']);
    }
}
?>