<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'root';
$DATABASE_PASS = '';
$DATABASE_NAME = 'razor';
$conn = null;

try {
    $conn = new PDO("mysql:host=$DATABASE_HOST;dbname=$DATABASE_NAME", $DATABASE_USER, $DATABASE_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $e->getMessage()
    ]);
    exit();
}
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);
switch ($method) {
    case 'POST':
        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'login':
                    handleLogin($conn, $data);
                    break;
                case 'register':
                    handleRegistration($conn, $data);
                    break;
                case 'add_password':
                    handleAddPassword($conn, $data);
                    break;
                case 'get_passwords':
                    handleGetPasswords($conn, $data);
                    break;
                case 'update_password':
                    handleUpdatePassword($conn, $data);
                    break;
                case 'delete_password':
                    handleDeletePassword($conn, $data);
                    break;
                default:
                    echo json_encode([
                        "success" => false,
                        "message" => "Invalid action specified"
                    ]);
            }
        } else {
            echo json_encode([
                "success" => false,
                "message" => "No action specified"
            ]);
        }
        break;
    case 'GET':
        echo json_encode([
            "success" => true,
            "message" => "Razor Password Manager API is running"
        ]);
        break;
    default:
        echo json_encode([
            "success" => false,
            "message" => "Invalid request method"
        ]);
}

function handleLogin($conn, $data) {
    if (!isset($data['username']) || !isset($data['password'])) {
        echo json_encode([
            "success" => false,
            "message" => "Username and password are required"
        ]);
        return;
    }
    $username = trim($data['username']);
    $password = $data['password'];
    if (empty($username) || empty($password)) {
        echo json_encode([
            "success" => false,
            "message" => "Username and password cannot be empty"
        ]);
        return;
    }
    try {
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password_hash'])) {
                $token = bin2hex(random_bytes(32));
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['token'] = $token;
                echo json_encode([
                    "success" => true,
                    "message" => "Login successful",
                    "token" => $token,
                    "user" => [
                        "id" => $user['id'],
                        "username" => $user['username']
                    ]
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid username or password"
                ]);
            }
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Invalid username or password"
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            "success" => false,
            "message" => "Login failed: " . $e->getMessage()
        ]);
    }
}

function handleRegistration($conn, $data) {
    if (!isset($data['username']) || !isset($data['password'])) {
        echo json_encode([
            "success" => false,
            "message" => "Username and password are required"
        ]);
        return;
    }
    $username = trim($data['username']);
    $password = $data['password'];
    if (empty($username) || empty($password)) {
        echo json_encode([
            "success" => false,
            "message" => "Username and password cannot be empty"
        ]);
        return;
    }
    if (strlen($username) < 3 || strlen($username) > 50) {
        echo json_encode([
            "success" => false,
            "message" => "Username must be between 3 and 50 characters"
        ]);
        return;
    }
    if (strlen($password) < 8) {
        echo json_encode([
            "success" => false,
            "message" => "Password must be at least 8 characters long"
        ]);
        return;
    }
    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                "success" => false,
                "message" => "Username already exists"
            ]);
            return;
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
        $result = $stmt->execute([$username, $password_hash]);
        if ($result) {
            echo json_encode([
                "success" => true,
                "message" => "Registration successful",
                "user_id" => $conn->lastInsertId()
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Registration failed"
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            "success" => false,
            "message" => "Registration failed: " . $e->getMessage()
        ]);
    }
}

