// ================================
// SELECIONAR ELEMENTOS DO HTML
// ================================

// Input da data
const inputData = document.getElementById("dataUltima");

// Input dos meses
const inputMeses = document.getElementById("meses");

// Local onde aparece o resultado
const resultado = document.getElementById("resultado");


// ================================
// FERRAMENTA: CALCULAR MANUTENCAO
// ================================

function calcularManutencao() {

    if (!inputData.value || !inputMeses.value) {
        resultado.textContent = "";
        return;
    }

    // Criar data a partir do input
    let data = new Date(inputData.value);

    // Converter meses para número
    let meses = parseInt(inputMeses.value);

    // Adicionar meses à data
    data.setMonth(data.getMonth() + meses);

    // Mostrar resultado formatado
    resultado.textContent = data.toLocaleDateString("pt-PT");
}

// ================================
// EVENTOS 
// ================================

// Sempre que muda a data
if (inputData) {
    inputData.addEventListener("input", calcularManutencao);
}

// Sempre que muda os meses
if (inputMeses) {
    inputMeses.addEventListener("input", calcularManutencao);
}


// =========================================================
// FERRAMENTA: AVALIAÇÃO TÉCNICA
// =========================================================

function avaliarEstadoTecnico() {
    // Ler o estado das opções do formulário
    const precisaCalibracao = document.getElementById("precisaCalibracao");
    const emManutencao = document.getElementById("emManutencao");
    const avariaReportada = document.getElementById("avariaReportada");
    const mensagemTecnica = document.getElementById("mensagemTecnica");

    // Se a página atual não tiver estes elementos, sair sem erro
    if (!precisaCalibracao || !emManutencao || !avariaReportada || !mensagemTecnica) {
        return;
    }

    // Ler se cada checkbox está selecionada
    const calibracao = precisaCalibracao.checked;
    const manutencao = emManutencao.checked;
    const avaria = avariaReportada.checked;

    // Definir recomendação consoante a situação
    if (avaria) {
        mensagemTecnica.textContent = "Encaminhar imediatamente para assistência técnica.";
    } else if (manutencao) {
        mensagemTecnica.textContent = "Equipamento temporariamente indisponível. Aguardar conclusão da manutenção.";
    } else if (calibracao) {
        mensagemTecnica.textContent = "Agendar calibração antes de voltar a colocar o equipamento em serviço.";
    } else {
        mensagemTecnica.textContent = "Equipamento apto para utilização normal.";
    }
}