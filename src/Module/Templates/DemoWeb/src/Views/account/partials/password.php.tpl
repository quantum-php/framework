<div id="account_password" class="col s12 hide">
    <form method="post" class="teal accent-4 col s12 card-content" action="<?php echo base_url(true) . '/' . current_lang() ?>/account-settings/update-password" enctype="multipart/form-data">
        <div class="row">
            <div class="input-field col s12">
                <label class="auth-form-label"><?php _t('common.current_password'); ?></label>
                <input type="password" name="current_password" value="">
            </div>
        </div>
        <div class="row">
            <div class="input-field col s12">
                <label class="auth-form-label"><?php _t('common.new_password'); ?></label>
                <input type="password" name="new_password" value="">
            </div>
        </div>
        <div class="row">
            <div class="input-field col s12">
                <label class="auth-form-label"><?php _t('common.confirm_password'); ?></label>
                <input type="password" name="confirm_password" value="">
            </div>
        </div>
        <div class="row center-align">
            <input type="hidden" name="csrf-token" value="<?php echo csrf_token() ?>"/>
            <input type="hidden" name="uuid" value="<?php echo auth()->user()->uuid ?>"/>
            <button class="btn btn-large waves-effect waves-light" type="submit">
                <?php _t('actions.save') ?>
            </button>
        </div>
    </form>
</div>
