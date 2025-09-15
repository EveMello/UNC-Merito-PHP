<?php
// api/receitas.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

// Handle preflight requests
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
    // Bootstrap de dados para montar selects
    if ($method === 'GET' && isset($_GET['bootstrap'])) {
        // Carregar pacientes
        $stmtPac = $pdo->prepare("SELECT id, nome, cpf FROM pacientes ORDER BY nome");
        $stmtPac->execute();
        $pac = $stmtPac->fetchAll(PDO::FETCH_ASSOC);
        
        // Carregar médicos
        $stmtMed = $pdo->prepare("SELECT id, nome, crm, especialidade FROM medicos ORDER BY nome");
        $stmtMed->execute();
        $med = $stmtMed->fetchAll(PDO::FETCH_ASSOC);
        
        // Carregar medicamentos
        $stmtMeds = $pdo->prepare("SELECT id, nome, dosagem, forma, fabricante FROM medicamentos ORDER BY nome");
        $stmtMeds->execute();
        $meds = $stmtMeds->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'pacientes' => $pac, 
            'medicos' => $med, 
            'medicamentos' => $meds
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Obter uma receita completa (para reabrir/ imprimir)
    if ($method === 'GET' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        if ($id <= 0) { 
            http_response_code(400); 
            echo json_encode(['error' => 'ID inválido']); 
            exit; 
        }

        $cab = $pdo->prepare("
            SELECT r.id, r.data_emissao, r.observacoes,
                   p.nome AS paciente_nome, p.cpf AS paciente_cpf,
                   m.nome AS medico_nome, m.crm AS medico_crm, m.especialidade AS medico_esp
            FROM receitas r
            JOIN pacientes p ON p.id = r.paciente_id
            JOIN medicos m ON m.id = r.medico_id
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
            SELECT ri.id, med.nome AS medicamento_nome, ri.dosagem, ri.posologia, ri.quantidade, ri.duracao
            FROM receita_itens ri
            JOIN medicamentos med ON med.id = ri.medicamento_id
            WHERE ri.receita_id = :id
            ORDER BY ri.id
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
        
        // Log para debug
        error_log('Dados recebidos: ' . print_r($data, true));
        
        $paciente_id = (int)($data['paciente_id'] ?? 0);
        $medico_id = (int)($data['medico_id'] ?? 0);
        $data_emissao = trim($data['data_emissao'] ?? date('Y-m-d'));
        $observacoes = trim($data['observacoes'] ?? '');
        $itens = $data['itens'] ?? [];

        if ($paciente_id <= 0 || $medico_id <= 0 || empty($itens)) {
            http_response_code(422);
            echo json_encode(['error' => 'Informe paciente, médico e ao menos 1 item.']);
            exit;
        }

        $pdo->beginTransaction();
        
        try {
            // Inserir receita
            $stmt = $pdo->prepare("
                INSERT INTO receitas (atendimento_id, dosagem, frequencia, duracao, data_emissao, observacoes) 
                VALUES (:p, :m, :d, :o)
            ");
            $stmt->execute([
                ':p' => $paciente_id, 
                ':m' => $medico_id, 
                ':d' => $data_emissao, 
                ':o' => $observacoes
            ]);
            $rid = (int)$pdo->lastInsertId();

            // Inserir itens da receita
            $ins = $pdo->prepare("
                INSERT INTO receita_itens (receita_id, medicamento_id, dosagem, posologia, quantidade, duracao)
                VALUES (:r, :med, :dos, :pos, :qt, :dur)
            ");

            foreach ($itens as $item) {
                $medicamento_id = (int)($item['medicamento_id'] ?? 0);
                $dosagem = trim($item['dosagem'] ?? '');
                $posologia = trim($item['posologia'] ?? '');
                $quantidade = trim($item['quantidade'] ?? '');
                $duracao = trim($item['duracao'] ?? '');

                if ($medicamento_id <= 0 || $dosagem === '' || $posologia === '') {
                    throw new Exception('Itens inválidos: medicamento, dosagem e posologia são obrigatórios.');
                }
                
                $ins->execute([
                    ':r' => $rid, 
                    ':med' => $medicamento_id, 
                    ':dos' => $dosagem, 
                    ':pos' => $posologia,
                    ':qt' => $quantidade, 
                    ':dur' => $duracao
                ]);
            }

            $pdo->commit();
            echo json_encode([
                'success' => true, 
                'receita_id' => $rid
            ], JSON_UNESCAPED_UNICODE);
            
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
    // Log do erro completo
    error_log('Erro na API receitas: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro no servidor',
        'message' => $e->getMessage()
    ]);
}