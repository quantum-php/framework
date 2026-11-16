<nav class="teal accent-4">
    <div class="nav-wrapper">
        <span class="navbar-logo brand-logo">
            <?php if (route_name() != 'home') : ?>
                <?php echo partial('partials/logo') ?>
            <?php endif; ?>
        </span>
        <a href="#" data-target="mobile-demo" class="sidenav-trigger"><i class="material-icons">menu</i></a>
        <ul class="right hide-on-med-and-down">
            <?php if (route_name() == 'posts') : ?>
                <?php echo partial('partials/search') ?>
            <?php endif; ?>
            <li>
                <a href="<?php echo base_url(true) . '/' . current_lang() ?>/posts" class="white-text">
                    <?php _t('posts.posts') ?>
                </a>
            </li>
            <?php if (auth()->check()) : ?>
                <li>
                    <a class="dropdown-trigger login-list" href="#!" data-target="dropdown1">
                        <span class="show-on-medium-and-up-header-avatar-box">
                            <?php if (auth()->user()->image): ?>
                                <img src="<?php echo base_url() . '/uploads/' . auth()->user()->uuid . '/' . auth()->user()->image ?>"
                                     alt="Avatar" class="circle left user-avatar">
                                <?php echo auth()->user()->firstname . ' ' . auth()->user()->lastname ?>
                                <i class="material-icons right">arrow_drop_down</i>
                            <?php else: ?>
                                <i class="material-icons left">person</i>
                            <?php endif; ?>

                        </span>
                    </a>
                    <ul id="dropdown1" class="dropdown-content">
                        <li>
                            <a href="<?php echo base_url(true) . '/' . current_lang() ?>/account-settings">
                                <?php _t('account.account_settings') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo base_url(true) . '/' . current_lang() ?>/my-posts">
                                <?php _t('posts.my_posts') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo base_url(true) . '/' . current_lang() ?>/signout"><?php _t('auth.signout'); ?></a>
                        </li>
                    </ul>
                </li>
            <?php else : ?>
                <li>
                    <?php if (route_name() != 'signup') : ?>
                        <a href="<?php echo base_url(true) . '/' . current_lang() ?>/signup" class="white-text">
                            <?php _t('auth.signup') ?>
                        </a>
                    <?php endif; ?>
                </li>
                <li>
                    <?php if (route_name() != 'signin') : ?>
                        <a href="<?php echo base_url(true) . '/' . current_lang() ?>/signin" class="white-text">
                            <?php _t('auth.signin') ?>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endif; ?>
            <?php echo partial('partials/language', ['attr' => 'dropdown2']) ?>
        </ul>

    </div>
</nav>
<?php echo partial('partials/sidebar') ?>
