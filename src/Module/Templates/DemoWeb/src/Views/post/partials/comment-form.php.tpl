<?php if (session()->has('error')) : ?>
    <?php echo partial('partials/messages/error') ?>
<?php endif; ?>
<form id="comment-form" method="post"
      action="<?php echo base_url(true) . '/' . current_lang() . '/comments/create/' . $post['uuid'] ?>">
    <div class="form-group comment-input-box">
        <label for="comment-content"><?= t('comments.leave_a_comment') ?></label>
        <textarea id="comment-content" name="content" class="form-control" rows="3"
                  required><?php echo old('content') ?></textarea>
    </div>
    <input type="hidden" name="csrf-token" value="<?php echo csrf_token() ?>"/>
    <button class="btn btn-large waves-effect waves-light submit-btn"
            type="submit"><?php _t('comments.submit_comment') ?></button>
</form>
