<?php
class OverbooruIOTDTheme extends Themelet {
    public function display_editor($posts) {
        global $page;
        $html = $this->get_html_for_iotd_editor($posts);
        $page->set_title("Blog Manager");
        $page->set_heading("Blog Manager");
        $page->add_block(new Block("Welcome to the Blog Manager!", $html, "main", 10));
        $page->add_block(new Block("Navigation", "<a href='".make_link()."'>Index</a>", "left", 0));
    }
    public function show_index($image, $page_number, $total_pages, $extension) {
        global $page, $config;
		$ename = $extension["name"];
        /**
         * Displaying the page:
         */
        
        $title = $extension["title"];
        
        $page->set_title($title);
        $page->set_heading($title);
        $this->generate_index($image);
        $this->display_paginator($page, "iotd/index", null, $page_number, $total_pages);
    }
    public function display_blog_post($post, $extension) {
        global $page, $config;
        /**
         * Displaying the blog:
         */
        $ename = $extension["name"];
		$title = $extension["title"];
        
        $page->set_title($title);
        $page->set_heading($title);
        $this->generate_blog_header("<a href='".make_link("$ename/list")."'>Index</a><br /><a href='".make_link()."'>&#171; Home</a>");
        
        $this->generate_blog_post($post['id'],
                                 $post['owner_id'],
                                 $post['post_date'],
                                 $post['post_title'],
                                 $post['post_text'],
                                 0, $extension);
        
        send_event(new BlogBuildingEvent()); // make it extendable.
    }
    private function is_odd($number) {
            return $number & 1; // 0 = even, 1 = odd
    }
    private function get_html_for_blog_editor($posts) {
        /**
         * Long function name, but at least I won't confuse it with something else ^_^
         */

        $html = "";
        $table_header =  "
            <tr>
            <th>Author</th>
            <th>Date</th>
            <th>Title</th>
            <th>Action</th>
            </tr>";
        $add_new = "
            <form action='".make_link("blog_manager/add")."' method='POST'>
            <table class='zebra'>
            <tr class='odd'><td style='width: 30px;'>Title</td><td><input type='text' name='post_title' maxlength='120' /></td></tr>
            <tr class='even'>
            <td colspan='2'><textarea style='text-align:left;' name='post_text' rows='5' /></textarea></td>
            </tr><tr class='odd'>
            <td><input type='submit' value='Add'></td>
            </tr>
            </table>
            </form>";

        // Posts list
        $table_rows = "";
        for ($i = 0 ; $i < count($posts) ; $i++)
        {
            /**
             * Add table rows
             */
            $id = $posts[$i]['id'];
            $post_author = User::by_id($posts[$i]['owner_id']);
            $post_date = $posts[$i]['post_date'];
            $post_title = $posts[$i]['post_title'];

            if(!$this->is_odd($i)) {$tr_class = "odd";}
            if($this->is_odd($i)) {$tr_class = "even";}
            // Add the new table row(s)
            $table_rows .=
                "<tr class='{$tr_class}'>
                <td>{$post_author->name}</td>
                <td>$post_date</td>
                <td>$post_title</td>

                <td><form name='remove$id' method='post' action='".make_link("blog_manager/remove")."'>
                <input type='hidden' name='id' value='$id' />
                <input type='submit' style='width: 100%;' value='Remove' />
                </form>
                </td>
                </tr>";
        }
        $html = "
                <table id='blog_entries' class='zebra'>
                <thead>$table_header</thead>
                <tbody>$table_rows</tbody>
                </table>
                <br />

                $add_new

                <br />
                <b>Help:</b><br />
                <blockquote>Add and remove blog entries on this page!</blockquote>";

        return $html;
    }
    private function generate_image($id, $user, $timestamp, $image_url, $image_source) {
            global $page, $config;
            $clean_date = date("m/d/y", strtotime($timestamp));
            
           $permalink = "<a href='".make_link("iotd/view/$id")."'>#</a> ";
		   $body = "<div class='iotd-body'>
		   		<a href='$image_source'><img src='$image_url'/></a>
                <span class='iotd-header'>".$permalink."Submitted by {$post_author->name} on $clean_date<br /><br /></span>
                </div>
            ";
            
            $page->add_block(new Block($post_title, $body, "main", 5));
    }
    private function generate_blog_index($image, $extension) {
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
									 
        $page->add_block(new Block(NULL,$config->get_string("iotd_news"), "main", 0));
    }
}
?>