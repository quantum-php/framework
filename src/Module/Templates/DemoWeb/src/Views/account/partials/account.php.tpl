<div id="account_profile" class="col s12">
    <form method="post" class="teal accent-4 col s12 card-content" action="<?php echo base_url(true) . '/' . current_lang() ?>/account-settings/update" enctype="multipart/form-data">
        <div class="row">
            <div class="input-field col s12">
                <label class="auth-form-label"><?php _t('common.firstname'); ?></label>
                <input type="text" name="firstname" value="<?php echo auth()->user()->firstname ?>">
            </div>
        </div>
        <div class="row">
            <div class="input-field col s12">
                <label class="auth-form-label"><?php _t('common.lastname'); ?></label>
                <input type="text" name="lastname" value="<?php echo auth()->user()->lastname ?>">
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
