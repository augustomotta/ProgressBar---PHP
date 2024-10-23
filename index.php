<?php
session_start();

// Conexão com o banco de dados
$host = 'localhost'; // Altere para o seu host
$user = 'root'; // Altere para seu usuário
$password = 'webserveradmin'; // Altere para sua senha
$database = 'teste'; // Altere para seu banco de dados

$conn = new mysqli($host, $user, $password, $database);

// Verifica a conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Inicializa a sessão para controle de progresso
if (!isset($_SESSION['progress'])) {
    $_SESSION['progress'] = 0;
}

// Processa os dados quando solicitado via AJAX
if (isset($_GET['get_data'])) {
    $offset = intval($_GET['offset']); // Posição atual
    $limit = 1; // Pegar um dado por vez

    // Conta o total de registros para o progresso
    $result = $conn->query("SELECT COUNT(*) AS total FROM funcionarios");
    $row = $result->fetch_assoc();
    $totalRows = $row['total'];

    // Pega o próximo dado
    $result = $conn->query("SELECT * FROM funcionarios LIMIT $offset, $limit");
    $row = $result->fetch_assoc();

    // Calcula o progresso
    $progress = (($offset + 1) / $totalRows) * 100;

    // Envia o dado e o progresso de volta ao cliente
    echo json_encode([
        'nome' => $row['nome'],
        'cargo' => $row['cargo'],
        'progress' => $progress
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Bar com PHP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
            background-color: #f4f4f4;
        }

        .progress-container {
            width: 80%;
            background: #ddd;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-bar {
            height: 30px;
            width: 0;
            background: #4caf50;
            text-align: center;
            color: white;
            line-height: 30px;
        }

        #status {
            text-align: center;
            margin-top: 20px;
        }

        button {
            margin-top: 20px;
            padding: 10px 15px;
            font-size: 16px;
            cursor: pointer;
        }

        .result-container {
            margin-top: 20px;
            width: 80%;
            height: 200px;
            border: 1px solid #ccc;
            overflow-y: auto;
            background-color: #fff;
            padding: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="progress-container">
        <div class="progress-bar" id="progressBar">0%</div>
    </div>
    <div id="status">Pronto para iniciar a consulta.</div>
    <button id="startBtn">Iniciar Consulta</button>
    <div class="result-container" id="resultContainer">
        <h4>Resultados:</h4>
        <div id="results"></div>
    </div>

    <script>
        let currentOffset = 0;

        document.getElementById('startBtn').onclick = function() {
            document.getElementById('status').innerText = 'Processando...';
            document.getElementById('progressBar').style.width = '0%';
            document.getElementById('progressBar').innerText = '0%';
            document.getElementById('results').innerHTML = ''; // Limpa resultados anteriores

            // Inicia a consulta e continua buscando dados
            fetchNextData();
        };

        function fetchNextData() {
            // Faz uma requisição AJAX para pegar o próximo dado
            fetch(`<?= $_SERVER['PHP_SELF'] ?>?get_data=true&offset=${currentOffset}`)
                .then(response => response.json())
                .then(data => {
                    // Atualiza a barra de progresso
                    document.getElementById('progressBar').style.width = data.progress + '%';
                    document.getElementById('progressBar').innerText = Math.round(data.progress) + '%';

                    // Adiciona o resultado à caixa de resultados
                    document.getElementById('results').innerHTML += `<div>${data.nome} - ${data.cargo}</div>`;

                    // Atualiza o offset para o próximo registro
                    currentOffset++;

                    // Se ainda não alcançou 100%, continua buscando
                    if (data.progress < 100) {
                        fetchNextData(); // Chama a função de novo para buscar o próximo dado
                    } else {
                        document.getElementById('status').innerText = 'Consulta concluída!';
                    }
                })
                .catch(error => {
                    document.getElementById('status').innerText = 'Erro ao processar a consulta.';
                    console.error('Erro:', error);
                });
        }
    </script>
</body>

</html>