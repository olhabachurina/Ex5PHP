<?php


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Получение и очистка данных из формы
        $drive = filter_input(INPUT_POST, 'drive', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $fileMask = filter_input(INPUT_POST, 'file_mask', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $searchText = filter_input(INPUT_POST, 'search_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

        //для пагинации
        $page = filter_input(INPUT_POST, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
        $filesPerPage = filter_input(INPUT_POST, 'files_per_page', FILTER_VALIDATE_INT, ['options' => ['default' => 10, 'min_range' => 1]]);

        // Проверка на обязательные поля
        if (empty($drive) || empty($fileMask)) {
            throw new Exception('Поля "Диск" и "Маска файлов" обязательны для заполнения.');
        }

        // Ограничение доступа к определенным дискам
        $allowedDrives = ['C:\\', 'D:\\', 'E:\\'];
        if (!in_array($drive, $allowedDrives, true)) {
            throw new Exception('Доступ к выбранному диску запрещен.');
        }

        // Преобразование маски файла в регулярное выражение
        $regexMask = '/' . str_replace(['\*', '\?'], ['.*', '.'], preg_quote($fileMask, '/')) . '$/i';

        // Функция для поиска файлов только в корневой директории
        function getFilesInRootDirectory($dir, $pattern) {
            $files = [];
            $realDir = realpath($dir);
            if ($realDir === false || !is_readable($realDir)) {
                throw new Exception("Нет доступа к директории $dir.");
            }

            $handle = opendir($realDir);
            if ($handle === false) {
                throw new Exception("Не удалось открыть директорию $dir.");
            }

            while (($file = readdir($handle)) !== false) {
                $filePath = $realDir . DIRECTORY_SEPARATOR . $file;
                if ($file == '.' || $file == '..') continue;
                if (is_file($filePath) && preg_match($pattern, $file)) {
                    $files[] = $filePath;
                }
            }
            closedir($handle);
            return $files;
        }

        // Получение всех подходящих файлов только в корневом каталоге
        $allFilesFound = getFilesInRootDirectory($drive, $regexMask);

        // Пагинация
        $totalFiles = count($allFilesFound);
        $totalPages = ceil($totalFiles / $filesPerPage);
        $offset = ($page - 1) * $filesPerPage;
        $filesOnPage = array_slice($allFilesFound, $offset, $filesPerPage);

        $results = [];

        // Функция для поиска текста в файле
        function searchInFile($filePath, $searchText) {
            $positions = [];
            $lineNumber = 1;

            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                throw new Exception("Не удалось открыть файл: $filePath");
            }

            while (($line = fgets($handle)) !== false) {
                $pos = 0;
                while (($pos = strpos($line, $searchText, $pos)) !== false) {
                    $positions[] = "Строка: $lineNumber, Позиция: $pos";
                    $pos++;
                }
                $lineNumber++;
            }
            fclose($handle);

            return $positions;
        }

        // Поиск текста в файлах (если указано)
        foreach ($filesOnPage as $file) {
            if (!empty($searchText)) {
                $positions = searchInFile($file, $searchText);
                if (!empty($positions)) {
                    $results[] = [
                        'file' => $file,
                        'positions' => $positions
                    ];
                }
            } else {
                $results[] = ['file' => $file, 'positions' => []];
            }
        }

        // Формируем JSON-ответ
        header('Content-Type: application/json');
        echo json_encode([
            'results' => $results,
            'searchText' => !empty($searchText),
            'pagination' => [
                'currentPage' => $page,
                'filesPerPage' => $filesPerPage,
                'totalFiles' => $totalFiles,
                'totalPages' => $totalPages,
            ]
        ]);
    } catch (Exception $e) {
        error_log("Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
