// Inicializa os modais do Bootstrap
var profissionaisModal = new bootstrap.Modal(document.getElementById('profissionaisModal'));
var servicosModal = new bootstrap.Modal(document.getElementById('servicosModal'));
var dateSelectionModal = new bootstrap.Modal(document.getElementById('dateSelectionModal'));
var timeSelectionModal = new bootstrap.Modal(document.getElementById('timeSelectionModal'));



// Lida com a seleção do serviço
document.querySelectorAll('.select-service').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();  // Impede a ação padrão do botão (ex: link)
        const nome = this.getAttribute('data-nome');  // Obtém o nome do serviço
        const id = this.getAttribute('data-id');  // Obtém o id do serviço
        document.querySelector('[name="servico"]').value = id;  // Atribui o id do serviço
        document.querySelector('#servico_nome').textContent = nome;  // Exibe o nome do serviço no formulário
        servicosModal.hide();  // Fecha o modal de serviços
    });
});

// Lida com a seleção do profissional
document.querySelectorAll('.select-profissional').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();  // Impede a ação padrão do botão
        const id = this.getAttribute('data-id');  // Obtém o id do profissional
        const nome = this.getAttribute('data-nome');  // Obtém o nome do profissional
        document.querySelector('[name="profissional_id"]').value = id;  // Atribui o id do profissional
        document.querySelector('#profissional_nome').textContent = nome;  // Exibe o nome do profissional no formulário
        document.getElementById('date-selection-container').style.display = 'block';  // Exibe a seção de seleção de data
        profissionaisModal.hide();  // Fecha o modal de profissionais
    });
});

// Lida com o clique no botão de seleção de data
document.getElementById('select-date-btn').addEventListener('click', function() {
    const profissionalId = document.querySelector('[name="profissional_id"]').value;
    if (!profissionalId) {
        alert('Por favor, selecione um profissional primeiro.');
        return;
    }

    // Chama o endpoint para buscar as datas disponíveis
    fetch('fetch_available_dates.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `profissional_id=${profissionalId}`
    })
    .then(response => response.json())
    .then(dates => {
        const datesList = document.getElementById('available-dates-list');
        datesList.innerHTML = '';  // Limpa a lista de datas

        if (dates.length === 0) {
            datesList.innerHTML = '<div class="alert alert-info">Nenhuma data disponível para este profissional.</div>';
            return;
        }

        dates.forEach(date => {
            const button = document.createElement('button');
            button.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
            button.innerHTML = `
                <div>
                    <strong>${date.data_formatada}</strong>
                    <br>
                    <small class="text-muted">${date.dia_semana}</small>
                </div>
                <i class="fas fa-chevron-right"></i>
            `;
            button.onclick = function() {
                fetchAvailableTimesForDate(profissionalId, date.data);
            };
            datesList.appendChild(button);
        });

        // Exibe o modal de seleção de data
        dateSelectionModal.show();  
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erro ao carregar datas disponíveis.');
    });
});

// Função para buscar horários disponíveis para uma data
function fetchAvailableTimesForDate(profissionalId, date) {
    const servicoId = document.querySelector('[name="servico"]').value; // Pega o ID do serviço selecionado
    fetch('fetch_available_times.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `profissional_id=${profissionalId}&data=${date}&servico_id=${servicoId}`
    })
    .then(response => response.json())
    .then(times => {
        const timesList = document.getElementById('available-times-list');
        timesList.innerHTML = '';  // Limpa a lista de horários

        if (times.length === 0) {
            timesList.innerHTML = '<div class="alert alert-info">Nenhum horário disponível nesta data.</div>';
            return;
        }

        times.forEach(time => {
            const button = document.createElement('button');
            button.className = 'list-group-item list-group-item-action';
            button.innerHTML = `<strong>${time}</strong>`;
            button.onclick = function() {
                selectDateTime(date, time);
                timeSelectionModal.hide();
                dateSelectionModal.hide();
            };
            timesList.appendChild(button);
        });

        timeSelectionModal.show();  
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erro ao carregar horários disponíveis.');
    });
}


// Função para selecionar a data e hora
function selectDateTime(date, time) {
    document.getElementById('data').value = date;  // Preenche o campo de data
    document.getElementById('hora').value = time;  // Preenche o campo de hora

    // Verifica se o elemento 'data_hora_selecionada' existe
    const dataHoraSelecionada = document.getElementById('data_hora_selecionada');
    if (dataHoraSelecionada) {
        dataHoraSelecionada.textContent = `Data: ${date} | Hora: ${time}`;
    } else {
        console.warn("Elemento 'data_hora_selecionada' não encontrado.");
    }

    // Verifica se o elemento 'datetime_display' existe
    const datetimeDisplay = document.getElementById('datetime_display');
    if (datetimeDisplay) {
        datetimeDisplay.textContent = `Você selecionou: ${date} às ${time}`;
    } else {
        console.warn("Elemento 'datetime_display' não encontrado.");
    }
}
