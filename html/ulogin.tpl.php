<form action="<?= url('user/login') ?>" method="post" class="login">
    <label><? _e('E-mail:') ?>
        <div><input type="text" name="username" placeholder="john@anonymous.com" value="<?= html(_get('username')) ?>"/>
        </div>
    </label>
    <label><? _e('Password:') ?>
        <div><input type="password" name="password" placeholder="********" value="<?= html(_get('password')) ?>"/></div>
    </label>

    <div>
        <button type="submit" class="btn btn-large btn-primary"><? _e('Login') ?> <i class="icon-signin"></i></button>
        <div class="or"><? _e('or') ?></div>
        <div class="fb-login-button"><? _e('Login with Facebook') ?></div>
    </div>

    <div class="registration"><a href="#"><? _e('Looking to register?') ?></a></div>
</form>