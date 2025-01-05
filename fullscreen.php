<?php
$file = isset($_GET['file']) ? htmlspecialchars($_GET['file']) : null;

// Check if file exists and is valid
$directory = __DIR__ . DIRECTORY_SEPARATOR . 'pages';
$filePath = $directory . DIRECTORY_SEPARATOR . $file;

if (!$file || !preg_match('/\.(html|php)$/', $file) || !file_exists($filePath)) {
    die('Invalid or missing file.');
}

// Process POST requests for adding, updating, or removing elements
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tag = $_POST['tag'] ?? '';
    $content = $_POST['content'] ?? '';
    $currentContent = $_POST['currentContent'] ?? '';

    if ($action === 'remove' && $tag) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(file_get_contents($filePath), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        foreach ($dom->getElementsByTagName($tag) as $node) {
            if ($tag === 'img' && $node->getAttribute('src') === $content) {
                $node->parentNode->removeChild($node);
                break;
            } elseif (trim($node->nodeValue) === trim($currentContent)) {
                $node->parentNode->removeChild($node);
                break;
            }
        }

        file_put_contents($filePath, $dom->saveHTML());
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'add' && $tag) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(file_get_contents($filePath), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $newElement = $dom->createElement($tag, $content);

        if ($tag === 'img') {
            $src = $_POST['src'] ?? '';
            $alt = $_POST['alt'] ?? '';
            $newElement = $dom->createElement('img');
            $newElement->setAttribute('src', $src);
            $newElement->setAttribute('alt', $alt);
        }

        $dom->getElementsByTagName('body')->item(0)->appendChild($newElement);
        file_put_contents($filePath, $dom->saveHTML());
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'update' && $tag && $content && $currentContent) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(file_get_contents($filePath), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        foreach ($dom->getElementsByTagName($tag) as $node) {
            if (trim($node->nodeValue) === trim($currentContent)) {
                $node->nodeValue = $content;
                break;
            }
        }

        file_put_contents($filePath, $dom->saveHTML());
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false]);
    exit;
}

// Load the content of the file
$content = file_get_contents($filePath);

// Initialize the DOMDocument and parse the HTML
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$tags = [];

if ($content && @$dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
    foreach ($dom->getElementsByTagName('*') as $node) {
        if (!in_array($node->nodeName, ['html', 'head', 'script', 'style', 'meta', 'link'])) {
            $tags[] = [
                'tag' => $node->nodeName,
                'content' => trim($node->nodeValue),
                'attributes' => iterator_to_array($node->attributes ?? []),
            ];
        }
    }
}
libxml_clear_errors();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fullscreen View: <?= htmlspecialchars($file) ?></title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
            overflow: hidden;
            background-color: #f4f4f9;
        }

        /* Side Navigation Styles */
        .side-nav {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
            overflow-y: auto;
        }

        .side-nav h2 {
            margin: 0 0 15px;
            font-size: 20px;
            text-transform: uppercase;
            color: #ecf0f1;
            border-bottom: 1px solid #34495e;
            padding-bottom: 10px;
        }

        .side-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .side-nav ul li {
            position: relative;
            margin-bottom: 15px;
        }

        .side-nav ul li .element-name {
            display: block;
            padding: 10px;
            background-color: #34495e;
            border-radius: 4px;
            font-size: 14px;
            color: white;
            cursor: pointer;
            text-align: left;
        }

        .side-nav ul li .menu-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #007BFF;
            border: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            padding: 5px 10px;
            font-size: 12px;
        }

        .side-nav ul li .menu-button:hover {
            background-color: #0056b3;
        }

        .side-nav ul li .dropdown-menu {
            display: none;
            position: absolute;
            left: 0;
            top: 100%;
            background-color: white;
            color: black;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 4px;
            z-index: 1000;
            padding: 10px 0;
            width: 100%;
        }

        .side-nav ul li .dropdown-menu.active {
            display: block;
        }

        .side-nav ul li .dropdown-menu button {
            background-color: transparent;
            border: none;
            padding: 10px 15px;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-size: 14px;
        }

        .side-nav ul li .dropdown-menu button:hover {
            background-color: #f4f4f9;
        }

        .add-element {
            margin-top: 20px;
            text-align: center;
        }

        .add-element button {
            background-color: #28a745;
            border: none;
            color: white;
            font-size: 16px;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .add-element button:hover {
            background-color: #218838;
        }

        /* Iframe Styles */
        iframe {
            flex: 1;
            border: none;
            height: 100%;
            width: calc(100% - 250px);
            background-color: white;
        }

        /* Popup Form Styles */
        .popup-form {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            z-index: 2000;
            padding: 20px;
            width: 400px;
            text-align: center;
        }

        .popup-form h3 {
            margin-bottom: 15px;
        }

        .popup-form .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .popup-form .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .popup-form .form-group input,
        .popup-form .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .popup-form .form-group button {
            margin-top: 10px;
        }

        .popup-form .buttons button {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            color: white;
            margin: 5px;
        }

        .popup-form .buttons .add-btn {
            background-color: #007BFF;
        }

        .popup-form .buttons .add-btn:hover {
            background-color: #0056b3;
        }

        .popup-form .buttons .cancel-btn {
            background-color: #d9534f;
        }

        .popup-form .buttons .cancel-btn:hover {
            background-color: #c9302c;
        }

        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1999;
        }
    </style>
