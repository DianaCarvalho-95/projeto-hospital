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
// FUNÇÃO PRINCIPAL
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