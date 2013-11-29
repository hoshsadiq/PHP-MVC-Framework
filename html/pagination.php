<div class="pagination pagination-centered">
    <ul>
        <? foreach ($navigation as $page): ?>
            <li class="<?= $page->classes ?>"><a href="<?= $page->url ?>"><?= $page->page ?></a></li>
        <? endforeach ?>
    </ul>
</div>