<?php
if (!isset($_GET['action']) && !isset($_POST['action'])):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Restore WordPress Backup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .step {
            margin-bottom: 20px;
        }
        .step h2 {
            font-size: 1.2em;
            margin-bottom: 10px;
        }
        .step p {
            margin: 5px 0;
        }
        .step input[type="text"], .step input[type="url"], .step input[type="password"], .step input[type="file"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .step button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 5px;
            transition: filter .2s, scale .2s;
            cursor: pointer;
        }
        .step button:hover {
            filter: drop-shadow(2px 4px 6px #6b6b6b80);
            scale: 1.05;
        }
        .step button:disabled {
            background-color: #ddd;
            cursor: not-allowed;
        }
        .progress {
            display: none;
            margin-top: 20px;
        }
        .progress-bar {
            width: 100%;
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        .progress-bar-inner {
            width: 0;
            height: 20px;
            background-color: #28a745;
            transition: width 0.3s;
        }
        .btn-wrapper {
            padding-top: 10px;
        }
        .backbtn {
            background-color: #569382 !important;
        }
        #step2 p {
            padding: 10px 0px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Restore WordPress Backup</h1>
    
    <div id="overall-progress" class="progress">
        <div class="progress-bar">
            <div class="progress-bar-inner" id="overall-progress-bar"></div>
        </div>
    </div>
    
    <div class="step" id="step1">
        <h2>Step 1: Enter Database Configuration</h2>
        <p>Database Host:</p>
        <input type="text" id="db-host" placeholder="localhost" required>
        <p>Database Name:</p>
        <input type="text" id="db-name" placeholder="your_database_name" required>
        <p>Database User:</p>
        <input type="text" id="db-user" placeholder="your_database_user" required>
        <p>Database Password:</p>
        <input type="password" id="db-password" placeholder="your_database_password" required>
        <div class="btn-wrapper">
            <button class="backbtn" onclick="stageBack()">Back</button>
            <button onclick="showStep2()">Next</button>
        </div>
    </div>

    <div class="step" id="step2" style="display: none;">
        <h2>Step 2: Select Backup Source</h2>
        <p>
            <input type="radio" id="source-url" name="backup-source" value="url" checked>
            <label for="source-url">Enter Backup Links</label>
        </p>
        <p>
            <input type="radio" id="source-local" name="backup-source" value="local">
            <label for="source-local">Upload Backup Files</label>
        </p>
        <div id="url-inputs">
            <p>Enter the URL to the SQL backup file:</p>
            <input type="url" id="sql-url" placeholder="http://example.com/backup.sql" required>
            <p>Enter the URL to the ZIP backup file:</p>
            <input type="url" id="zip-url" placeholder="http://example.com/backup.zip" required>
        </div>
        <div id="file-inputs" style="display: none;">
            <p>Upload the SQL backup file:</p>
            <input type="file" id="sql-file" accept=".sql">
            <p>Upload the ZIP backup file:</p>
            <input type="file" id="zip-file" accept=".zip">
        </div>
        <div class="btn-wrapper">
            <button class="backbtn" onclick="stageBack()">Back</button>
            <button onclick="downloadOrUploadFiles()">Next</button>
        </div>
    </div>

    <div class="step" id="step3" style="display: none;">
        <h2>Step 3: Enter New Domain</h2>
        <p>Enter the new domain for the multisite network:</p>
        <input type="text" id="old-domain" placeholder="newdomain.com" required>
        <input type="text" id="new-domain" placeholder="newdomain.com" required>
        <div class="btn-wrapper">
            <button class="backbtn" onclick="stageBack()">Back</button>
            <button onclick="executeSQL()">Next</button>
        </div>
    </div>

    <div class="step" id="step4" style="display: none;">
        <h2>Step 4: Restore Database</h2>
        <p>Database restoration in progress...</p>
        <div class="progress">
            <div class="progress-bar">
                <div class="progress-bar-inner" id="db-progress-bar"></div>
            </div>
        </div>
        <div class="btn-wrapper">
            <button class="backbtn" onclick="executeSQL()">Reload</button>
        </div>
    </div>

    <div class="step" id="step5" style="display: none;">
        <h2>Step 5: Restore Files</h2>
        <p>File restoration in progress...</p>
        <div class="progress">
            <div class="progress-bar">
                <div class="progress-bar-inner" id="file-progress-bar"></div>
            </div>
        </div>
    </div>

    <div class="step" id="step6" style="display: none;">
        <h2>Restore Complete</h2>
        <p>The backup has been successfully restored.</p>
    </div>
</div>

<script>
    let dbConfig = {};

    //Вернуться на стадию назад
    function stageBack() {
        document.querySelectorAll('.step').forEach(elem => {
            if (elem.style.display != 'none') {
                var cur_step = elem.getAttribute('id').replace('step', '');
                if (cur_step > 1) {
                    document.querySelectorAll('.step').forEach(elem_sub => {
                        elem_sub.style.display = 'none';
                    });
                    document.querySelector('#step' + (cur_step - 1)).style.display = 'block';
                }
            }
        });
    }

    // Добавляем обработчик для переключения между URL и локальной загрузкой файлов
    document.querySelectorAll('input[name="backup-source"]').forEach((elem) => {
        elem.addEventListener('change', function(event) {
            if (event.target.value === 'url') {
                document.getElementById('url-inputs').style.display = 'block';
                document.getElementById('file-inputs').style.display = 'none';
            } else {
                document.getElementById('url-inputs').style.display = 'none';
                document.getElementById('file-inputs').style.display = 'block';
            }
        });
    });

    // Переход ко второму шагу после заполнения конфигурации базы данных
    function showStep2() {
        dbConfig.host = document.getElementById('db-host').value;
        dbConfig.name = document.getElementById('db-name').value;
        dbConfig.user = document.getElementById('db-user').value;
        dbConfig.password = document.getElementById('db-password').value;

        if (!dbConfig.host || !dbConfig.name || !dbConfig.user || !dbConfig.password) {
            alert('Please fill in all database configuration fields.');
            return;
        }

        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = 'block';
    }

    // Обработка выбора источника резервной копии (URL или локальные файлы)
    function downloadOrUploadFiles() {
        const source = document.querySelector('input[name="backup-source"]:checked').value;
        if (source === 'url') {
            downloadFiles();
        } else {
            uploadFiles();
        }
    }

    // Загрузка файлов по URL
    function downloadFiles() {
        const sqlUrl = document.getElementById('sql-url').value;
        const zipUrl = document.getElementById('zip-url').value;
        if (!sqlUrl || !zipUrl) {
            alert('Please enter both URLs.');
            return;
        }

        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'block';

        fetch(`restore.php?action=download&sql_url=${encodeURIComponent(sqlUrl)}&zip_url=${encodeURIComponent(zipUrl)}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message);
                }
            });
    }

    // Загрузка локальных файлов на сервер
    function uploadFiles() {
        const sqlFile = document.getElementById('sql-file').files[0];
        const zipFile = document.getElementById('zip-file').files[0];
        if (!sqlFile || !zipFile) {
            alert('Please upload both files.');
            return;
        }

        const formData = new FormData();
        formData.append('sql_file', sqlFile);
        formData.append('zip_file', zipFile);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'restore.php?action=upload', true);

        // Обновление прогресс-бара во время загрузки файлов
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                document.getElementById('overall-progress-bar').style.width = percentComplete + '%';
            }
        });

        // Обработка завершения загрузки файлов
        xhr.addEventListener('load', function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    document.getElementById('step2').style.display = 'none';
                    document.getElementById('step3').style.display = 'block';
                    document.getElementById('overall-progress-bar').style.width = '0%';
                } else {
                    alert(response.message);
                }
            } else {
                alert('Upload failed. Server returned status: ' + xhr.status);
            }
        });

        xhr.send(formData);
    }

    function executeSQL() {
        const newDomain = document.getElementById('new-domain').value;
        if (!newDomain) {
            alert('Please enter the new domain.');
            return;
        }

        const step4_process = document.querySelector('#step4 p');

        document.getElementById('step3').style.display = 'none';
        document.getElementById('step4').style.display = 'block';

        const params = new URLSearchParams(dbConfig);
        params.append('action', 'restore_db');
        params.append('new_domain', newDomain);

        fetch(`restore.php?${params.toString()}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'completed') {
                step4_process.innerHTML = 'SQL file processed successfully.';
                document.getElementById('step4').style.display = 'none';
                document.getElementById('step5').style.display = 'block';
                restoreFiles();
            } else if (data.status === 'in_progress') {
                step4_process.innerHTML = 'Processing next chunk... [' + data.progress[0] + ' / ' + data.progress[1]+ ']';
                if (document.querySelector('#step4 .progress').style.display != 'block') {
                    document.querySelector('#step4 .progress').style.display = 'block';
                } 
                var percentComplete = (data.progress[0] / data.progress[1]) * 100;
                document.querySelector('#step4 .progress-bar-inner').style.width = percentComplete + '%';
                setTimeout(executeSQL, 1000); // Continue processing after a short delay
            } else if (data.status === 'error') {
                step4_process.innerHTML = 'Error: ' + data.message;
            }
            console.log(data);
        })
        .catch(error => {
            step4_process.innerHTML = 'Fetch error: ' + error;
        });
    }

    // Восстановление базы данных
    function restoreDatabase() {
        const newDomain = document.getElementById('new-domain').value;
        if (!newDomain) {
            alert('Please enter the new domain.');
            return;
        }

        document.getElementById('step3').style.display = 'none';
        document.getElementById('step4').style.display = 'block';

        const params = new URLSearchParams(dbConfig);
        params.append('action', 'restore_db');
        params.append('new_domain', newDomain);

        fetch(`restore.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('db-progress-bar').style.width = '100%';
                    document.getElementById('step4').style.display = 'none';
                    document.getElementById('step5').style.display = 'block';
                    restoreFiles();
                } else {
                    alert(data.message);
                }
            });
    }

    // Восстановление файлов
    function restoreFiles() {
        fetch('restore.php?action=restore_files')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('file-progress-bar').style.width = '100%';
                    document.getElementById('step5').style.display = 'none';
                    document.getElementById('step6').style.display = 'block';
                } else {
                    alert(data.message);
                }
            });
    }
</script>

</body>
</html>
<?php
endif;

// Функция для отправки JSON-ответов
function send_json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Функция для проверки наличия утилиты в системе
function check_utility($utility) {
    $output = shell_exec("which $utility");
    return !empty($output);
}

function remove_foreign_keys_from_sql($sql) {
    // Удаление внешних ключей из SQL запроса
    $modifiedSql = preg_replace('/,\s*CONSTRAINT\s+`[^`]+`\s+FOREIGN\s+KEY\s+\([^)]+\)\s+REFERENCES\s+`[^`]+`\s+\([^)]+\)\s+ON\s+DELETE\s+CASCADE/i', '', $sql);
    return $modifiedSql;
}

function process_sql_chunk($filename, $mysqli, $offset, $chunk_size = 1000024) {
    // send_json_response(['check' => 'process_sql_chunk1', 'value' => $offset]);
    $handle = fopen($filename, "r");
    if ($handle === false) {
        die("Ошибка при открытии файла: " . $filename);
    }

    if ($offset > 0) {
        fseek($handle, $offset);
    }

    $delimiter = ';';
    $command = '';
    $in_string = false;
    $string_delimiter = '';
    $in_comment = false;
    $processed = 0;

    while (($line = fgets($handle)) !== false) {
        $processed += strlen($line);
        $offset = ftell($handle);
        file_put_contents(__FILE__ . '_11.log', print_r($_SESSION['sql_offset'], 1));
        $trimmed_line = trim($line);

        // Пропуск пустых строк
        if (!$in_string && !$in_comment && $trimmed_line == '') {
            continue;
        }

        // Обработка многострочных комментариев
        if (!$in_string) {
            if (!$in_comment && substr($trimmed_line, 0, 2) == '/*') {
                $in_comment = true;
            }
            if ($in_comment && substr($trimmed_line, -2) == '*/') {
                $in_comment = false;
                continue;
            }
            if ($in_comment) {
                continue;
            }
        }

        $command .= $line;

        // Переключение флага in_string, если встречен неэкранированный разделитель строки
        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];
            if ($in_string) {
                if ($char == $string_delimiter && ($i == 0 || $line[$i - 1] != '\\')) {
                    $in_string = false;
                }
            } else {
                if ($char == '"' || $char == "'") {
                    $in_string = true;
                    $string_delimiter = $char;
                }
            }
        }

        // Проверка, заканчивается ли строка разделителем и мы не находимся внутри строки или комментария
        if (!$in_string && !$in_comment && substr(trim($line), -1) == $delimiter) {
            // Выполнение команды
            $command = remove_foreign_keys_from_sql($command);
            if ($offset > 0) {
                // send_json_response(['check' => 'process_sql_chunk2', 'value' => $command]);
                file_put_contents(__FILE__.'_process_sql_chunk.log', print_r($command.PHP_EOL, 1), FILE_APPEND);
            } else {
                file_put_contents(__FILE__.'_process_sql_chunk.log', print_r('', 1));
            }
            if ($mysqli->query($command) === false) {
                fclose($handle);
                return false;
            }
            $command = '';  // Сброс команды
        }

        // Прерывание цикла, чтобы избежать долгого времени выполнения
        if ($processed >= $chunk_size) {
            break;
        }
    }

    $res = feof($handle);  // Проверка завершенности файла
    fclose($handle);

    // Обновление смещения в отдельном файле
    $offset_out_file = __DIR__ . '/offset_out_file.txt';
    $current_offset = file_exists($offset_out_file) ? (int)file_get_contents($offset_out_file) : 0;

    file_put_contents($offset_out_file, $current_offset + $processed);

    return $res;
}

// Функция для скачивания файла с использованием доступной утилиты
function download_file($url, $output_file) {
    // Открываем файл для записи
    $fp = fopen($output_file, 'w+');
    if ($fp === false) {
        echo "Не удалось открыть файл для записи.\n";
        return false;
    }

    // Инициализируем cURL
    $ch = curl_init($url);

    ini_set('max_execution_time', 0); // Без ограничения времени выполнения
    ini_set('memory_limit', '1024M'); // Увеличение лимита памяти

    // Устанавливаем параметры cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FILE, $fp); // Записываем данные непосредственно в файл
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Следуем за редиректами
    curl_setopt($ch, CURLOPT_TIMEOUT, 0); // Без таймаута, полезно для больших файлов
    curl_setopt($ch, CURLOPT_BUFFERSIZE, 12800000); // Устанавливаем размер буфера
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:', 'Content-Type: application/octet-stream')); // Убираем заголовок Expect, который может вызывать проблемы, и устанавливаем тип контента
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET'); // Используем метод GET

    // Переменная для хранения вывода
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    // Выполняем запрос
    $result = curl_exec($ch);

    // Получаем HTTP-код ответа
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Закрываем cURL и файл
    curl_close($ch);
    fclose($fp);

    // Проверяем результат
    if ($result === false || $http_code != 200) {
        // Выводим подробную информацию об ошибке
        rewind($verbose);        
        unlink($output_file); // Удаляем файл, если загрузка не удалась
        return false;
    }

    return realpath($output_file); // Возвращаем абсолютный путь до скачанного файла
}

// Проверка наличия параметра action в запросе
if (isset($_GET['action']) || isset($_POST['action'])) {
    $action = isset($_GET['action']) ? $_GET['action'] : $_POST['action'];

    switch ($action) {
        case 'download':
            ini_set('max_execution_time', 0); // Без ограничения времени выполнения
            ini_set('memory_limit', '1024M'); // Увеличение лимита памяти
            $sql_url = $_GET['sql_url'];
            $zip_url = $_GET['zip_url'];

            $sql_backup_file = __DIR__.'/backup.sql';
            $zip_backup_file = __DIR__.'/backup.zip';

            // Загрузка файлов с использованием доступной утилиты
            $sql_download = download_file($sql_url, $sql_backup_file);
            $zip_download = download_file($zip_url, $zip_backup_file);

            // Проверка, успешно ли были загружены файлы
            if (!file_exists($sql_backup_file) || !file_exists($zip_backup_file)) {
                send_json_response(['success' => false, 'message' => 'Failed to download backup files.', '$zip_backup_file' => $zip_backup_file]);
            } else {
                send_json_response(['success' => true]);
            }
            break;

        case 'upload':
            ini_set('max_execution_time', 0); // Без ограничения времени выполнения
            ini_set('memory_limit', '1024M'); // Увеличение лимита памяти
            $sql_backup_file = 'backup.sql';
            $zip_backup_file = 'backup.zip';

            // Перемещение загруженных файлов в целевое место на сервере
            if (move_uploaded_file($_FILES['sql_file']['tmp_name'], $sql_backup_file) === false ||
                move_uploaded_file($_FILES['zip_file']['tmp_name'], $zip_backup_file) === false) {
                send_json_response(['success' => false, 'message' => 'Failed to upload backup files.']);
            } else {
                send_json_response(['success' => true]);
            }
            break;

        case 'restore_db':

            file_put_contents(__FILE__.'.log', print_r('1'.PHP_EOL, 1));

            ini_set('max_execution_time', 0); // Без ограничения времени выполнения
            ini_set('memory_limit', '1024M'); // Увеличение лимита памяти
            $db_host = $_GET['host'];
            $db_name = $_GET['name'];
            $db_user = $_GET['user'];
            $db_password = $_GET['password'];
            $old_domain = 'consilium.zheldor.ru';
            $new_domain = $_GET['new_domain'];
        
            $sql_backup_file = __DIR__ . '/backup.sql';
            $conn = new mysqli($db_host, $db_user, $db_password, $db_name);
        
            // Проверка соединения с базой данных
            if ($conn->connect_error) {
                send_json_response(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
                break;
            }

            file_put_contents(__FILE__.'.log', print_r('2'.PHP_EOL, 1), FILE_APPEND);
        
            $sql = file_get_contents($sql_backup_file);
            if ($sql === false) {
                send_json_response(['success' => false, 'message' => 'Failed to read SQL backup file.']);
                break;
            }

            file_put_contents(__FILE__.'.log', print_r('2'.PHP_EOL, 1), FILE_APPEND);
        
            // Установка режима sql_mode
            if ($conn->query("SET sql_mode = ''") === false) {
                send_json_response(['success' => false, 'message' => 'Failed to set SQL mode: ' . $conn->error]);
                break;
            }

            file_put_contents(__FILE__.'.log', print_r('3'.PHP_EOL, 1), FILE_APPEND);
        
            // Изменяем старый домен на новый домен в SQL файле
            if ($old_domain) {
                $sql = str_replace($old_domain, $new_domain, $sql);
            }

            file_put_contents(__FILE__.'.log', print_r('4'.PHP_EOL, 1), FILE_APPEND);
        
            // Выполнение SQL команд из файла
            // send_json_response(['check' => 'prefiles']);
            file_put_contents(__FILE__.'out_.log', print_r($_SESSION,1));

            // send_json_response(['check' => 'files']);

            $offset_out_file = __DIR__.'/offset_out_file.txt';

            if(!file_exists($offset_out_file)){
                // Очистка базы данных: удаление всех таблиц
                $result = $conn->query("SHOW TABLES");
                if ($result) {
                    while ($row = $result->fetch_array(MYSQLI_NUM)) {
                        $conn->query("DROP TABLE IF EXISTS " . $row[0]);
                    }
                } else {
                    send_json_response(['success' => false, 'message' => 'Failed to get list of tables: ' . $conn->error]);
                    break;
                }

                // Создание пустого файла
                $file = fopen($offset_out_file , 'w');
                fwrite($file, '0');
                // Закрытие файла
                fclose($file);
            }

            // Открываем файл для чтения
            $handle = fopen($offset_out_file, "r");
            // Читаем первую строку
            $firstLine = fgets($handle);
            // Закрываем файл
            fclose($handle);

            // send_json_response(['check' => 'offset_out', 'value' => $firstLine]);
            file_put_contents(__FILE__.'.log_12', print_r($offset_out,1));
            $max_offset = filesize($sql_backup_file);

            $completed = process_sql_chunk($sql_backup_file, $conn, $firstLine);

            if ($completed) {
                unset($file);
                send_json_response(['status' => 'completed', 'progress' => 100]);
            } else {
                $progress = ($offset_out / $max_offset) * 100;
                send_json_response(['status' => 'in_progress', 'progress' => [$firstLine, $max_offset]]);
            }

            // $sql_commands = splitStringWithQuotes($sql, ';');

            // file_put_contents(__FILE__.'.log', print_r($sql_commands, 1).PHP_EOL, FILE_APPEND);
            // file_put_contents(__FILE__.'_1.log', print_r('', 1).PHP_EOL);
            // foreach ($sql_commands as $command) {
            //     file_put_contents(__FILE__.'_1.log', print_r($command, 1).PHP_EOL, FILE_APPEND);
            //     file_put_contents(__FILE__.'_1.log', print_r($conn->error, 1).PHP_EOL, FILE_APPEND);
            //     $command = trim($command);
            //     if (!empty($command)) {
            //         if ($conn->query($command) === false) {
            //             send_json_response(['success' => false, 'message' => 'Error executing query: ' . $conn->error]);
            //             break 2; // Выход из цикла и case
            //         }
            //     }
            // }

            file_put_contents(__FILE__.'.log', print_r('5'.PHP_EOL, 1), FILE_APPEND);
        
            $conn->close();
            // send_json_response(['success' => true]);
            break;

        case 'restore_files':
            ini_set('max_execution_time', 0); // Без ограничения времени выполнения
            ini_set('memory_limit', '1024M'); // Увеличение лимита памяти
            $zip_backup_file = __DIR__.'/backup.zip';
            $restore_dir = dirname(__FILE__); // Восстановление в текущую папку

            $zip = new ZipArchive;
            // Открытие ZIP файла и извлечение содержимого
            if ($zip->open($zip_backup_file) === true) {
                $zip->extractTo($restore_dir);
                $zip->close();
                send_json_response(['success' => true]);
            } else {
                send_json_response(['success' => false, 'message' => 'Failed to open ZIP backup file.']);
            }
            break;
    }
}
?>
