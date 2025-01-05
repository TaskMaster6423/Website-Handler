<?php
// Directory to scan for HTML and PHP files
$directory = __DIR__ . DIRECTORY_SEPARATOR . 'pages';

if (!file_exists($directory)) {
    mkdir($directory); // Create the directory if it doesn't exist
}

// Scan the directory for HTML and PHP files
$files = array_diff(scandir($directory), ['..', '.']);
$files = array_filter($files, function ($file) use ($directory) {
    return is_file($directory . DIRECTORY_SEPARATOR . $file) && preg_match('/\.(html|php)$/', $file);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Previews</title>
    <style>
        /* General Styles */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            overflow: hidden;
        }
        h1 {
            text-align: center;
            margin: 20px 0;
            font-size: 24px;
            color: #007BFF;
        }
        .container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            padding: 20px;
        }
        .card {
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
        iframe {
            width: 100%;
            height: 200px;
            border: none;
        }
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background-color: #f9f9f9;
        }
        .button {
            background-color: #007BFF;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }
        .button:hover {
            background-color: #0056b3;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: -260px;
            width: 250px;
            height: 100%;
            background-color: #007BFF;
            color: white;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
            transition: left 0.3s ease;
            z-index: 1000;
        }
        .sidebar.open {
            left: 0;
        }
        .sidebar h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: white;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        .sidebar ul li {
            margin: 10px 0;
        }
        .sidebar ul li a {
            color: white;
            text-decoration: none;
            font-size: 14px;
        }
        .sidebar ul li a:hover {
            text-decoration: underline;
        }

        /* Toggle Button Styles */
        .menu-toggle {
            position: fixed;
            top: 20px;
            left: 45px;
            width: 40px;
            height: 40px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
            z-index: 1100;
            transition: transform 0.3s ease;
        }
        .menu-toggle.open {
            transform: translateX(250px);
        }
        .menu-toggle.open::after {
            content: '←';
        }
        .menu-toggle::after {
            content: '☰';
        }
        .menu-toggle:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <h2>Pages List</h2>
        <ul>
            <?php foreach ($files as $file): ?>
                <li><a href="fullscreen.php?file=<?= urlencode($file) ?>"><?= htmlspecialchars($file) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()"></button>
    <h1>Page Previews</h1>
    <div class="container" id="previewContainer">
        <?php foreach ($files as $file): ?>
            <div class="card" id="preview-<?= htmlspecialchars($file) ?>">
                <iframe src="pages/<?= htmlspecialchars($file) ?>?t=<?= time() ?>" title="<?= htmlspecialchars($file) ?>"></iframe>
                <div class="card-footer">
                    <span><?= htmlspecialchars($file) ?></span>
                    <a class="button" href="fullscreen.php?file=<?= urlencode($file) ?>">Open</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            sidebar.classList.toggle('open');
            menuToggle.classList.toggle('open');
        }

        function refreshPreviews() {
            const container = document.getElementById('previewContainer');
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContainer = doc.getElementById('previewContainer');
                    container.innerHTML = newContainer.innerHTML;

                    // Re-attach event listeners if necessary
                })
                .catch(error => console.error('Error refreshing previews:', error));
        }

        // Refresh previews every 10 seconds
        setInterval(refreshPreviews, 10000);
    </script>
</body>
</html>
