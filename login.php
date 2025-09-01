 <?php
 ini_set('display_errors', 1);
 ini_set('display_startup_errors', 1);
 error_reporting(E_ALL);
 
+$lifetime = 60 * 60 * 24 * 30; // 30 dias
+session_set_cookie_params([
+    'lifetime' => $lifetime,
+    'path'     => '/',
+    'httponly' => true,
+    'samesite' => 'Lax'
+]);
+ini_set('session.gc_maxlifetime', $lifetime);
 session_start();
 require_once 'conexao.php';
 
 if (empty($_POST['email']) || empty($_POST['senha'])) {
     header('Content-Type: application/json');
     echo json_encode(['success' => false, 'message' => 'Preencha email e senha.']);
     exit;
 }
 
 $email = $_POST['email'];
 $senha = $_POST['senha'];
 
 $stmt = $conn->prepare("SELECT id, nome, senha_hash, is_admin FROM usuarios WHERE email = ?");
 if (!$stmt) {
     header('Content-Type: application/json');
     echo json_encode(['success' => false, 'message' => 'Erro no prepare: ' . $conn->error]);
     exit;
 }
 $stmt->bind_param("s", $email);
 
 if (!$stmt->execute()) {
     header('Content-Type: application/json');
     echo json_encode(['success' => false, 'message' => 'Erro ao executar: ' . $stmt->error]);
     exit;
 }
