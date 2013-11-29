<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="author" content="Simon Stackevicius (si9.co), Hosh Sadiq (hosh.info)">
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <base href="<?= $base ?>/">
    <link rel="icon" href="favicon.ico">

    <link href="css/bootstrap.css" rel="stylesheet" type="text/css"/>
    <link href="css/font-awesome.css" rel="stylesheet" type="text/css"/>
    <link href="css/fonts.css" rel="stylesheet" type="text/css"/>
    <link href="css/styles.css" rel="stylesheet" type="text/css"/>

    <script type="text/javascript" src="js/jquery-1.7.2.min.js"></script>
    <script type="text/javascript" src="js/jquery-ui-1.8.20.custom.min.js"></script>
    <script type="text/javascript" src="js/jquery.easing.1.3.js"></script>
    <script type="text/javascript" src="js/jquery.hoverIntent.minified.js"></script>
    <script type="text/javascript" src="js/jquery.masonry.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.js"></script>
    <script type="text/javascript" src="js/common.js"></script>
    <? $this->do_head() ?>

</head>

<body>
<div id="fb-root"></div>

<div class="modal hide fade" id="testModal">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Login</h3>
    </div>
    <div class="modal-body">
        <div>
            <input type="text" placeholder="Enter your email"/>
        </div>
        <div>
            <input type="password" placeholder="Enter your password"/>
        </div>
    </div>
    <div class="modal-footer">
        <a href="#" class="btn" data-dismiss="modal">Close</a>
        <a href="#" class="btn btn-large btn-primary">Log me in</a>
    </div>
</div>

<div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container">
            <a class="brand" href="#"><span>over</span>hear<span class="in">.in<sup>/Canterbury</sup></span></a>
            <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>

            <div class="btn-group pull-right">
                <? if ($user): ?>

                    <a class="btn btn-primary dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="icon-user"></i> <?= $username ?>
                        <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="#">Profile</a></li>
                        <li class="divider"></li>
                        <li><a href="<?= url('user/logout') ?>">Sign Out</a></li>
                    </ul>

                <? else: ?>

                    <button class="btn btn-primary quicklogin-link">
                        <i class="icon-user"></i> Login
                    </button>
                    <button class="btn btn-primary quickregister-link">
                        Register
                    </button>

                <? endif; ?>
            </div>

            <form class="" id="quicklogin" method="post" action="<?= url('user/login') ?>">
                <div class="input-prepend">
                    <span class="add-on"><i class="icon-envelope"></i></span><input type="text" name="username"
                                                                                    class="input-medium"
                                                                                    placeholder="Email"/>
                </div>

                <div class="input-prepend">
                    <span class="add-on"><i class="icon-asterisk"></i></span><input type="password" name="password"
                                                                                    class="input-medium"
                                                                                    placeholder="Password"/>
                </div>

                <label class="checkbox">
                    <input type="checkbox" checked="checked"/> Remember me
                </label>
                <button type="submit" class="btn">Sign in <i class="icon-signin"></i></button>
            </form>

            <form class="" id="quickregister" method="post" action="<?= url('user/register') ?>">
                <div class="input-prepend">
                    <span class="add-on"><i class="icon-user"></i></span><input type="text" name="username"
                                                                                class="input-medium"
                                                                                placeholder="Username"/>
                </div>

                <div class="input-prepend">
                    <span class="add-on"><i class="icon-envelope"></i></span><input type="text" name="email"
                                                                                    class="input-medium"
                                                                                    placeholder="Email"/>
                </div>

                <div class="input-prepend">
                    <span class="add-on"><i class="icon-asterisk"></i></span><input type="password" name="passwordass"
                                                                                    class="input-medium"
                                                                                    placeholder="Password"/>
                </div>

                <div class="input-prepend">
                    <span class="add-on"><i class="icon-asterisk"></i></span><input type="password" name="passwordc"
                                                                                    class="input-medium"
                                                                                    placeholder="Confirm Password"/>
                </div>

                <label class="checkbox">
                    <input type="checkbox" checked="checked"/> Remember me
                </label>
                <button type="submit" class="btn">Sign in <i class="icon-signin"></i></button>
            </form>

            <div class="nav-collapse">
                <ul class="nav">
                    <li class="active"><a href="#"><i class="icon-home"></i> Latest</a></li>
                    <li><a href="#about"><i class="icon-map-marker"></i> Locations</a></li>
                    <li><a href="#about"><i class="icon-thumbs-up"></i> Top Rated</a></li>
                </ul>
                <form class="navbar-search pull-left" action="<?= url('content/search') ?>">
                    <input type="text" class="search-query span2" placeholder="Search..."/>
                </form>
            </div>
            <!--.nav-collapse -->
        </div>
        <!--.container -->
    </div>
</div>

<div class="container">
    <img src="img/logopic.png" class="logopic"/>

    <? //var_dump($errors) ?>
    <? //var_dump($messages) ?>
    <? if ($errors || $messages): ?>
        <div id="messages">
            <? if ($errors): ?>
                <div class="alert alert-error">
                    <button class="close" data-dismiss="alert">×</button>
                    <? foreach ($errors as $error): ?>
                        <div><?= $error ?></div>
                    <? endforeach; ?>
                </div>
            <? endif ?>
            <? if ($messages): ?>
                <div class="alert alert-success">
                    <button class="close" data-dismiss="alert">×</button>
                    <? foreach ($messages as $message): ?>
                        <div><i class="icon-ok-sign"></i> <?= $message ?></div>
                    <? endforeach; ?>
                </div>
            <? endif ?>
        </div>
    <? endif ?>