</head>
<body>
    <div class="popup-overlay" id="popupOverlay" onclick="hideAddForm()"></div>
    <div class="popup-form" id="addForm">
        <h3>Add New Element</h3>
        <form id="addElementForm">
            <div class="form-group">
                <label for="newTag">Element Type</label>
                <select id="newTag" onchange="toggleImageFields()">
                    <option value="div">div</option>
                    <option value="p">p</option>
                    <option value="h1">h1</option>
                    <option value="img">img</option>
                </select>
            </div>
            <div class="form-group" id="textFields">
                <label for="newContent">Content</label>
                <input type="text" id="newContent" placeholder="Enter content">
            </div>
            <div class="form-group" id="imageFields" style="display: none;">
                <label for="newImageSrc">Image URL</label>
                <input type="text" id="newImageSrc" placeholder="Enter image URL">
                <label for="newImageAlt">Alt Text</label>
                <input type="text" id="newImageAlt" placeholder="Enter alt text">
            </div>
            <div class="buttons">
                <button type="button" class="add-btn" onclick="addElement()">Add</button>
                <button type="button" class="cancel-btn" onclick="hideAddForm()">Cancel</button>
            </div>
        </form>
    </div>
    <div class="side-nav">
        <h2>Page Elements</h2>
        <ul>
            <?php foreach ($tags as $index => $tag): ?>
                <?php if (in_array($tag['tag'], ['a', 'img', 'div', 'p', 'h1', 'h2'])): ?>
                    <li>
                        <span class="element-name"><?= htmlspecialchars($tag['tag']) ?></span>
                        <button class="menu-button" onclick="toggleMenu(<?= $index ?>)">⋮</button>
                        <div class="dropdown-menu" id="menu-<?= $index ?>">
                            <button onclick="convertToHyperlink('<?= htmlspecialchars($tag['tag']) ?>', '<?= htmlspecialchars($tag['content']) ?>')">Convert to Hyperlink</button>
                            <button onclick="changeContent('<?= htmlspecialchars($tag['tag']) ?>', '<?= htmlspecialchars($tag['content']) ?>')">Change Content</button>
                            <button onclick="removeElement('<?= htmlspecialchars($tag['tag']) ?>', '<?= htmlspecialchars($tag['content']) ?>')">Remove Element</button>
                        </div>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <div class="add-element">
            <button onclick="showAddForm()">+ Add New Element</button>
        </div>
    </div>
    <iframe src="pages/<?= htmlspecialchars($file) ?>?t=<?= time() ?>" id="iframe"></iframe>

    <script>
        function toggleMenu(index) {
            const menu = document.getElementById(`menu-${index}`);
            menu.classList.toggle('active');
        }

        function toggleImageFields() {
            const tag = document.getElementById('newTag').value;
            const textFields = document.getElementById('textFields');
            const imageFields = document.getElementById('imageFields');
            if (tag === 'img') {
                textFields.style.display = 'none';
                imageFields.style.display = 'block';
            } else {
                textFields.style.display = 'block';
                imageFields.style.display = 'none';
            }
        }

        function showAddForm() {
            document.getElementById('addForm').style.display = 'block';
            document.getElementById('popupOverlay').style.display = 'block';
        }

        function hideAddForm() {
            document.getElementById('addForm').style.display = 'none';
            document.getElementById('popupOverlay').style.display = 'none';
        }

        function addElement() {
            const newTag = document.getElementById('newTag').value;
            let content = document.getElementById('newContent').value;
            const src = document.getElementById('newImageSrc').value;
            const alt = document.getElementById('newImageAlt').value;

            if (newTag === 'img') {
                content = src; // Use image URL as content for the img tag
            }

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&tag=${encodeURIComponent(newTag)}&content=${encodeURIComponent(content)}&src=${encodeURIComponent(src)}&alt=${encodeURIComponent(alt)}`,
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Element added successfully!');
                        window.location.reload();
                    } else {
                        alert('Failed to add element.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred.');
                });
        }

        function removeElement(tag, content) {
            if (confirm(`Are you sure you want to remove this <${tag}> element?`)) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove&tag=${encodeURIComponent(tag)}&content=${encodeURIComponent(content)}`,
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Element removed successfully!');
                            window.location.reload();
                        } else {
                            alert('Failed to remove element.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred.');
                    });
            }
        }

        function convertToHyperlink(tag, content) {
            const href = prompt('Enter URL to convert this element into a hyperlink:');
            if (href) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=add&tag=a&content=${encodeURIComponent(content)}&href=${encodeURIComponent(href)}`,
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Element converted to hyperlink successfully!');
                            window.location.reload();
                        } else {
                            alert('Failed to convert to hyperlink.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred.');
                    });
            }
        }

        function changeContent(tag, content) {
            const newContent = prompt(`Enter new content for <${tag}> element:`, content);
            if (newContent) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update&tag=${encodeURIComponent(tag)}&currentContent=${encodeURIComponent(content)}&content=${encodeURIComponent(newContent)}`,
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Content updated successfully!');
                            window.location.reload();
                        } else {
                            alert('Failed to update content.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred.');
                    });
            }
        }
    </script>
</body>
</html>
