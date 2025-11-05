const validDaysInput = document.getElementById('validDays');
const feedbackElement = document.getElementById('expiryFeedback');

/**
 * Calcula a data/hora de expiração e atualiza o feedback na tela.
 * Esta função precisa ser GLOBAL (fora do listener de submit) para funcionar.
 */
function updateExpiryFeedback() {
    const days = parseInt(validDaysInput.value);

    if (isNaN(days) || days < 1 || days > 7) {
        feedbackElement.textContent = "Por favor, defina um valor entre 1 e 7 dias.";
        feedbackElement.style.color = 'red';
        return;
    }

    feedbackElement.style.color = 'inherit';

    const expiryDate = new Date();
    expiryDate.setDate(expiryDate.getDate() + days);

    const formattedDate = expiryDate.toLocaleDateString('pt-BR');
    const formattedTime = expiryDate.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    feedbackElement.innerHTML = `Até ${formattedDate} às ${formattedTime}.`;
}

document.addEventListener('DOMContentLoaded', updateExpiryFeedback);

validDaysInput.addEventListener('input', updateExpiryFeedback);

document.getElementById('urlForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const pathname = window.location.pathname;
    const baseUrlPath = pathname.substring(0, pathname.lastIndexOf('/') + 1);
    const apiUrl = window.location.origin + baseUrlPath + 'api/link';
    let longUrl = document.getElementById('longUrl').value;

    if (longUrl.length > 0 && !longUrl.startsWith('http://') && !longUrl.startsWith('https://')) {
        longUrl = 'http://' + longUrl;
    }

    const resultadoDiv = document.getElementById('resultado');
    const shortUrlInput = document.getElementById('shortUrlInput');
    const mensagemErro = document.getElementById('mensagemErro');
    const validDaysInput = document.getElementById('validDays');

    const days = parseInt(validDaysInput.value);

    if (isNaN(days) || days < 1 || days > 7) {
        mensagemErro.textContent = 'Erro de Validação: O número de dias deve estar entre 1 e 7.';
        return;
    }

    const expiryDate = new Date();
    expiryDate.setDate(expiryDate.getDate() + days);

    const pad = (num) => num.toString().padStart(2, '0');

    const year = expiryDate.getFullYear();
    const month = pad(expiryDate.getMonth() + 1);
    const day = pad(expiryDate.getDate());
    const hour = pad(expiryDate.getHours());
    const minute = pad(expiryDate.getMinutes());
    const second = pad(expiryDate.getSeconds());

    const validUntil = `${year}-${month}-${day} ${hour}:${minute}:${second}`;

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

        if (response.ok) {
            shortUrlText.textContent = 'Link Curto Criado com Sucesso:';
            shortUrlInput.value = data.short_url;
            shortUrlInput.style.display = 'block';
            resultadoDiv.style.display = 'block';

            navigator.clipboard.writeText(data.short_url)
            alert('Link copiado para a área de transferência! 🎉');

        } else {
            const errorMessage = data.error || 'Ocorreu um erro desconhecido.';
            mensagemErro.textContent = 'Erro ao encurtar: ' + errorMessage;
        }

    } catch (error) {
        mensagemErro.textContent = 'Falha na comunicação com a API.';
        console.error('Fetch Error:', error);
    }
});