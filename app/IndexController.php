<?php

/**
 * @todo default user city by ip/geolocation
 */

class IndexController extends Controller
{
    public function page_index()
    {
        $location = $this->get->location;
        if (empty($location)) {
            Session::message('Redirected to default location');
            // default user location if not specified
            redirect(url('index/index', ['location' => 'Canterbury']));
            die;
        }
        // For later!
        //Event::register('footer', function() {
        //    echo '<script type="text/javascript" src="'.ABSURL.'/js/tinymce/jquery.tinymce.js"></script>';
        //});
        $this->set_template('index');
        $this->title = __('Overheard in %s', $location);

        // if want, can implement $this->vars(); which will behave like View::vars();
        $this->location = $location;

        $max = 5;
        $page = (isset($this->query->page)) ? $this->query->page - 1 : 0;
        $start = $max * $page;
        $end = $max;

        $total = Mysql::query('SELECT count(*) FROM posts WHERE posts.location=%s', $location)->var;
        $nav = pagination(ceil($total / $max), $page, 'index/index', ['location' => $location]);

        $this->navigation = $nav;
        $this->latest_posts = Mysql::query(
            'SELECT posts.id, posts.user_id, posts.content, posts.time, users.name, posts.location
                                                        FROM posts
                                                        LEFT JOIN users
                                                        ON posts.user_id = users.id
                                                        WHERE posts.location=%s
                                                        ORDER BY posts.time DESC
                                                        LIMIT %u, %u',
            $location,
            $start,
            $end
        )->results;
    }
}