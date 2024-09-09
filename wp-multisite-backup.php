<?php
/**
 * Plugin Name: WP Multisite Backup
 * Description: Плагин для создания и восстановления резервных копий мультисайтовой сети WordPress.
 * Version: 2.3
 * Author: Pavel Afanasev
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Подключение необходимых файлов
require_once plugin_dir_path(__FILE__) . 'includes/backup-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/restore-functions.php';

// Создание страницы настроек плагина в админке
add_action('network_admin_menu', 'wp_multisite_backup_menu');

function wp_multisite_backup_menu() {
    add_menu_page(
        'WP Multisite Backup',
        'Multisite Backup',
        'manage_network_options',
        'wp-multisite-backup',
        'wp_multisite_backup_page'
    );
}

function wp_multisite_backup_page() {
    ?>
    <div class="wrap">
        <h1>WP Multisite Backup</h1>
        <h2 class="nav-tab-wrapper">
            <a href="#tab-backup" class="nav-tab nav-tab-active" id="tab-backup-link">Create</a>
            <a href="#tab-backuplist" class="nav-tab" id="tab-backuplist-link">Backups</a>
            <a href="#tab-restore" class="nav-tab" id="tab-restore-link">Restore</a>
        </h2>
        <div id="tab-backup" class="tab-content">
            <form id="backup-form" method="post">
                <h2>Create Backup</h2>
                <p>Select number of rows to export per request:</p>
                <p>
                    <input type="number" id="rows-per-request" value="10000">
                </p>
                <p>
                    <input type="checkbox" id="skipDB" value="0"> Skip creating DB dump
                </p>
                <p>Select number of files to process per request:</p>
                <p>
                    <input type="number" id="files-per-request" value="300">
                </p>
                <p>
                    <input type="checkbox" id="skipFiles" value="0"> Skip creating files archive
                </p>
                <p>
                    <button type="button" id="create-backup-button" class="button button-primary">Create Backup</button>
                    <button type="button" id="cancel-backup-button" class="button button-secondary" style="display: none;">Cancel Backup</button>
                </p>
                <div id="backup-status"></div>
                <div id="progress-bar" style="display: none;">
                    <div id="progress-bar-inner" style="width: 0%; height: 20px; background-color: #4caf50;"></div>
                </div>
            </form>
            <div class="notice">
                <p>For deploying the backup on a bare environment, you can use the <a href="<?php echo plugin_dir_url(__FILE__) . 'restore.php'; ?>" download>restore.php</a> script.</p>
            </div>
        </div>
        <div id="tab-backuplist" class="tab-content" style="display:none;">
            <ul id="backuplist-wrapper"></ul>
        </div>
        <div id="tab-restore" class="tab-content" style="display:none;">
            <form id="restore-form" method="post">
                <h2>Restore Backup</h2>
                <p>Enter the new domain if it has changed:</p>
                <p>
                    <input type="text" name="new_domain" placeholder="newdomain.com">
                </p>
                <p>
                    <input type="submit" name="restore_backup" class="button button-secondary" value="Restore Backup">
                </p>
            </form>
        </div>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle tab switching
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                $('.nav-tab').removeClass('nav-tab-active');
                $('.tab-content').hide();
                $(this).addClass('nav-tab-active');
                var target = $(this).attr('href');
                $(target).show();
            });

            // Добавляем обработчик события клика
            document.querySelectorAll('input[type=checkbox]').forEach(elem => {
                elem.addEventListener('click', () => {
                    // Проверяем текущее значеие атрибута value
                    if (elem.getAttribute('value') === '1') {
                        // Если значение 1, меняем на 0
                        elem.setAttribute('value', '0');
                    } else {
                        // Иначе устанавливаем значение 1
                        elem.setAttribute('value', '1');
                    }
                });
            })

            $('#tab-backuplist').on('click', function(){
                getBackupList();
            });

            getBackupList();

            // Handle backup process
            $('#create-backup-button').on('click', function() {
                $('#backup-status').html('<p>Starting backup process...</p>');
                $('#create-backup-button').hide();
                $('#cancel-backup-button').show();
                $('#progress-bar').show();
                createBackup(1, 0, 0);
            });

            $('#cancel-backup-button').on('click', function() {
                cancelBackup();
            });

            function getBackupList() {
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'wp_multisite_get_backup_list',
                    },
                    success: function(response) {
                        folders = response.data.folders;
                        bck_wrapper = $('#backuplist-wrapper');
                        counter = 1;
                        folders.forEach(folder => {
                            bck_wrapper.html(bck_wrapper.html() + '<li class=""><b>' + counter + '.</b> - ' + folder + '</li>');
                            counter++;
                        });
                    },
                    error: function(response) {
                        folders = response.data.folders;
                        bck_wrapper = $('#backuplist-wrapper');
                        folders.forEach(folder => {
                            bck_wrapper.html(bck_wrapper.html() + '<li>' + folder + '</li>');
                        });
                    }
                });
            }

            function createBackup(step, offset, fileOffset) {
                const rowsPerRequest = $('#rows-per-request').val();
                const filesPerRequest = $('#files-per-request').val();
                const skipDB = $('#skipDB').val();
                const skipFiles = $('#skipFiles').val();
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'wp_multisite_create_backup',
                        step: step,
                        offset: offset,
                        file_offset: fileOffset,
                        rows_per_request: rowsPerRequest,
                        files_per_request: filesPerRequest,
                        skipDB: skipDB,
                        skipFiles: skipFiles
                    },
                    success: function(response) {
                        $('#backup-status').html(response.data.message);
                        updateProgressBar(response.data.progress);
                        if (response.data.nextStep || response.data.nextOffset !== undefined || response.data.nextFileOffset !== undefined) {
                            createBackup(response.data.nextStep, response.data.nextOffset, response.data.nextFileOffset);
                        } else {
                            $('#backup-status').append('<p>Backup process completed.</p>');
                            $('#create-backup-button').show();
                            $('#cancel-backup-button').hide();
                            $('#progress-bar').hide();
                        }
                    },
                    error: function(response) {
                        $('#backup-status').html('<p>Backup process canceled or failed.</p>');
                        $('#create-backup-button').show();
                        $('#cancel-backup-button').hide();
                        $('#progress-bar').hide();
                    }
                });
            }

            function cancelBackup() {
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'wp_multisite_cancel_backup'
                    },
                    success: function(response) {
                        $('#backup-status').html('<p>Backup process has been canceled.</p>');
                        $('#create-backup-button').show();
                        $('#cancel-backup-button').hide();
                        $('#progress-bar').hide();
                    }
                });
            }

            function updateProgressBar(progress) {
                $('#progress-bar-inner').css('width', progress + '%');
            }
        });
    </script>
    <?php
}

// AJAX обработчик для создания резервной копии
add_action('wp_ajax_wp_multisite_get_backup_list', 'wp_multisite_get_backup_list');

function wp_multisite_get_backup_list() {
    // Путь к директории, в которой нужно получить список папок
    $directory_path = __DIR__ . '/backup/';

    // Функция для получения списка папок
    function get_folders($directory) {
        $folders = array();

        // Открываем директорию
        if (is_dir($directory)) {
            if ($dh = opendir($directory)) {
                // Читаем содержимое директории
                while (($file = readdir($dh)) !== false) {
                    // Пропускаем текущую и родительскую директории
                    if ($file == '.' || $file == '..') {
                        continue;
                    }

                    // Проверяем, является ли элемент папкой
                    if (is_dir($directory . $file)) {
                        $folders[] = $file;
                    }
                }
                closedir($dh);
            }
            wp_send_json_success(['folders' => $folders]);
        } else {
            wp_send_json_error(['folders' => ["Директория не существует."]]);
        }

    }

    // Получаем список папок
    get_folders($directory_path);
}

// AJAX обработчик для создания резервной копии
add_action('wp_ajax_wp_multisite_create_backup', 'wp_multisite_create_backup_ajax');

function wp_multisite_create_backup_ajax() {
    global $wpdb;
    $step = isset($_POST['step']) ? intval($_POST['step']) : 1;
    $skipDB = isset($_POST['skipDB']) ? intval($_POST['skipDB']) : 0;
    $skipFiles = isset($_POST['skipFiles']) ? intval($_POST['skipFiles']) : 0;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $file_offset = isset($_POST['file_offset']) ? intval($_POST['file_offset']) : 0;
    $rows_per_request = isset($_POST['rows_per_request']) ? intval($_POST['rows_per_request']) : 10000;
    $files_per_request = isset($_POST['files_per_request']) ? intval($_POST['files_per_request']) : 300;

    // Проверка, была ли отмена
    if (get_option('wp_multisite_backup_cancel', false)) {
        delete_option('wp_multisite_backup_cancel');
        wp_multisite_cleanup_backup();
        wp_send_json_error(['message' => 'Backup process canceled.']);
    }

    switch ($step) {
        case 1:
            // Создание директории для резервных копий
            $backup_dir = __DIR__ . '/backup';
            if (!file_exists($backup_dir)) {
                if (!mkdir($backup_dir, 0755, true)) {
                    wp_send_json_error(['message' => 'Step 1: Failed to create backup directory.']);
                }
            }
            $backup_temp_dir = $backup_dir . '/backup-' . time();
            if (!file_exists($backup_temp_dir)) {
                if (!mkdir($backup_temp_dir, 0755, true)) {
                    wp_send_json_error(['message' => 'Step 1: Failed to create backup temp directory.']);
                }
            }
            update_option('wp_multisite_backup_temp_dir', $backup_temp_dir);
            wp_send_json_success(['message' => 'Step 1: Backup directory created.', 'nextStep' => 2]);
            break;
        case 2:
            // Инициализация резервной копии базы данных
            $backup_file = get_option('wp_multisite_backup_temp_dir') . '/db.sqlbkp';
            update_option('wp_multisite_backup_file', $backup_file);
            file_put_contents($backup_file, "");  // Создаем пустой файл

            // Получение списка всех таблиц в базе данных
            $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
            if (!$tables) {
                wp_send_json_error(['message' => 'Step 2: Failed to retrieve tables.']);
            }
            update_option('wp_multisite_backup_tables', $tables);
            update_option('wp_multisite_backup_table_index', 0);
            update_option('wp_multisite_backup_total_tables', count($tables));

            wp_send_json_success(['message' => 'Step 2: Tables list retrieved.', 'nextStep' => 3, 'nextOffset' => 0]);
            break;
        case 3:
            if ($skipDB == 0) {
                // Резервное копирование данных таблиц
                $tables = get_option('wp_multisite_backup_tables');
                $table_index = get_option('wp_multisite_backup_table_index', 0);
                $total_tables = get_option('wp_multisite_backup_total_tables', count($tables));
                $backup_file = get_option('wp_multisite_backup_file');
                $table = $tables[$table_index][0];

                // Если это новый цикл для этой таблицы, добавляем создание таблицы
                if ($offset == 0) {
                    $create_table = $wpdb->get_row("SHOW CREATE TABLE `$table`", ARRAY_N);
                    if (!$create_table) {
                        wp_send_json_error(['message' => "Step 3: Failed to get CREATE statement for table $table."]);
                    }
                    file_put_contents($backup_file, "\n\n" . $create_table[1] . ";\n\n", FILE_APPEND);
                }

                // Получаем данные из таблицы с учетом смещения
                $rows = $wpdb->get_results("SELECT * FROM `$table` LIMIT $offset, $rows_per_request", ARRAY_N);
                if ($rows === false) {
                    wp_send_json_error(['message' => "Step 3: Failed to get rows for table $table."]);
                }

                // Формируем SQL для вставки данных
                $sql = '';
                foreach ($rows as $row) {
                    $sql .= "INSERT INTO `$table` VALUES(";
                    foreach ($row as $data) {
                        $data = addslashes($data);
                        $data = str_replace("\n", "\\n", $data);
                        $sql .= "'$data',";
                    }
                    $sql = rtrim($sql, ',');
                    $sql .= ");\n";
                }

                // Записываем данные в файл
                file_put_contents($backup_file, $sql, FILE_APPEND);

                // Если все строки из таблицы выгружены, переходим к следующей таблице
                if (count($rows) < $rows_per_request) {
                    $table_index++;
                    $offset = 0;
                } else {
                    $offset += $rows_per_request;
                }

                // Обновление прогресса
                $progress = min(100, (($table_index + $offset / $rows_per_request) / $total_tables) * 100);

                // Если все таблицы обработаны, переходим к шагу архивации файлов
                if ($table_index >= count($tables)) {
                    delete_option('wp_multisite_backup_tables');
                    delete_option('wp_multisite_backup_table_index');
                    delete_option('wp_multisite_backup_total_tables');
                    wp_send_json_success(['message' => 'Step 3: Database backup completed.', 'nextStep' => 4, 'nextOffset' => 0, 'progress' => $progress]);
                } else {
                    update_option('wp_multisite_backup_table_index', $table_index);
                    wp_send_json_success(['message' => "Step 3: Backing up table $table...", 'nextStep' => 3, 'nextOffset' => $offset, 'progress' => $progress, 'filename' => $backup_file]);
                }
                break;
            } else {
                wp_send_json_success(['message' => 'Step 3: Database backup skipped.', 'nextStep' => 4]);
            }
        case 4:
            if ($skipFiles == 0) {

                // wp_send_json_success(['message' => 'Step 4444: Database backup skipped.', 'nextStep' => 4]);
                // break;

                // Архивация файлов
                $backup_dir = get_option('wp_multisite_backup_temp_dir') . '/';
                if (!file_exists($backup_dir)) {
                    // Папка не существует, создаем её
                    mkdir($backup_dir, 0755, true);
                }
                $zip_file = $backup_dir . 'files.zipbkp';
                update_option('wp_multisite_backup_zip', $zip_file);
                $root_path = realpath(ABSPATH);
                $exclude_dir = $root_path . '/wp-content/plugins/wp-multisite-backup';
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($root_path),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                $total_files = iterator_count($files);
                $files->rewind();

                // Пропуск обработанных файлов
                for ($i = 0; $i < $file_offset; $files->next(), $i++);

                $zip = new ZipArchive();
                if ($zip->open($zip_file, ZipArchive::CREATE) !== TRUE) {
                    wp_send_json_error(['message' => 'Step 4: Failed to create zip file.']);
                }

                $added_files = 0;
                while ($files->valid() && $added_files < $files_per_request) {  // Обрабатываем по files_per_request файлов за раз
                    if (!$files->isDir()) {
                        // Проверка, была ли отмена
                        if (get_option('wp_multisite_backup_cancel', false)) {
                            delete_option('wp_multisite_backup_cancel');
                            $zip->close();
                            wp_multisite_cleanup_backup();
                            wp_send_json_error(['message' => 'Backup process canceled.']);
                        }

                        $file_path = $files->getRealPath();
                        $relative_path = substr($file_path, strlen($root_path) + 1);
                        
                        // Исключаем файлы из определённой директории
                        if (strpos($file_path, $exclude_dir) !== 0) {
                            $zip->addFile($file_path, $relative_path);
                        }
                    }
                    $files->next();
                    $added_files++;
                    $file_offset++;
                }

                $zip->close();

                // Обновление прогресса
                $progress = min(100, ($file_offset / $total_files) * 100);

                if ($file_offset >= $total_files) {
                    wp_send_json_success(['message' => 'Step 4: Files backup completed.', 'progress' => $progress]);
                } else {
                    wp_send_json_success(['message' => "Step 4: Backing up files... ($file_offset / $total_files)", 'nextStep' => 4, 'nextFileOffset' => $file_offset, 'progress' => $progress]);
                }
                break;
            }
        default:
            wp_send_json_error(['message' => 'Unknown step.']);
    }
}

// AJAX обработчик для отмены резервной копии
add_action('wp_ajax_wp_multisite_cancel_backup', 'wp_multisite_cancel_backup_ajax');

function wp_multisite_cancel_backup_ajax() {
    update_option('wp_multisite_backup_cancel', true);
    wp_send_json_success(['message' => 'Backup process canceled.']);
}

function wp_multisite_cleanup_backup() {
    $backup_file = get_option('wp_multisite_backup_file');
    $backup_zip = get_option('wp_multisite_backup_zip');
    if ($backup_file && file_exists($backup_file)) {
        unlink($backup_file);
        delete_option('wp_multisite_backup_file');
    }
    if ($backup_zip && file_exists($backup_zip)) {
        unlink($backup_zip);
        delete_option('wp_multisite_backup_zip');
    }
}
?>
