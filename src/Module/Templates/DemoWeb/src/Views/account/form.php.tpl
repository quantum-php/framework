<div class="main-wrapper">
    <div class="polaroid">
        <h1 class="center-align teal-text"><?php _t('account.account_settings'); ?></h1>    

        <?php if (session()->has('error')) : ?>
            <?php echo partial('partials/messages/error') ?>
        <?php endif; ?>

        <?php if (session()->has('success')): ?>
            <?php echo partial('partials/messages/success') ?>
        <?php endif; ?>

        <div class="row card teal accent-4">
            <div class="s12">
                <ul class="tabs account-tabs z-depth-1 teal accent-4">
                    <li class="tab col s3">
                        <a href="#account_profile" class="active">
                            <h6 class="white-text"><?php _t('account.profile'); ?></h6>
                        </a>
                    </li>
                    <li class="tab col s3">
                        <a href="#account_password">
                            <h6 class="white-text"><?php _t('common.password'); ?></h6>
                        </a>
                    </li>
                </ul>
            </div>
            
            <?php echo partial('account/partials/account') ?>
            <?php echo partial('account/partials/password') ?>
        </div>
    </div>
</div>
