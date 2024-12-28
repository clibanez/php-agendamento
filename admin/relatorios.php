<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

// Consultas para obter dados
// Dados Diários
$query_diario = "
    SELECT DATE(a.data_hora) AS data, COUNT(*) AS total_agendamentos, SUM(s.preco) AS total_faturamento
    FROM agendamentos a
    JOIN servicos s ON a.servico_id = s.id
    WHERE a.status = 'concluido' AND a.data_hora >= NOW() - INTERVAL 1 DAY
    GROUP BY DATE(a.data_hora)
";
$result_diario = $pdo->query($query_diario);
$dados_diarios = $result_diario->fetchAll(PDO::FETCH_ASSOC);

// Dados Semanais
$query_semanal = "
    SELECT DATE(a.data_hora) AS data, COUNT(*) AS total_agendamentos, SUM(s.preco) AS total_faturamento
    FROM agendamentos a
    JOIN servicos s ON a.servico_id = s.id
    WHERE a.status = 'concluido' AND a.data_hora >= NOW() - INTERVAL 7 DAY
    GROUP BY DATE(a.data_hora)
";
$result_semanal = $pdo->query($query_semanal);
$dados_semanais = $result_semanal->fetchAll(PDO::FETCH_ASSOC);

// Dados Mensais
$query_mensal = "
    SELECT MONTH(a.data_hora) AS mes, COUNT(*) AS total_agendamentos, SUM(s.preco) AS total_faturamento
    FROM agendamentos a
    JOIN servicos s ON a.servico_id = s.id
    WHERE a.status = 'concluido' AND a.data_hora >= NOW() - INTERVAL 1 YEAR
    GROUP BY MONTH(a.data_hora)
";
$result_mensal = $pdo->query($query_mensal);
$dados_mensais = $result_mensal->fetchAll(PDO::FETCH_ASSOC);

// Dados Anuais
$query_anual = "
    SELECT YEAR(a.data_hora) AS ano, COUNT(*) AS total_agendamentos, SUM(s.preco) AS total_faturamento
    FROM agendamentos a
    JOIN servicos s ON a.servico_id = s.id
    WHERE a.status = 'concluido' AND a.data_hora >= NOW() - INTERVAL 5 YEAR
    GROUP BY YEAR(a.data_hora)
