<?php
// api/medicamentos.php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../config/db.php';

function json_input() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

$method = $_SERVER['REQUEST_METHOD'];

try {
  if ($method === 'GET') {
    $stmt = $pdo->query("SELECT id, nome, dosagem, forma, fabricante FROM medicamentos ORDER BY id DESC");
    echo json_encode($stmt->fetchAll());
    exit;
  }

  if ($method === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $dos  = trim($_POST['dosagem'] ?? '');
    $form = trim($_POST['forma'] ?? '');
    $fab  = trim($_POST['fabricante'] ?? '');

    if ($nome === '' || $dos === '' || $form === '' || $fab === '') {
      http_response_code(422);
      echo json_encode(['error' => 'Campos obrigatórios não informados.']);
      exit;
    }

    $sql = "INSERT INTO medicamentos (nome, dosagem, forma, fabricante)
            VALUES (:nome, :dos, :forma, :fab)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':nome' => $nome,
      ':dos'  => $dos,
      ':forma'=> $form,
      ':fab'  => $fab
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
  }

  if ($method === 'PUT' || $method === 'PATCH') {
    $data = json_input();
    $id   = (int)($data['id'] ?? 0);
    $nome = trim($data['nome'] ?? '');
    $dos  = trim($data['dosagem'] ?? '');
    $form = trim($data['forma'] ?? '');
    $fab  = trim($data['fabricante'] ?? '');

    if ($id <= 0 || $nome === '' || $dos === '' || $form === '' || $fab === '') {
      http_response_code(422);
      echo json_encode(['error' => 'Dados inválidos.']);
      exit;
    }

    $sql = "UPDATE medicamentos
            SET nome=:nome, dosagem=:dos, forma=:forma, fabricante=:fab
            WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':nome'=>$nome, ':dos'=>$dos, ':forma'=>$form, ':fab'=>$fab, ':id'=>$id
    ]);

    echo json_encode(['success' => true]);
    exit;
  }

  if ($method === 'DELETE') {
    parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
    $id = (int)($qs['id'] ?? 0);
    if ($id <= 0) {
      http_response_code(400);
      echo json_encode(['error' => 'ID inválido.']);
      exit;
    }

    $stmt = $pdo->prepare("DELETE FROM medicamentos WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['success' => true]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['error' => 'Método não permitido.']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Erro no servidor: '.$e->getMessage()]);
}
