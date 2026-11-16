<div class="comments-section">
    <h3><?php _t('comments.title') ?></h3>

    <?php if (!empty($comments)): ?>
        <ul class="comments-list">
            <?php foreach ($comments as $comment): ?>
                <?php echo partial('post/partials/comment-item', ['comment' => $comment]) ?>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p><?php _t('comments.no_comments') ?></p>
    <?php endif; ?>

    <?php if (auth()->check()): ?>
        <?php echo partial('post/partials/comment-form', ['post' => $post]) ?>
    <?php else: ?>
        <div class="alert card">
            <div class="card-content comment-info">
                <i class="material-icons">report_problem</i>
                <span><?php _t('comments.login_to_comment') ?></span>
            </div>
        </div>
    <?php endif; ?>
</div>
