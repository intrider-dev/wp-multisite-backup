<?php

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

function wp_multisite_restore_backup($new_domain = null) {
    // Путь к директории для хранения резервных копий
    $backup_dir = wp_upload_dir()['basedir'] . '/multisite-backups/';
    $backup_files = glob($backup_dir . '*.sql');

    if (!empty($backup_files)) {
        $latest_backup = end($backup_files);

        // Восстановление базы данных
        $command = sprintf('mysql --user=%s --password=%s --host=%s %s < %s',
            DB_USER,
            DB_PASSWORD,
            DB_HOST,
            DB_NAME,
            $latest_backup
        );
        system($command);

        // Восстановление файлов
        $zip_files = glob($backup_dir . '*.zip');
        if (!empty($zip_files)) {
            $latest_zip = end($zip_files);
            $zip = new ZipArchive();
            if ($zip->open($latest_zip) === TRUE) {
                $zip->extractTo(ABSPATH);
                $zip->close();
            }
        }

        // Обновление домена в базе данных
        if ($new_domain) {
            global $wpdb;
            $old_domain = get_site_option('siteurl');
            
            // Обновление URL-адресов в таблицах wp_blogs, wp_site и wp_sitemeta
            $wpdb->query("UPDATE {$wpdb->blogs} SET domain = REPLACE(domain, '{$old_domain}', '{$new_domain}')");
            $wpdb->query("UPDATE {$wpdb->site} SET domain = '{$new_domain}'");
            $wpdb->query("UPDATE {$wpdb->sitemeta} SET meta_value = REPLACE(meta_value, '{$old_domain}', '{$new_domain}') WHERE meta_key IN ('siteurl', 'home')");
            
            // Обновление URL-адресов в других таблицах
            $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->base_prefix}%'");
            foreach ($tables as $table) {
                foreach ($table as $t) {
                    $wpdb->query("UPDATE {$t} SET option_value = REPLACE(option_value, '{$old_domain}', '{$new_domain}') WHERE option_name IN ('siteurl', 'home')");
                    $wpdb->query("UPDATE {$t} SET meta_value = REPLACE(meta_value, '{$old_domain}', '{$new_domain}')");
                    $wpdb->query("UPDATE {$t} SET guid = REPLACE(guid, '{$old_domain}', '{$new_domain}')");
                }
            }
        }

        echo '<div class="notice notice-success is-dismissible"><p>Backup restored successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>No backup found to restore!</p></div>';
    }
}
