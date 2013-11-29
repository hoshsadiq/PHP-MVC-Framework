<form action="<?= url('user/register') ?>" method="post" class="login">
    <label><? _e('Username:') ?>
        <div><input type="text" name="username" placeholder="<? _e('username') ?>"
                    value="<?= html(_get('username')) ?>"/></div>
    </label>
    <label><? _e('E-mail:') ?>
        <div><input type="text" name="email" placeholder="john@anonymous.com" value="<?= html(_get('email')) ?>"/></div>
    </label>
    <label><? _e('Password:') ?>
        <div><input type="password" name="password" placeholder="********" value="<?= html(_get('password')) ?>"/></div>
    </label>
    <label><? _e('Confirm Password:') ?>
        <div><input type="password" name="passwordc" placeholder="********" value="<?= html(_get('passwordc')) ?>"/>
        </div>
    </label>

    <div>
        <button type="submit" class="btn btn-large btn-primary"><? _e('Register') ?> <i class="icon-signin"></i>
        </button>
        <div class="or"><? _e('or') ?></div>
        <div class="fb-login-button"><? _e('Register with Facebook') ?></div>
    </div>

    <div class="registration"><a href="<?= url('user/login') ?>"><? _e('Already have an account?') ?></a></div>
</form>