<?php

class UserController extends Controller
{
    public function page_index()
    {
        $this->page_login();
    }

    public function page_register()
    {
        if (Maker::posted() && User::register(
                $this->post->username,
                $this->post->password,
                $this->post->passwordc,
                $this->post->email
            )
        ) {
            Session::message(__('You were successfully registered, please confirm your email.'));
            Maker::redirect(ABSURL);
        } else {
            Session::message(__('Debug: Register form not submitted'));
        }
        $this->set_template('user/register');
        $this->title = __('Register');
    }

    public function page_login()
    {
        if (Maker::posted() && User::login($this->post->username, $this->post->password)) {
            Session::message(__('You were successfully logged in.'));
            Maker::redirect(ABSURL);
        } else {
            Session::message(__('Debug: Login form not submitted'));
        }
        $this->set_template('ulogin');
        $this->title = __('Login');
    }

    public function page_logout()
    {
        Session::delcookies();
        Session::message('You\'re logged out');

        redirect(url('index/index'));
        exit;
    }
}