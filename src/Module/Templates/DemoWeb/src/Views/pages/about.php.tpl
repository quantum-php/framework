<div class="main-wrapper teal accent-4">
    <div class="container wrapper center-align white-text">
        <h1><?php _t('common.about'); ?></h1>

        <div class="card teal accent-4">
            <div class="card-content">
                <div class="star-message section">
                    <h6><?php _t('common.star.message'); ?></h6>
                    <a class="btn waves-effect waves-light" href="https://github.com/quantum-php/framework" target="_blank" rel="noopener noreferrer"><?php _t('common.star.button'); ?></a>
                    <img src="https://img.shields.io/github/stars/quantum-php/framework?style=social" alt="<?php _t('common.star.badge_alt'); ?>">
                </div>

                <h6><?php _t('common.about_framework'); ?></h6>

                <h4><?php _t('common.version'); ?></h4>
                <h6><?php _t('common.current_version', env('APP_VERSION')); ?></h6>

                <h4 class="mt-4"><?php _t('common.cli_commands') ?></h4>

                <div class="cli-list">
                    <?php foreach ($commands as $command): ?>
                        <div class="cli-item">
                            <div class="cli-desc white-text">
                                <?php echo $command['description'] ?>
                            </div>

                            <div class="cli-command">
                                <code>php qt <?php echo $command['name'] ?></code>
                                <button class="copy-btn tooltipped" title="Copy" data-command="php qt <?php echo $command['name'] ?>">
                                    <i class="material-icons copy-content">content_copy</i>
                                    <i class="material-icons done-icon" style="display:none;">done</i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>
    </div>
</div>
<?php echo partial('partials/bubbles') ?>
