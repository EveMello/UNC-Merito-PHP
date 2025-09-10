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
    $stmt = $pdo->query("SELECT id, nome, principio_ativo, data_validade, fabricante, quantidade, cod_barras, lote, custo, unidade_medida FROM medicamentos ORDER BY id DESC");
    echo json_encode($stmt->fetchAll());
    exit;
  }

  if ($method === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $pri  = trim($_POST['principio_ativo'] ?? '');
    $dtv = trim($_POST['data_validade'] ?? '');
    $fab  = trim($_POST['fabricante'] ?? '');
    $qtd  = trim($_POST['quantidade'] ?? '');
    $cob  = trim($_POST['cod_barras'] ?? '');
    $lot  = trim($_POST['lote'] ?? '');
    $cus  = trim($_POST['custo'] ?? '');
    $unm  = trim($_POST['unidade_medida'] ?? '');

    if ($nome === '' || $dtv === '' || $lot === '' || $unm === '') {
      http_response_code(422);
      echo json_encode(['error' => 'Campos obrigatórios não validados.']);
      exit;
    }

    $sql = "INSERT INTO medicamentos (nome, principio_ativo, data_validade, fabricante, quantidade, cod_barras, lote, custo, unidade_medida)
            VALUES (:nome, :pri, :dtv, :fab, :qtd, :cob, :lot, :cus, :unm)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':nome' => $nome,
      ':pri'  => $pri,
      ':dtv'=> $dtv,
      ':fab'  => $fab,
      ':qtd'  => $qtd,
      ':cob'  => $cob,
      ':lot'  => $lot,
      ':cus'  => $cus,
      ':unm'  => $unm
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
  }

  if ($method === 'PUT' || $method === 'PATCH') {
    $data = json_input();
    $id   = (int)($data['id'] ?? 0);
    $nome = trim($data['nome'] ?? '');
    $pri  = trim($data['principio_ativo'] ?? '');
    $dtv = trim($data['data_validade'] ?? '');
    $fab  = trim($data['fabricante'] ?? '');
    $qtd  = trim($data['quantidade'] ?? '');
    $cob  = trim($data['cod_barras'] ?? '');
    $lot  = trim($data['lote'] ?? '');
    $cus  = trim($data['custo'] ?? '');
    $unm  = trim($data['unidade_medida'] ?? '');

    if ($id <= 0 || $nome === '' || $dtv === '' || $lot === '' || $unm === '') {
      http_response_code(422);
      echo json_encode(['error' => 'Dados inválidos.']);
      exit;
    }

    $sql = "UPDATE medicamentos
            SET nome=:nome, principio_ativo=:pri, data_validade=:data_validade, fabricante=:fab, quantidade=:qtd, cod_barras=:cob, lote=:lot, custo=:cus, unidade_medida=:unm
            WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':nome' => $nome,
      ':pri'  => $pri,
      ':data_validade'=> $dtv,
      ':fab'  => $fab,
      ':qtd'  => $qtd,
      ':cob'  => $cob,
      ':lot'  => $lot,
      ':cus'  => $cus,
      ':unm'  => $unm
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
