<?php
// api/receitas.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function json_input() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

try {
    // Bootstrap de dados (pacientes, médicos, medicamentos)
    if ($method === 'GET' && isset($_GET['bootstrap'])) {
        $stmtPac = $pdo->prepare("SELECT id, nome, cpf FROM pacientes ORDER BY nome");
        $stmtPac->execute();
        $pac = $stmtPac->fetchAll(PDO::FETCH_ASSOC);

        $stmtMed = $pdo->prepare("SELECT id, nome, crm, especialidade FROM medicos ORDER BY nome");
        $stmtMed->execute();
        $med = $stmtMed->fetchAll(PDO::FETCH_ASSOC);

        $stmtMeds = $pdo->prepare("SELECT id, nome, principio_ativo, fabricante, data_validade, quantidade, cod_barras FROM medicamentos ORDER BY nome");
        $stmtMeds->execute();
        $meds = $stmtMeds->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'pacientes' => $pac, 
            'medicos' => $med, 
            'medicamentos' => $meds
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Obter receita completa
    if ($method === 'GET' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        if ($id <= 0) { 
            http_response_code(400); 
            echo json_encode(['error' => 'ID inválido']); 
            exit; 
        }

        $cab = $pdo->prepare("
            SELECT p.id AS paciente_id, p.nome AS paciente_nome, p.cpf AS paciente_cpf,
                   a.id AS atendimento_id,
                   r.id AS receita_id, r.criado_em AS data_emissao
            FROM prescricoes r
            JOIN atendimentos a ON a.id = r.atendimento_id
            JOIN pacientes p ON p.id = a.paciente_id
            WHERE r.id = :id
        ");
        $cab->execute([':id' => $id]);
        $header = $cab->fetch(PDO::FETCH_ASSOC);

        if (!$header) {
            http_response_code(404);
            echo json_encode(['error' => 'Receita não encontrada']);
            exit;
        }

        $it = $pdo->prepare("
            SELECT mp.id, m.nome AS medicamento_nome, mp.dosagem, mp.frequencia, mp.duracao
            FROM medicamentos_prescricao mp
            JOIN medicamentos m ON m.id = mp.medicamento_id
            WHERE mp.prescricao_id = :id
            ORDER BY mp.id
        ");
        $it->execute([':id' => $id]);
        $itens = $it->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'cabecalho' => $header,
            'itens' => $itens
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Criar receita
    if ($method === 'POST') {
        $data = json_input();
        $atendimento_id = (int)($data['atendimento_id'] ?? 0);
        $paciente_id = (int)($data['paciente_id'] ?? 0);
        $medico_id = (int)($data['medico_id'] ?? 0);
        $data_emissao = trim($data['data_emissao'] ?? date('Y-m-d'));
        $observacoes = trim($data['observacoes'] ?? '');
        $itens = $data['itens'] ?? [];

        if ($atendimento_id <= 0 || $paciente_id <= 0 || $medico_id <= 0 || empty($itens)) {
            http_response_code(422);
            echo json_encode(['error' => 'Informe atendimento, paciente, médico e ao menos 1 item.']);
            exit;
        }

        $pdo->beginTransaction();
        try {
            // Inserir prescrição
            $stmt = $pdo->prepare("
                INSERT INTO prescricoes (atendimento_id, criado_em) 
                VALUES (:atendimento, :data_emissao)
            ");
            $stmt->execute([
                ':atendimento' => $atendimento_id,
                ':data_emissao' => $data_emissao
            ]);
            $rid = (int)$pdo->lastInsertId();

            // Inserir medicamentos
            $ins = $pdo->prepare("
                INSERT INTO medicamentos_prescricao 
                (prescricao_id, medicamento_id, dosagem, frequencia, duracao)
                VALUES (:prescricao, :medicamento, :dosagem, :frequencia, :duracao)
            ");

            foreach ($itens as $item) {
                $medicamento_id = (int)($item['medicamento_id'] ?? 0);
                $dosagem = trim($item['dosagem'] ?? '');
                $frequencia = trim($item['posologia'] ?? '');
                $duracao = trim($item['duracao'] ?? '');

                if ($medicamento_id <= 0 || $dosagem === '' || $frequencia === '') {
                    throw new Exception('Itens inválidos: medicamento, dosagem e frequência são obrigatórios.');
                }

                $ins->execute([
                    ':prescricao' => $rid,
                    ':medicamento' => $medicamento_id,
                    ':dosagem' => $dosagem,
                    ':frequencia' => $frequencia,
                    ':duracao' => $duracao
                ]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'receita_id' => $rid], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(422);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);

} catch (Throwable $e) {
    error_log('Erro na API receitas: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro no servidor',
        'message' => $e->getMessage()
    ]);
}