function handleAddPassword($conn, $data) {
    if (!isAuthenticated()) {
        echo json_encode([
            "success" => false,
            "message" => "Authentication required"
        ]);
        return;
    }
    if (!isset($data['website']) || !isset($data['username']) || !isset($data['password'])) {
        echo json_encode([
            "success" => false,
            "message" => "Website, username, and password are required"
        ]);
        return;
    }
    $user_id = $_SESSION['user_id'];
    $website = trim($data['website']);
    $username = trim($data['username']);
    $password = $data['password'];
    if (empty($website) || empty($username) || empty($password)) {
        echo json_encode([
            "success" => false,
            "message" => "Website, username, and password cannot be empty"
        ]);
        return;
    }
    try {
        $encrypted_password = encryptPassword($password);
        $stmt = $conn->prepare("INSERT INTO passwords (user_id, website, username, encrypted_password) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$user_id, $website, $username, $encrypted_password]);
        if ($result) {
            echo json_encode([
                "success" => true,
                "message" => "Password added successfully",
                "password_id" => $conn->lastInsertId()
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to add password"
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to add password: " . $e->getMessage()
        ]);
    }
}

function handleGetPasswords($conn, $data) {
    if (!isAuthenticated()) {
        echo json_encode([
            "success" => false,
            "message" => "Authentication required"
        ]);
        return;
    }
    $user_id = $_SESSION['user_id'];
    try {
        $stmt = $conn->prepare("SELECT id, website, username, encrypted_password, created_at FROM passwords WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $passwords = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $decrypted_password = decryptPassword($row['encrypted_password']);
            $passwords[] = [
                "id" => $row['id'],
                "website" => $row['website'],
                "username" => $row['username'],
                "password" => $decrypted_password,
                "created_at" => $row['created_at']
            ];
        }
        echo json_encode([
            "success" => true,
            "passwords" => $passwords
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to retrieve passwords: " . $e->getMessage()
        ]);
    }
}

function handleUpdatePassword($conn, $data) {
    if (!isAuthenticated()) {
        echo json_encode([
            "success" => false,
            "message" => "Authentication required"
        ]);
        return;
    }
    if (!isset($data['id']) || !isset($data['website']) || !isset($data['username']) || !isset($data['password'])) {
        echo json_encode([
            "success" => false,
            "message" => "Password ID, website, username, and password are required"
        ]);
        return;
    }
    $user_id = $_SESSION['user_id'];
    $password_id = $data['id'];
    $website = trim($data['website']);
    $username = trim($data['username']);
    $password = $data['password'];
    if (empty($website) || empty($username) || empty($password)) {
        echo json_encode([
            "success" => false,
            "message" => "Website, username, and password cannot be empty"
        ]);
        return;
    }
    try {
        $stmt = $conn->prepare("SELECT id FROM passwords WHERE id = ? AND user_id = ?");
        $stmt->execute([$password_id, $user_id]);
        if ($stmt->rowCount() === 0) {
            echo json_encode([
                "success" => false,
                "message" => "Password not found or you don't have permission to update it"
            ]);
            return;
        }
        $encrypted_password = encryptPassword($password);
        $stmt = $conn->prepare("UPDATE passwords SET website = ?, username = ?, encrypted_password = ? WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$website, $username, $encrypted_password, $password_id, $user_id]);
        if ($result) {
            echo json_encode([
                "success" => true,
                "message" => "Password updated successfully"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to update password"
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to update password: " . $e->getMessage()
        ]);
    }
}

function handleDeletePassword($conn, $data) {
    if (!isAuthenticated()) {
        echo json_encode([
            "success" => false,
            "message" => "Authentication required"
        ]);
        return;
    }
    if (!isset($data['id'])) {
        echo json_encode([
            "success" => false,
            "message" => "Password ID is required"
        ]);
        return;
    }
    $user_id = $_SESSION['user_id'];
    $password_id = $data['id'];
    try {
        $stmt = $conn->prepare("SELECT id FROM passwords WHERE id = ? AND user_id = ?");
        $stmt->execute([$password_id, $user_id]);
        if ($stmt->rowCount() === 0) {
            echo json_encode([
                "success" => false,
                "message" => "Password not found or you don't have permission to delete it"
            ]);
            return;
        }
        $stmt = $conn->prepare("DELETE FROM passwords WHERE id = ? AND user_id = ?"); // delete the password
        $result = $stmt->execute([$password_id, $user_id]);
        if ($result) {
            echo json_encode([
                "success" => true,
                "message" => "Password deleted successfully"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to delete password"
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to delete password: " . $e->getMessage()
        ]);
    }
}

function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['token']);
}

function encryptPassword($password) {
    $encryption_key = "your_secure_encryption_key";
    $method = "AES-256-CBC";
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
    $encrypted = openssl_encrypt($password, $method, $encryption_key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decryptPassword($encrypted_password) {
    $encryption_key = "your_secure_encryption_key"; // NOTE that this is an insecure placeholder key meant for VCS commits, actual production code will need to be changed
    $method = "AES-256-CBC";
    list($encrypted_data, $iv) = explode('::', base64_decode($encrypted_password), 2);
    return openssl_decrypt($encrypted_data, $method, $encryption_key, 0, $iv);
}
?>