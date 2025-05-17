<?php
$servername = "merito.mysql.dbaas.com.br";
$username = "merito";
$password = "Merito123@";
$dbname = "merito";


$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}


if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'register') {

        $firstName = $_POST['firstName'] ?? null;
        $lastName = $_POST['lastName'] ?? null;
        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;
        $passwordConfirm = $_POST['passwordConfirm'] ?? null;


        if (is_null($firstName) || is_null($lastName) || is_null($email) || is_null($password) || is_null($passwordConfirm)) {
            die("Por favor, preencha todos os campos.");
        }


        if ($password !== $passwordConfirm) {
            die("As senhas não correspondem.");
        }


        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            die("Este e-mail já está registrado.");
        }


        $sql = "INSERT INTO usuarios (email, senha, nome) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $email, $password, $firstName);
        if ($stmt->execute()) {
            echo "Conta criada com sucesso!";
            header("Location: login.html");
            exit();
        } else {
            echo "Erro: " . $stmt->error;
        }
    } elseif ($action == 'login') {


        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;


        if (is_null($email) || is_null($password)) {
            die("Por favor, preencha todos os campos.");
        }


        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();


        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($password === $user['senha']) {
                echo "Login bem-sucedido!";
                header("Location: index.html");
                exit();
            } else {
                echo "Senha inválida. Tente novamente.";
            }
        } else {
            echo "E-mail não encontrado. Tente novamente.";
        }
    } elseif ($action == 'add_paciente') {



        $nome = $_POST['nome'] ?? null;
        $cpf = $_POST['cpf'] ?? null;
        $data_nascimento = $_POST['data_nascimento'] ?? null;
        $contato = $_POST['contato'] ?? null;
        $ativo = $_POST['ativo'] ?? 1;


        if (is_null($nome) || is_null($cpf) || is_null($data_nascimento)) {
            die("Por favor, preencha todos os campos obrigatórios.");
        }


        $sql = "SELECT * FROM pacientes WHERE cpf = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $cpf);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            die("Este CPF já está registrado.");
        }


        $sql = "INSERT INTO pacientes (nome, cpf, data_nascimento, contato, ativo) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $nome, $cpf, $data_nascimento, $contato, $ativo);
        if ($stmt->execute()) {
            echo "Paciente adicionado com sucesso!";
            header("Location: pacientes.html");
            exit();
        } else {
            echo "Erro: " . $stmt->error;
        }
    } elseif ($action == 'list_pacientes') {


        $sql = "SELECT * FROM pacientes";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {

            $pacientes = [];
            while ($row = $result->fetch_assoc()) {
                $pacientes[] = $row;
            }
            echo json_encode($pacientes);
        } else {
            echo "Nenhum paciente encontrado.";
        }
    } elseif ($action == 'delete_paciente') {


        $id = $_POST['id'] ?? null;

        if (is_null($id)) {
            die("ID do paciente não fornecido.");
        }

        $sql = "DELETE FROM pacientes WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo "Paciente excluído com sucesso!";
            header("Location: pacientes.html");
            exit();
        } else {
            echo "Erro: " . $stmt->error;
        }
    }
} else {
    die("Ação não especificada.");
}


$conn->close();
?>
