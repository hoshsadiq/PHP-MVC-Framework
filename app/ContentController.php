<?php

class ContentController extends Controller
{
    public function page_submit_xhr()
    {
        //xdebug_disable();
        $content = trim(_get('content'));
        $location = trim(_get('location'));

        Akismet::init();

        if (strlen($content) < 2) {
            $response = __('Too short');
        } elseif (strlen($content) > 2000) {
            $response = __('It\'s too long... that\'s what she said!');
        } elseif (!Akismet::content($content, 'post')) {
            exit(json_encode(
                array('error' => __('Your post was flagged as spam. It has not been posted. Please try again.'))
            ));
        } else {
            // @todo replace non markup html with nothing, also remove unsafe js and xss shiz
            //$content = strip_tags($content, '<a><b><strong>');

            $response = Mysql::query(
                'INSERT INTO posts (user_id, time, ip, content, location) VALUES (%u, UNIX_TIMESTAMP(), %s, %s, %s)',
                Session::instance()->userid,
                inet_pton($_SERVER['REMOTE_ADDR']),
                $content,
                $location
            )->insert_id;
        }

        echo json_encode($response);
    }

    public function page_getpost_xhr()
    {
        $post = Mysql::query(
            'SELECT u.name, p.id, p.content, p.location, p.time, p.user_id
                                          FROM posts AS p
                                          JOIN users AS u ON p.user_id = u.id
                                          WHERE p.id = %u
                                          LIMIT 1',
            _get('id')
        )->row();

        $body = $this->view->contents(
            'post_single',
            array(
                'post' => $post
            )
        );
        echo json_encode($body);
    }

    public function page_vote()
    {
        var_dump($this->get, $this->post);
        exit;
        //echo json_encode($body->get_contents());
    }
}