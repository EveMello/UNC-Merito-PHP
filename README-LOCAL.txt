# Rodar localmente sem XAMPP (PHP + SQLite)

1) Instale o PHP no Windows
   - Baixe: https://windows.php.net/download/ (x64 Non Thread Safe ZIP é suficiente)
   - Extraia em C:\php
   - Adicione C:\php à variável de ambiente PATH
   - Copie `php.ini-development` para `php.ini` e habilite as extensões:
       extension=pdo_sqlite
       extension=sqlite3

2) Inicie o servidor embutido do PHP
   - Abra o PowerShell na pasta do projeto (onde estão os .html)
   - Rode: `php -S localhost:8080`

3) Banco local (SQLite)
   - O arquivo `conexao.php` cria automaticamente `./data/merito.db` e as tabelas `usuarios` e `pacientes`.
   - Ações (POST ou GET param `action`):
       - registrar_usuario (email, senha, nome)
       - login (email, senha)
       - criar_paciente (nome, cpf, data_nascimento, contato, ativo)
       - listar_pacientes (retorna JSON com pacientes)
       - excluir_paciente (id)

4) Exemplo JS para listar na tela (adaptar em pacientes.html)
   fetch('conexao.php?action=listar_pacientes')
     .then(r => r.json())
     .then(rows => console.log(rows));