";
$result_anual = $pdo->query($query_anual);
$dados_anuais = $result_anual->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - <?php echo htmlspecialchars($empresa['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #FF69B4;
            --secondary-color: #FFC0CB;
            --accent-color: #8A4FFF;
            --background-gradient-start: #FFE5EC;
            --background-gradient-end: #FFF0F5;
            --text-color: #4A4A4A;
        }

        body {
            background: linear-gradient(135deg, var(--background-gradient-start), var(--background-gradient-end));
            min-height: 100vh;
            font-family: 'Poppins', 'Arial', sans-serif;
            color: var(--text-color);
        }

        .container {
            padding: 2rem 0;
        }

        .relatorio-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(255,105,180,0.2);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 2px solid var(--secondary-color);
        }

        .page-title {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .chart-container {
            position: relative;
            height: 300px; /* Diminuindo a altura dos gráficos */
            width: 100%;
        }

        .btn-back {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .tab-content {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="relatorio-container">
            <h2 class="page-title">
                <i class="fas fa-chart-bar me-2"></i>
                Relatório de Agendamentos
            </h2>

            <a href="dashboard.php" class="btn btn-back mb-3">
                <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
            </a>

            <!-- Abas para os gráficos -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="diario-tab" data-bs-toggle="tab" data-bs-target="#diario" type="button" role="tab" aria-controls="diario" aria-selected="true">Diário</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="semanal-tab" data-bs-toggle="tab" data-bs-target="#semanal" type="button" role="tab" aria-controls="semanal" aria-selected="false">Semanal</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="mensal-tab" data-bs-toggle="tab" data-bs-target="#mensal" type="button" role="tab" aria-controls="mensal" aria-selected="false">Mensal</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="anual-tab" data-bs-toggle="tab" data-bs-target="#anual" type="button" role="tab" aria-controls="anual" aria-selected="false">Anual</button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="diario" role="tabpanel" aria-labelledby="diario-tab">
                    <div class="chart-container">
                        <canvas id="graficoDiario"></canvas>
                    </div>
                </div>
                <div class="tab-pane fade" id="semanal" role="tabpanel" aria-labelledby="semanal-tab">
                    <div class="chart-container">
                        <canvas id="graficoSemanal"></canvas>
                    </div>
                </div>
                <div class="tab-pane fade" id="mensal" role="tabpanel" aria-labelledby="mensal-tab">
                    <div class="chart-container">
                        <canvas id="graficoMensal"></canvas>
                    </div>
                </div>
                <div class="tab-pane fade" id="anual" role="tabpanel" aria-labelledby="anual-tab">
                    <div class="chart-container">
                        <canvas id="graficoAnual"></canvas>
                    </div>
                </div>
            </div>

            <script>
                // Dados Diários
                const dadosDiarios = <?php echo json_encode($dados_diarios); ?>;
                const labelsDiarios = dadosDiarios.map(d => d.data);
                const totalDiarios = dadosDiarios.map(d => d.total_agendamentos);
                const totalFaturamentoDiario = dadosDiarios.map(d => d.total_faturamento);

                // Gráfico Diário
                const ctxDiario = document.getElementById('graficoDiario').getContext('2d');
                new Chart(ctxDiario, {
                    type: 'line',
                    data: {
                        labels: labelsDiarios,
                        datasets: [{
                            label: 'Agendamentos Diários',
                            data: totalDiarios,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 2,
                            fill: false
                        }, {
                            label: 'Faturamento Diário (R$)',
                            data: totalFaturamentoDiario,
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 2,
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Total (R$)'
                                }
                            }
                        }
                    }
                });

                // Dados Semanais
                const dadosSemanais = <?php echo json_encode($dados_semanais); ?>;
                const labelsSemanais = dadosSemanais.map(d => d.data);
                const totalSemanais = dadosSemanais.map(d => d.total_agendamentos);
                const totalFaturamentoSemanal = dadosSemanais.map(d => d.total_faturamento);

                // Gráfico Semanal
                const ctxSemanal = document.getElementById('graficoSemanal').getContext('2d');
                new Chart(ctxSemanal, {
                    type: 'bar',
                    data: {
                        labels: labelsSemanais,
                        datasets: [{
                            label: 'Agendamentos Semanais',
                            data: totalSemanais,
                            backgroundColor: 'rgba(153, 102, 255, 0.2)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Faturamento Semanal (R$)',
                            data: totalFaturamentoSemanal,
                            backgroundColor: 'rgba(255, 159, 64, 0.2)',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Total (R$)'
                                }
                            }
                        }
                    }
                });

                // Dados Mensais
                const dadosMensais = <?php echo json_encode($dados_mensais); ?>;
                const labelsMensais = dadosMensais.map(d => d.mes);
                const totalMensais = dadosMensais.map(d => d.total_agendamentos);
                const totalFaturamentoMensal = dadosMensais.map(d => d.total_faturamento);

                // Gráfico Mensal
                const ctxMensal = document.getElementById('graficoMensal').getContext('2d');
                new Chart(ctxMensal, {
                    type: 'bar',
                    data: {
                        labels: labelsMensais,
                        datasets: [{
                            label: 'Agendamentos Mensais',
                            data: totalMensais,
                            backgroundColor: 'rgba(255, 159, 64, 0.2)',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Faturamento Mensal (R$)',
                            data: totalFaturamentoMensal,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Total (R$)'
                                }
                            }
                        }
                    }
                });

                // Dados Anuais
                const dadosAnuais = <?php echo json_encode($dados_anuais); ?>;
                const labelsAnuais = dadosAnuais.map(d => d.ano);
                const totalAnuais = dadosAnuais.map(d => d.total_agendamentos);
                const totalFaturamentoAnual = dadosAnuais.map(d => d.total_faturamento);

                // Gráfico Anual
                const ctxAnual = document.getElementById('graficoAnual').getContext('2d');
                new Chart(ctxAnual, {
                    type: 'bar',
                    data: {
                        labels: labelsAnuais,
                        datasets: [{
                            label: 'Agendamentos Anuais',
                            data: totalAnuais,
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Faturamento Anual (R$)',
                            data: totalFaturamentoAnual,
                            backgroundColor: 'rgba(153, 102, 255, 0.2)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Total (R$)'
                                }
                            }
                        }
                    }
                });
            </script>
        </div>
    </div>
</body>
</html>
