// app.js (Início do arquivo, ou após as declarações de import/constantes)

const validDaysInput = document.getElementById('validDays');
const feedbackElement = document.getElementById('expiryFeedback');

/**
 * Calcula a data/hora de expiração e atualiza o feedback na tela.
 * Esta função precisa ser GLOBAL (fora do listener de submit) para funcionar.
 */
function updateExpiryFeedback() {
    const days = parseInt(validDaysInput.value);

    // Validação de segurança. O HTML já tem min/max, mas garantimos no JS
    if (isNaN(days) || days < 1 || days > 7) {
        feedbackElement.textContent = "Por favor, defina um valor entre 1 e 7 dias.";
        feedbackElement.style.color = 'red'; // Feedback visual de erro
        return;
    }

    feedbackElement.style.color = 'inherit'; // Retorna à cor normal

    // Calcula a data de expiração, baseada no momento atual
    const expiryDate = new Date();
    expiryDate.setDate(expiryDate.getDate() + days); // Adiciona os N dias

    // Formata para exibição (PT-BR)
    const formattedDate = expiryDate.toLocaleDateString('pt-BR');
    const formattedTime = expiryDate.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    feedbackElement.innerHTML = `Até ${formattedDate} às ${formattedTime}.`;
}

// 1. Inicializa o feedback assim que o DOM estiver pronto
document.addEventListener('DOMContentLoaded', updateExpiryFeedback);

// 2. Atualiza o feedback em tempo real quando o usuário muda o valor
validDaysInput.addEventListener('input', updateExpiryFeedback);

// app.js (Onde você define o listener de submit)

document.getElementById('urlForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    // 1. Definições de Variáveis e Base URL (mantidas)
    const pathname = window.location.pathname;
    const baseUrlPath = pathname.substring(0, pathname.lastIndexOf('/') + 1);
    const apiUrl = window.location.origin + baseUrlPath + 'api/link';
    let longUrl = document.getElementById('longUrl').value;

    // ... (lógica de prefixo http://)
    if (longUrl.length > 0 && !longUrl.startsWith('http://') && !longUrl.startsWith('https://')) {
        longUrl = 'http://' + longUrl;
    }

    const resultadoDiv = document.getElementById('resultado');
    const shortUrlInput = document.getElementById('shortUrlInput');
    const mensagemErro = document.getElementById('mensagemErro');
    const validDaysInput = document.getElementById('validDays'); // Novo

    // --- NOVO: CÁLCULO DA DATA FINAL PARA O BACKEND (SQL FORMAT) ---
    const days = parseInt(validDaysInput.value);

    if (isNaN(days) || days < 1 || days > 7) {
        mensagemErro.textContent = 'Erro de Validação: O número de dias deve estar entre 1 e 7.';
        return; // Impede o envio se a validação falhar
    }

    // Recalcula a data exata para garantir que a hora de submissão seja precisa
    const expiryDate = new Date();
    expiryDate.setDate(expiryDate.getDate() + days);

    // Função auxiliar para formatar com padding (ex: 09 em vez de 9)
    const pad = (num) => num.toString().padStart(2, '0');

    const year = expiryDate.getFullYear();
    const month = pad(expiryDate.getMonth() + 1); // Mês é 0-base
    const day = pad(expiryDate.getDate());
    const hour = pad(expiryDate.getHours());
    const minute = pad(expiryDate.getMinutes());
    const second = pad(expiryDate.getSeconds());

    // Formato final SQL: "2025-11-08 15:00:00"
    const validUntil = `${year}-${month}-${day} ${hour}:${minute}:${second}`;
    // --- FIM DO CÁLCULO ---


    resultadoDiv.style.display = 'none';
    mensagemErro.textContent = '';
    shortUrlInput.style.display = 'none';

    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                long_url: longUrl,
                valid_until: validUntil
            }) 
        });

        const data = await response.json();

        if (response.ok) { // Status 2xx (201 Created, no seu caso)
            shortUrlText.textContent = 'Link Curto Criado com Sucesso:';
            shortUrlInput.value = data.short_url;
            shortUrlInput.style.display = 'block';
            resultadoDiv.style.display = 'block';

            navigator.clipboard.writeText(data.short_url)
            alert('Link copiado para a área de transferência! 🎉');

        } else { // Status 4xx, 5xx (Erro)
            const errorMessage = data.error || 'Ocorreu um erro desconhecido.';
            mensagemErro.textContent = 'Erro ao encurtar: ' + errorMessage;
        }

    } catch (error) {
        mensagemErro.textContent = 'Falha na comunicação com a API.';
        console.error('Fetch Error:', error);
    }

    // REMOVEMOS A FUNÇÃO updateExpiryFeedback DAQUI!
});