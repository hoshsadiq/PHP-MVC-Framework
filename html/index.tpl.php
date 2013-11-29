<? if (user_can('post')): ?>
    <form action="<?= url('content/submit') ?>" method="post" class="submit-overhear hero-unit">
        <h1>What have you overheard?</h1>

        <div>
            <textarea name="text" placeholder="Something worth sharing?" class="tinymce"><?= html(
                    _get('text')
                ) ?></textarea>
        </div>

        <div class="controls">
            <div class="input-prepend">
                <span class="add-on"><i class="icon-map-marker"></i></span><input class="span2" name="location"
                                                                                  id="inputIcon" type="text"
                                                                                  placeholder="Where did this happen? e.g. Canterbury">
            </div>
        </div>

        <button type="submit" data-loading-text="Sharing..." class="btn btn-primary btn-large fl">Share <i
                class="icon-ok-sign"></i></button>
        <div id="share-error" class="alert alert-error fl">
            <b>Oh snap!</b> Something went wrong
        </div>
        <br class="clear"/>
    </form>
<? endif ?>

    <div class="posts row">
        <? $half = ceil(count($latest_posts) / 2) ?>
        <div class="span6 posts-container left">
            <? foreach ($latest_posts as $j => $post): $i = $j + 1 ?>
            <? include('post_single.tpl.php') ?>
            <? if ($i == $half): ?>
        </div>
        <div class="span6 posts-container right">
            <? endif ?>
            <? endforeach ?>
        </div>
    </div><!-- /.row -->

<? include('pagination.php') ?>