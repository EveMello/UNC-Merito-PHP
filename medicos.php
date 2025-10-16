<?php
session_start();
?>
<?php /* medicos.php - CRUD de Médicos conectado */ ?>
<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Sistema de Prontuário Médico - Médicos" />
    <title>Médicos - Sistema de Prontuário Médico</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
      .actions { display:flex; gap:.5rem; }
      .card-body { padding:2rem; }
      table { margin-top:1rem; border-spacing:0 .5rem; width:100%; }
      table th { background-color:#f8f9fa; text-align:center; padding:.75rem; }
      table td { background-color:#ffffff; text-align:center; padding:.75rem; border:1px solid #dee2e6; }
    </style>
  </head>
  <body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
      <a class="navbar-brand ps-3" href="dashboard.php">Prontuário Médico</a>
      <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
            <li><a class="dropdown-item" href="#">Configurações</a></li>
            <form id="logoutForm" method="post" action="conexao.php">
              <input type="hidden" name="action" value="logout">
              <li><a class="dropdown-item" href="#" onclick="document.getElementById('logoutForm').submit();">Sair</a></li>
            </form>
          </ul>
        </li>
      </ul>
    </nav>

    <div id="layoutSidenav">
      <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
          <div class="sb-sidenav-menu">
            <div class="nav">
              <div class="sb-sidenav-menu-heading">Principal</div>
              <a class="nav-link" href="dashboard.php">
                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                Dashboard
              </a>
              <div class="sb-sidenav-menu-heading">Gestão</div>
              <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePatients" aria-expanded="false" aria-controls="collapsePatients">
                <div class="sb-nav-link-icon"><i class="fas fa-user-injured"></i></div>
                Pacientes
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
              </a>
              <div class="collapse" id="collapsePatients" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                  <a class="nav-link" href="pacientes.php">Lista de Pacientes</a>
                </nav>
              </div>
                <a class="nav-link" 
                  href="#" 
                  data-bs-toggle="collapse" 
                  data-bs-target="#collapseDoctors" 
                  aria-expanded="true" 
                  aria-controls="collapseDoctors">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-md"></i></div>
                    Médicos
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
              <div class="collapse show" id="collapseDoctors" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                  <a class="nav-link active" href="medicos.php">Lista de Médicos</a>
                </nav>
              </div>
              <a class="nav-link" href="medicamentos.php">
                <div class="sb-nav-link-icon"><i class="fas fa-pills"></i></div>
                Medicamentos
              </a>
            <a class="nav-link" href="atendimentos.php"><div class="sb-nav-link-icon"><i class="fas fa-notes-medical"></i></div>Atendimentos</a>
            <!-- <a class="nav-link" href="receita.html"><div class="sb-nav-link-icon"><i class="fas fa-file-signature"></i></div>Receitas</a> -->
            </div>
          </div>
          <div class="sb-sidenav-footer">
              <div class="small">Logado como:</div>
              <?= isset($_SESSION['usuario_nome']) ? htmlspecialchars($_SESSION['usuario_nome'], ENT_QUOTES, 'UTF-8') : 'Visitante' ?>
          </div>
        </nav>
      </div>

      <div id="layoutSidenav_content">
        <main>
          <div class="container-fluid px-4">
            <h1 class="mt-4">Médicos</h1>
            <ol class="breadcrumb mb-4">
              <li class="breadcrumb-item"><a href="dahsboard.php">Dashboard</a></li>
              <li class="breadcrumb-item active">Médicos</li>
            </ol>
            <div class="card mb-4">
              <div class="card-header d-flex justify-content-between align-items-center">
                <div><i class="fas fa-user-md"></i> Lista de Médicos</div>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newDoctorModal" id="btnNovo">Novo Médico</button>
              </div>
              <div class="card-body">
                <div id="alertContainer"></div>
                <table id="doctorTable" class="table table-striped table-hover">
                  <thead>
                    <tr>
                      <th>Nome</th>
                      <th>CRM</th>
                      <th>Especialidade</th>
                      <th>Contato</th>
                      <th>Ações</th>
                    </tr>
                  </thead>
                  <tbody id="doctorTbody">
                    <!-- linhas via JS -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </main>

        <!-- Modal Criar/Editar -->
        <div class="modal fade" id="newDoctorModal" tabindex="-1" aria-labelledby="newDoctorModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="newDoctorModalLabel">Novo Médico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <form id="newDoctorForm">
                  <input type="hidden" id="doctorId" />
                  <div class="mb-3">
                    <label for="doctorName" class="form-label required">Nome</label>
                    <input type="text" class="form-control" id="doctorName" maxlength="100" required />
                  </div>
                  <div class="mb-3">
                    <label for="doctorCRM" class="form-label required">CRM</label>
                    <input type="text" class="form-control" id="doctorCRM" maxlength="20" required />
                  </div>
                  <div class="mb-3">
                    <label for="doctorSpecialty" class="form-label required">Especialidade</label>
                    <input type="text" class="form-control" id="doctorSpecialty" maxlength="50" required />
                  </div>
                  <div class="mb-3">
                    <label for="doctorContact" class="form-label required">Contato</label>
                    <input type="text" class="form-control" id="doctorContact" />
                  </div>
                </form>
              </div>
              <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" id="saveDoctorBtn">Salvar</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Detalhes -->
        <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Detalhes do Médico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body" id="detailsContent"></div>
              <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
              </div>
            </div>
          </div>
        </div>

        <footer class="py-4 bg-light mt-auto">
          <div class="container-fluid px-4">
            <div class="d-flex align-items-center justify-content-between small">
              <div class="text-muted">Copyright &copy; Mérito Health</div>
              <div>
                <a href="#">Política de Privacidade</a> &middot;
                <a href="#">Termos &amp; Condições</a>
              </div>
            </div>
          </div>
        </footer>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
      const API = 'api/medicos.php';

      const tbody = document.getElementById('doctorTbody');
      const saveBtn = document.getElementById('saveDoctorBtn');
      const alertContainer = document.getElementById('alertContainer');
      const modalEl = document.getElementById('newDoctorModal');
      const modalForm = document.getElementById('newDoctorForm');
      const detailsContent = document.getElementById('detailsContent');

      const getModal = () => bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);

      function showAlert(msg, type = 'success') {
        const oldAlert = document.getElementById('floatingAlert');
        if (oldAlert) oldAlert.remove();

        // Cria o alerta flutuante
        const alertDiv = document.createElement('div');
        alertDiv.id = 'floatingAlert';
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.role = 'alert';
        alertDiv.style.position = 'fixed';
        alertDiv.style.top = '20px';
        alertDiv.style.right = '20px';
        alertDiv.style.zIndex = '9999';
        alertDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.2)';
        alertDiv.innerHTML = `
          ${msg}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alertDiv);

        // Remove após 3 segundos
        setTimeout(() => {
          const al = bootstrap.Alert.getOrCreateInstance(alertDiv);
          al.close();
        }, 3000);
      }

      // LISTAR
      async function loadDoctors() {
        tbody.innerHTML = '<tr><td colspan="5">Carregando...</td></tr>';
        try {
          const res = await fetch(API);
          const data = await res.json();
          if (!Array.isArray(data)) throw new Error('Resposta inesperada do servidor.');
          if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5">Nenhum médico cadastrado.</td></tr>';
            return;
          }
          tbody.innerHTML = '';
          data.forEach(m => {
            const tr = document.createElement('tr');
            tr.dataset.id = m.id;
            tr.innerHTML = `
              <td>${m.nome}</td>
              <td>${m.crm}</td>
              <td>${m.especialidade}</td>
              <td>${m.contato}</td>
              <td class="actions justify-content-center">
                <button class="btn btn-sm btn-info" data-action="detalhes">Detalhes</button>
                <button class="btn btn-sm btn-warning" data-action="editar">Editar</button>
                <button class="btn btn-sm btn-danger" data-action="excluir">Excluir</button>
              </td>`;
            tbody.appendChild(tr);
          });
        } catch (e) {
          tbody.innerHTML = `<tr><td colspan="5" class="text-danger">Erro ao carregar: ${e.message}</td></tr>`;
        }
      }

      // CRIAR / ATUALIZAR
      saveBtn.addEventListener('click', async () => {
        const id = document.getElementById('doctorId').value.trim();
        const nome = document.getElementById('doctorName').value.trim();
        const crm  = document.getElementById('doctorCRM').value.trim();
        const especialidade = document.getElementById('doctorSpecialty').value.trim();
        const contato = document.getElementById('doctorContact').value.trim();

        if (!nome || !crm || !especialidade || !contato) {
          showAlert('Preencha todos os campos obrigatórios.', 'warning');
          return;
        }

        try {
          if (id) {
            // PUT
            const res = await fetch(API, {
              method: 'PUT',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id, nome, crm, especialidade, contato })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Falha ao atualizar.');
            showAlert('Médico atualizado com sucesso!');
          } else {
            // POST
            const fd = new FormData();
            fd.append('nome', nome);
            fd.append('crm', crm);
            fd.append('especialidade', especialidade);
            fd.append('contato', contato);

            const res = await fetch(API, { method: 'POST', body: fd });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Falha ao salvar.');
            showAlert('Médico salvo com sucesso!');
          }

          getModal().hide();
          modalForm.reset();
          document.getElementById('doctorId').value = '';
          await loadDoctors();
        } catch (e) {
          showAlert(e.message, 'danger');
        }
      });

      // Abrir modal como "novo"
      document.getElementById('btnNovo').addEventListener('click', () => {
        document.getElementById('newDoctorModalLabel').innerText = 'Novo Médico';
        modalForm.reset();
        document.getElementById('doctorId').value = '';
      });

      // Detalhes / Editar / Excluir
      tbody.addEventListener('click', async (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;
        const action = btn.dataset.action;
        const tr = btn.closest('tr');
        const id = tr?.dataset?.id;

        if (action === 'detalhes') {
          const tds = tr.querySelectorAll('td');
          const labels = ['Nome', 'CRM', 'Especialidade', 'Contato'];
          const html = Array.from(tds).slice(0,4).map((td,i)=>`<p><strong>${labels[i]}:</strong> ${td.textContent}</p>`).join('');
          detailsContent.innerHTML = html;
          new bootstrap.Modal(document.getElementById('detailsModal')).show();
          return;
        }

        if (action === 'editar') {
          const tds = tr.querySelectorAll('td');
          document.getElementById('newDoctorModalLabel').innerText = 'Editar Médico';
          document.getElementById('doctorId').value = id;
          document.getElementById('doctorName').value = tds[0].textContent;
          document.getElementById('doctorCRM').value = tds[1].textContent;
          document.getElementById('doctorSpecialty').value = tds[2].textContent;
          document.getElementById('doctorContact').value = tds[3].textContent;
          new bootstrap.Modal(modalEl).show();
          return;
        }

        if (action === 'excluir') {
          if (!confirm('Deseja excluir este médico?')) return;
          try {
            const res = await fetch(`${API}?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Falha ao excluir.');
            showAlert('Médico excluído com sucesso!');
            await loadDoctors();
          } catch (e) {
            showAlert(e.message, 'danger');
          }
        }
      });

      // Inicializa
      loadDoctors();
    </script>
  </body>
</html>
