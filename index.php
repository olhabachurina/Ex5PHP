<<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Поиск файлов и текста</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-image: url('image.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
            color: #fff;
        }

        h1 {
            text-align: center;
            padding: 20px;
            margin: 0;
            background-color: #007bff;
            color: #fff;
        }

        .container {
            max-width: 960px;
            margin: 20px auto;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 10px;
            padding: 20px;
        }

        .search-form {
            background-color: transparent;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .search-form label span {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .search-form input[type="text"],
        .search-form select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .search-form input[type="text"]:focus,
        .search-form select:focus {
            border-color: #007bff;
            outline: none;
        }

        .search-form button {
            display: block;
            width: 100%;
            padding: 12px 0;
            background-color: #28a745;
            border: none;
            border-radius: 4px;
            color: #fff;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .search-form button:hover {
            background-color: #218838;
        }

        #loading {
            display: none;
            text-align: center;
            margin: 30px 0;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 6px solid rgba(0,0,0,0.1);
            border-top-color: #007bff;
            border-radius: 50%;
            animation: spin 1s infinite linear;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        #results {
            margin-top: 20px;
            color: #fff;
        }

        .result-item {
            background-color: rgba(0, 0, 0, 0.7);
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
            color: #fff;
        }

        .result-item h3 {
            margin: 0 0 10px;
            font-size: 20px;
            color: #00d1b2;
            word-break: break-all;
        }

        .positions-list {
            list-style-type: disc;
            padding-left: 20px;
        }

        .pagination-button {
            margin: 5px;
            padding: 8px 12px;
            border: 1px solid #007bff;
            background-color: transparent;
            color: #00d1b2;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .pagination-button.active {
            background-color: #007bff;
            color: #fff;
            border-color: #007bff;
            cursor: default;
        }
    </style>
</head>
<body>
<h1><i class="fas fa-search"></i> Поиск файлов и текста</h1>

<<div class="container">
    <form id="searchForm" class="search-form">
        <label>
            <span><i class="fas fa-hdd"></i> Select Drive:</span>
            <select name="drive">
                <?php
                foreach (range('C', 'Z') as $driveLetter) {
                    if (is_dir($driveLetter . ':\\')) {
                        echo "<option value='{$driveLetter}:\'>{$driveLetter}:</option>";
                    }
                }
                ?>
            </select>
        </label>
        <label>
            <span><i class="fas fa-file"></i> File Mask:</span>
            <input type="text" name="file_mask" required placeholder="Example: *.txt">
        </label>
        <label>
            <span><i class="fas fa-font"></i> Text to Search:</span>
            <input type="text" name="search_text" placeholder="Enter text to search (optional)">
        </label>
        <button type="submit"><i class="fas fa-search"></i> Search</button>
    </form>

    <div id="loading">
        <div class="spinner"></div>
        <p>Searching, please wait...</p>
    </div>

    <div id="results"></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                fetchResults();
            });
        }
    });

    function fetchResults() {
        const form = document.getElementById('searchForm');
        const resultsDiv = document.getElementById('results');
        const loadingDiv = document.getElementById('loading');

        if (!form || !resultsDiv || !loadingDiv) {
            console.error("Не удалось найти необходимые элементы на странице.");
            return;
        }

        const formData = new FormData(form);

        // Очистка результатов и отображение индикатора загрузки
        resultsDiv.innerHTML = '';
        loadingDiv.style.display = 'block';

        fetch('search.php', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                // Проверка, что ответ является JSON
                if (!response.ok) {
                    throw new Error(`Ошибка сети: ${response.status} - ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                loadingDiv.style.display = 'none';
                if (data.error) {
                    // Отображение сообщения об ошибке
                    resultsDiv.innerHTML = `<p class="message error-message">Ошибка: ${data.error}</p>`;
                } else {
                    displayResults(data.results, data.searchText);
                }
            })
            .catch(error => {
                loadingDiv.style.display = 'none';
                resultsDiv.innerHTML = `<p class="message error-message">Произошла ошибка: ${error.message}</p>`;
                console.error('Ошибка запроса:', error);
            });
    }

    function displayResults(results, searchText) {
        const resultsDiv = document.getElementById('results');
        resultsDiv.innerHTML = '';

        if (results && results.length > 0) {
            results.forEach(result => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'result-item';
                itemDiv.innerHTML = `<h3><i class="fas fa-file-alt"></i> ${result.file}</h3>`;

                // Если есть текст поиска и позиции
                if (searchText && result.positions && result.positions.length > 0) {
                    const positionsList = document.createElement('ul');
                    positionsList.className = 'positions-list';
                    result.positions.forEach(pos => {
                        const posItem = document.createElement('li');
                        posItem.textContent = pos;
                        positionsList.appendChild(posItem);
                    });
                    itemDiv.appendChild(positionsList);
                }
                resultsDiv.appendChild(itemDiv);
            });
        } else {
            resultsDiv.innerHTML = '<p class="message">Результаты не найдены.</p>';
        }
    }
</script>
</body>
</html>