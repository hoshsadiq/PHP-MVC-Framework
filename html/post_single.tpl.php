<div class="span6 post bg-<?= $i ?>" id="post-<?= $post->id ?>">
    <div class="new"><i class="icon-pushpin"></i></div>
    <div class="top">
        <span class="original-poster"><i class="icon-user"></i> <?= $post->name ?></span>
        <? if (user_can('vote')): ?>
            <div class="btn-group fr rate">
                <button class="btn up"><i class="icon-thumbs-up"></i></button>
                <button class="btn down"><i class="icon-thumbs-down"></i></button>
            </div>
        <? endif ?>
    </div>
    <div class="inner">
        <p><?= html($post->content) ?></p>

        <div class="details">
            <a href="#" class="location label label-info"><i class="icon-map-marker"></i> <?= $post->location ?></a>
            <time datetime="<?= date('Y-m-d H:i:s', $post->time) ?>"
                  title="<?= date('d M Y @ H:i:s', $post->time) ?>"><?= when($post->time) ?></time>

        </div>

    </div>
    <div class="comments">
        <div class="fb-comments" data-href="http://www.universetoday.com" data-num-posts="3" data-width="410">
            loading
        </div>
    </div>
    <button class="show-comments"><i class="icon-comments"></i> Show 8 comments...</button>
</div>