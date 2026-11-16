<div class="post-form full-height">
    <div class="polaroid">
        <?php echo partial('post/partials/back') ?>
        <div class="row">
            <div class="col s12">
                <h1 class="center-align teal-text"><?php isset($post) ? _t('posts.update_post') : _t('posts.new_post') ?></h1>

                <?php if (session()->has('error')) : ?>
                    <?php echo partial('partials/messages/error') ?>
                <?php endif; ?>

                <?php echo partial('post/partials/modal', ['item' => t('common.image')]) ?>

                <div class="card teal accent-4">
                    <div class="card-content">
                        <form id="post_form" method="post" action="<?php echo base_url(true) . '/' . current_lang() . '/my-posts/' . (isset($post) ? 'amend/' . $post['uuid'] : 'create') ?>" enctype="multipart/form-data">
                            <div class="row">
                                <div class="input-field col s12">
                                    <input value="<?php echo $post['title'] ?? old('title') ?>" name="title" id="title" type="text" class="validate">
                                    <label for="title"><?php _t('common.title') ?></label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field col s12"><textarea name="content" id="content" placeholder="<?php echo _t('common.content') ?>"><?php echo $post['content'] ?? old('content') ?></textarea></div>
                            </div>
                            <div class="file-field input-field upload-btn">
                                <div class="btn">
                                    <span>Image</span>
                                    <input type="file" name="image">
                                </div>
                                <div class="file-path-wrapper">
                                    <input class="file-path validate" type="text">
                                </div>
                            </div>

                            <div class="post-image">
                                <?php if (isset($post) && $post['image']) : ?>
                                    <a class="waves-effect waves-light btn modal-trigger image_delete"
                                    data-url="<?php echo base_url(true) . '/' . current_lang() . '/my-posts/delete-image/' . $post['uuid'] ?>"
                                    href="#modal-confirm"
                                    title="<?php _t('actions.delete') ?>">
                                        <i class="material-icons">close</i>
                                    </a>
                                    <img src="<?php echo base_url() . '/uploads/' . auth()->user()->uuid . '/' . $post['image'] ?>" class="update_page_img">
                                <?php endif; ?>
                            </div>

                            <div class="center-align">
                                <input type="hidden" name="csrf-token" value="<?php echo csrf_token() ?>" />
                                <button class="btn btn-large waves-effect waves-light submit-btn" type="submit">
                                    <?php _t('actions.save') ?>
                                </button>
                                <a href="<?php echo base_url(true) . '/' . current_lang() ?>/my-posts" class="btn btn-large waves-effect waves-teal btn-flat white-text">
                                    <?php _t('actions.cancel') ?>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
