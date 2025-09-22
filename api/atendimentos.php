<?php
// api/atendimentos.php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

function json_input() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function save_upload($field, $destDir) {
  if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
  if (!is_dir($destDir)) { @mkdir($destDir, 0775, true); }
  $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
  $safe = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', pathinfo($_FILES[$field]['name'], PATHINFO_FILENAME));
  $fname = $safe . '_' . time() . ($ext ? '.'.$ext : '');
  $target = rtrim($destDir,'/\\') . DIRECTORY_SEPARATOR . $fname;
  if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) return null;
  return 'uploads/atendimentos/' . $fname;
}

try {
  // GET por id
  if ($method === 'GET' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("
        SELECT a.id, a.paciente_id, p.nome AS paciente,
               a.medico_id, m.nome AS profissional,
               a.tipo, a.status, DATE_FORMAT(a.data_atendimento,'%Y-%m-%d') AS data_atendimento,
               a.diagnostico, a.cid, a.observacoes, a.anexo
        FROM atendimentos a
        INNER JOIN pacientes p ON p.id = a.paciente_id
        INNER JOIN medicos m ON m.id = a.medico_id
        WHERE a.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $atendimento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$atendimento) {
        http_response_code(404);
        echo json_encode(['error' => 'Atendimento não encontrado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode($atendimento, JSON_UNESCAPED_UNICODE);
    exit;
  }

  // GET (lista)
  if ($method === 'GET') {
      $stmt = $pdo->query("
          SELECT a.id, p.nome as paciente, m.nome as profissional, 
                 a.tipo, a.status, DATE_FORMAT(a.data_atendimento,'%Y-%m-%d') AS data_atendimento, 
                 a.diagnostico, a.cid, a.observacoes, a.anexo 
          FROM atendimentos a
          INNER JOIN pacientes p ON p.id = a.paciente_id
          INNER JOIN medicos m ON m.id = a.medico_id
          ORDER BY id DESC
      ");
      echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
      exit;
  }

  // POST (criar)
  if ($method === 'POST') {
    $paciente_id     = trim($_POST['paciente_id'] ?? '');
    $medico_id       = trim($_POST['medico_id'] ?? '');
    $tipo            = trim($_POST['tipo'] ?? 'Consulta');
    $status          = trim($_POST['status'] ?? 'Pendente');
    $data_atendimento= trim($_POST['data_atendimento'] ?? '');
    $diagnostico     = trim($_POST['diagnostico'] ?? '');
    $cid             = trim($_POST['cid'] ?? '');
    $observacoes     = trim($_POST['observacoes'] ?? '');

    if ($paciente_id==='' || $medico_id==='' || $data_atendimento==='') {
      http_response_code(422);
      echo json_encode(['error'=>'Preencha paciente, profissional e data.'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $anexo = save_upload('anexo', __DIR__ . '/../uploads/atendimentos');

    $sql = "INSERT INTO atendimentos (paciente_id, medico_id, tipo, status, data_atendimento, diagnostico, cid, observacoes, anexo)
            VALUES (:paciente_id, :medico_id, :tipo, :status, :data_atendimento, :diagnostico, :cid, :observacoes, :anexo)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':paciente_id'=>$paciente_id, ':medico_id'=>$medico_id, ':tipo'=>$tipo, ':status'=>$status,
      ':data_atendimento'=>$data_atendimento, ':diagnostico'=>$diagnostico, ':cid'=>$cid,
      ':observacoes'=>$observacoes, ':anexo'=>$anexo
    ]);
    echo json_encode(['success'=>true, 'id'=>$pdo->lastInsertId(), 'anexo'=>$anexo], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // PUT/PATCH (atualizar)
  if ($method === 'PUT' || $method === 'PATCH') {
    $data = json_input();
    $id   = (int)($data['id'] ?? 0);
    if ($id<=0) { http_response_code(422); echo json_encode(['error'=>'ID inválido'], JSON_UNESCAPED_UNICODE); exit; }

    // aceitável: paciente_id ou paciente (fallback para compatibilidade com versões antigas)
    $paciente_id     = trim($data['paciente_id'] ?? $data['paciente'] ?? '');
    $medico_id       = trim($data['medico_id'] ?? $data['medico'] ?? $data['profissional'] ?? '');
    $tipo            = trim($data['tipo'] ?? 'Consulta');
    $status          = trim($data['status'] ?? 'Pendente');
    $data_atendimento= trim($data['data_atendimento'] ?? $data['data'] ?? '');
    $diagnostico     = trim($data['diagnostico'] ?? '');
    $cid             = trim($data['cid'] ?? '');
    $observacoes     = trim($data['observacoes'] ?? '');
    $remover_anexo   = !empty($data['remover_anexo']);

    if ($paciente_id==='' || $medico_id==='' || $data_atendimento==='') {
      http_response_code(422);
      echo json_encode(['error'=>'Preencha paciente, profissional e data.'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $sql = "UPDATE atendimentos
            SET paciente_id=:paciente_id, medico_id=:medico_id, tipo=:tipo, status=:status,
                data_atendimento=:data_atendimento, diagnostico=:diagnostico, cid=:cid,
                observacoes=:observacoes";
    if ($remover_anexo) { $sql .= ", anexo = NULL "; }
    $sql .= " WHERE id=:id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':paciente_id'=>$paciente_id, ':medico_id'=>$medico_id, ':tipo'=>$tipo, ':status'=>$status,
      ':data_atendimento'=>$data_atendimento, ':diagnostico'=>$diagnostico, ':cid'=>$cid,
      ':observacoes'=>$observacoes, ':id'=>$id
    ]);
    echo json_encode(['success'=>true], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // DELETE
  if ($method === 'DELETE') {
    parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
    $id = (int)($qs['id'] ?? 0);
    if ($id<=0) { http_response_code(400); echo json_encode(['error'=>'ID inválido'], JSON_UNESCAPED_UNICODE); exit; }
    $stmt = $pdo->prepare("DELETE FROM atendimentos WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    echo json_encode(['success'=>true], JSON_UNESCAPED_UNICODE);
    exit;
  }

  http_response_code(405);
  echo json_encode(['error'=>'Método não permitido'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'Erro no servidor: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
