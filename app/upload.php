<?php
session_start();
require_once 'config.php';
require_once 'csrf.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['token_csrf']) || !verifyToken($_POST['token_csrf'])) {
        die("Something went wrong");
    }    
    
    $title = htmlspecialchars(trim($_POST['title']), ENT_QUOTES, 'UTF-8');
    $type = htmlspecialchars(trim($_POST['type']), ENT_QUOTES, 'UTF-8');
    $content = isset($_POST['content']) ? htmlspecialchars(trim($_POST['content']), ENT_QUOTES, 'UTF-8') : null;

    $is_premium = isset($_POST['is_premium']) ? 1 : 0;
    $author_id = $_SESSION['user_id'];

    if (empty($title) || empty($type)) {
        die('Title and type are required!');
    }

    $file_path = null;
    if ($type === 'full' && isset($_FILES['file'])) {
        // Controllo errori di upload
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                die('File is too large. Maximum size is 2MB.');
            case UPLOAD_ERR_PARTIAL:
                die('The file was only partially uploaded.');
            case UPLOAD_ERR_NO_FILE:
                die('No file was uploaded.');
            default:
                die('An unknown error occurred during file upload.');
        }
        
        // **2️ Creazione sicura della cartella uploads**
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                die('Error: Unable to create upload directory. Check permissions.');
            }
        }

        // **3️ Verifica dell'estensione del file**
        $allowed_extensions = ['pdf'];
        $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_extensions)) {
            die('Invalid file extension. Only PDF files are allowed.');
        }

        // **4️ Verifica del MIME Type**
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($_FILES['file']['tmp_name']);
        $allowed_mime_types = ['application/pdf'];

        if (!in_array($mime_type, $allowed_mime_types)) {
            die('Invalid file type. Only PDF files are allowed.');
        }

        // **5️ Limitazione della dimensione del file (max 2MB)**
        $max_size = 2 * 1024 * 1024; // 2MB
        if ($_FILES['file']['size'] > $max_size) {
            die('File is too large. Maximum size is 2MB.');
        }

        // **6️ Generazione di un nome file univoco**
        $file_name = uniqid() . '_' . basename($_FILES['file']['name']);
        $file_path = $upload_dir . $file_name;

        // **7️ Spostamento sicuro del file**
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
            die('File upload failed! Check folder permissions.');
        }

        // **8️ Convertiamo il percorso in relativo per evitare esposizione del filesystem**
        $file_path = 'uploads/' . $file_name;
    }

    // **9️ Inserimento nel database**
    $stmt = $conn->prepare('INSERT INTO Novels (author_id, title, type, content, file_path, is_premium) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issssi', $author_id, $title, $type, $content, $file_path, $is_premium);

    if ($stmt->execute()) {
        header('Location: dashboard.php');
        exit();
    } else {
        echo 'Error: ' . $stmt->error;
    }

    $stmt->close();
}

$conn->close();

?>
