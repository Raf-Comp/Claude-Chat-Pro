<?php
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die(__('Brak dostępu', 'claude-chat-pro'));

// Informacje o aktualnym użytkowniku i czasie
$current_user = wp_get_current_user();
$current_time_utc = current_time('mysql', true);
?>

<div class="wrap claude-chat-settings">
    <h1><?php _e('Ustawienia Claude Chat Pro', 'claude-chat-pro'); ?></h1>

    <!-- Informacje systemowe -->
    <div class="system-info">
        <p>
            <strong><?php _e('Aktualny użytkownik:', 'claude-chat-pro'); ?></strong> 
            <?php echo esc_html($current_user->user_login); ?>
        </p>
        <p>
            <strong><?php _e('Data i czas (UTC):', 'claude-chat-pro'); ?></strong> 
            <?php echo esc_html($current_time_utc); ?>
        </p>
    </div>

    <!-- Formularz ustawień -->
    <form method="post" action="options.php" class="claude-chat-settings-form">
        <?php
        settings_fields('claude_chat_pro_options');
        do_settings_sections('claude_chat_pro_settings');
        submit_button(__('Zapisz ustawienia', 'claude-chat-pro'));
        ?>
    </form>
</div>

<style>
.claude-chat-settings {
    max-width: 800px;
    margin: 20px auto;
}

.system-info {
    background: #fff;
    padding: 15px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
}

.api-status {
    margin: 20px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.api-status-item {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-icon {
    font-size: 1.2em;
}

.status-icon.success {
    color: #46b450;
}

.status-icon.error {
    color: #dc3232;
}

.api-key-field {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
}

.toggle-password {
    padding: 0 8px;
    height: 30px;
}

.toggle-password .dashicons {
    margin-top: 3px;
}

/* Responsywność */
@media screen and (max-width: 782px) {
    .api-key-field {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .api-key-field input[type="password"],
    .api-key-field input[type="text"] {
        width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Obsługa pokazywania/ukrywania haseł
    $('.toggle-password').on('click', function() {
        const targetId = $(this).data('target');
        const input = $('#' + targetId);
        const icon = $(this).find('.dashicons');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            input.attr('type', 'password');
            icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });

    // Potwierdzenie przed zapisaniem zmian
    $('.claude-chat-settings-form').on('submit', function(e) {
        if (!confirm('<?php _e('Czy na pewno chcesz zapisać zmiany w ustawieniach?', 'claude-chat-pro'); ?>')) {
            e.preventDefault();
        }
    });
});
</script>