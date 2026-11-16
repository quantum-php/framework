<div class="main-wrapper">
    <h1 class="center-align teal-text"><?php _t('posts.posts') ?></h1>
    <div class="row post_container">
        <?php if (count($posts)): ?>
            <?php foreach ($posts as $post): ?>
                <?php echo partial('post/partials/post-item', ['post' => $post]) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php if (empty($posts)): ?>
        <h4 class="center-align"><?php _t('posts.no_posts') ?>... <?php _t('posts.try_creating') ?></h4>
    <?php endif; ?>

    <?php if (!empty($pagination)): ?>
        <div class="center-align">
	        <?php echo $pagination->getPagination(false, 5) ?>
        </div>
    <?php endif; ?>
    <?php echo partial('post/partials/modal', ['item' => t('posts.post')]) ?>
</div>
