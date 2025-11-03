document.getElementById('urlForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const pathname = window.location.pathname;
    const baseUrlPath = pathname.substring(0, pathname.lastIndexOf('/') + 1);
    const apiUrl = window.location.origin + baseUrlPath + 'api/link';
    let longUrl = document.getElementById('longUrl').value;
    if (longUrl.length > 0 && !longUrl.startsWith('http://') && !longUrl.startsWith('https://')) {
        longUrl = 'http://' + longUrl;
    }

    const resultadoDiv = document.getElementById('resultado');
    const shortUrlText = document.getElementById('shortUrlText');
    const shortUrlInput = document.getElementById('shortUrlInput');
    const mensagemErro = document.getElementById('mensagemErro');

    // Limpa resultados anteriores
    resultadoDiv.style.display = 'none';
    mensagemErro.textContent = '';
    shortUrlInput.style.display = 'none';

    try {

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ long_url: longUrl }) // Envia a URL no corpo como JSON
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
        // Erros de rede ou JSON inválido
        mensagemErro.textContent = 'Falha na comunicação com a API.';
        console.error('Fetch Error:', error);
    }
});