<?php

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

function wp_multisite_create_backup() {
    // Путь к директории для хранения резервных копий
    $backup_dir = wp_upload_dir()['basedir'] . '/multisite-backups/';
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }

    // Создание резервной копии базы данных
    global $wpdb;
    $backup_file = $backup_dir . 'backup-' . time() . '.sql';
    $command = sprintf('mysqldump --user=%s --password=%s --host=%s %s > %s',
        DB_USER,
        DB_PASSWORD,
        DB_HOST,
        DB_NAME,
        $backup_file
    );
    system($command);

    // Создание резервной копии файлов
    $zip_file = $backup_dir . 'backup-' . time() . '.zip';
    $root_path = realpath(ABSPATH);
    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root_path),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $file_path = $file->getRealPath();
                $relative_path = substr($file_path, strlen($root_path) + 1);
                $zip->addFile($file_path, $relative_path);
            }
        }
        $zip->close();
    }

    echo '<div class="notice notice-success is-dismissible"><p>Backup created successfully!</p></div>';
}
