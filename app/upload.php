<?php
require_once 'vendor/autoload.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

// Create a logger instance
$log = new Logger('upload');
// Define the log file path
$logFile = __DIR__ . '/logs/novelist-app.log';
// Add a handler to write logs to the specified file
$log->pushHandler(new StreamHandler($logFile, Level::Debug));

session_start();
require_once 'config.php';
require_once 'csrf.php';
if (!isset($_SESSION['user_id'])) {
    $log->warning('Unauthenticated user attempted to upload a novel.', ['ip' => $_SERVER['REMOTE_ADDR']]);
    // Redirect to login if not authenticated
    $_SESSION['error_message'] = "You must log in first.";
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['token_csrf']) || !verifyToken($_POST['token_csrf'])) {
        $log->warning('csrf token error', ['ip' => $_SERVER['REMOTE_ADDR']]);
        $_SESSION['error_message'] = "Something went wrong";
        $_SESSION['type'] = "error";  
    
        header('Location: dashboard.php');  
        exit();
    }    
    
    $title = htmlspecialchars(trim($_POST['title']), ENT_QUOTES, 'UTF-8');
    $type = htmlspecialchars(trim($_POST['type']), ENT_QUOTES, 'UTF-8');
    $content = isset($_POST['content']) ? htmlspecialchars(trim($_POST['content']), ENT_QUOTES, 'UTF-8') : null;

    $is_premium = isset($_POST['is_premium']) ? 1 : 0;
    $author_id = $_SESSION['user_id'];

    if (empty($title) || empty($type)) {
        $_SESSION['error_message'] = "Title and type required";
        $_SESSION['type'] = "error";  
    
        header('Location: dashboard.php');  
        exit();
    }

    $file_path = null;
    if ($type === 'full' && isset($_FILES['file'])) {
       
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $log->warning('File is too large.', ['size' => $_FILES['file']['size']]);
                $_SESSION['error_message'] = "File is too large. Maximum size is 2MB.";
                $_SESSION['type'] = "error";  
            
                header('Location: dashboard.php');  
                exit();
            case UPLOAD_ERR_PARTIAL:
                $log->warning('The file was only partially uploaded.');
                $_SESSION['error_message'] = "The file was only partially uploaded.";
                $_SESSION['type'] = "error";  
            
                header('Location: dashboard.php');  
                exit();
            case UPLOAD_ERR_NO_FILE:
                $log->warning('No file was uploaded.');
                $_SESSION['error_message'] = "No file was uploaded.";
                $_SESSION['type'] = "error";  
            
                header('Location: dashboard.php');  
                exit();
            default:
                $log->error('An unknown error occurred during file upload.');
                $_SESSION['error_message'] = "An unknown error occurred during file upload..";
                $_SESSION['type'] = "error";  
            
                header('Location: dashboard.php'); 
                exit();
        }
        
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                $log->error('Unable to create upload directory. Check permissions.');
                $_SESSION['error_message']='Unable to create upload directory. Check permissions';
                $_SESSION['type'] = "error";  
                header('Location: dashboard.php');  
                exit();
            }
        }

        
        $allowed_extensions = ['pdf'];
        $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_extensions)) {
            $log->warning('Invalid file extension.', ['extension' => $file_ext, 'ip' => $_SERVER['REMOTE_ADDR']]);
            $_SESSION['error_message']='Invalid file type. Only PDF files are allowed.';
            $_SESSION['type'] = "error";  
            header('Location: dashboard.php');  
            exit();
        }

       
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($_FILES['file']['tmp_name']);
        $allowed_mime_types = ['application/pdf'];

        if (!in_array($mime_type, $allowed_mime_types)) {
            $log->warning('Invalid file type.', ['type' => $mime_type, 'ip' => $_SERVER['REMOTE_ADDR']]);
            $_SESSION['error_message']='Invalid file type. Only PDF files are allowed.';
            $_SESSION['type'] = "error"; 
            header('Location: dashboard.php');  
            exit();
        }

       
        $max_size = 2 * 1024 * 1024; // 2MB
        if ($_FILES['file']['size'] > $max_size) {
            $log->warning('File is too large.', ['size' => $_FILES['file']['size']]);
        
            $_SESSION['error_message'] = "File is too large. Maximum size is 2MB.";
            $_SESSION['type'] = "error";  
        
            header('Location: dashboard.php');  
            exit();
        }
        

        $file_name = uniqid() . '_' . basename($_FILES['file']['name']);
        $file_path = $upload_dir . $file_name;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
            $log->error('File upload failed. Check folder permissions.');
            $_SESSION['error_message'] = "File upload failed! Check folder permissions..";
            $_SESSION['type'] = "error"; 
            header('Location: dashboard.php');  
            exit();
        }

        $file_path = 'uploads/' . $file_name;
    }
    else if($type === 'short' && $content=== ""){
        $_SESSION['error_message'] = "Empty Content.";
        $_SESSION['type'] = "error"; 
    
        header('Location: dashboard.php');  
        exit();
    }

    $stmt = $conn->prepare('INSERT INTO Novels (author_id, title, type, content, file_path, is_premium) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issssi', $author_id, $title, $type, $content, $file_path, $is_premium);

    if ($stmt->execute()) {
        $log->info('Novel uploaded successfully.', [
            'author_id' => $author_id, 
            'title' => $title, 
            'username' => $_SESSION['username'], 
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
        header('Location: dashboard.php');
        exit();
    } else {
        $log->error('Error in db query occurred.', ['error' => $stmt->error]);
        echo 'Error: ' . $stmt->error;
    }

    $stmt->close();
}

$conn->close();

?>
