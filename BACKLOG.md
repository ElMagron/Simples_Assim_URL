# 🚀 Backlog do Produto: Simples Assim URL (URL Shortener)

Este documento lista as funcionalidades, melhorias e tarefas futuras para o projeto, priorizadas por valor para o usuário e esforço de implementação.

---

## 1. 🌐 Épico: Usabilidade e UX (Experiência do Usuário)

### [Feature 1] Página de Criação de Links (Frontend)

**Prioridade:** ALTA
**Status:** ✅ **CONCLUÍDO**

**História de Usuário:**
COMO UM **usuário da aplicação**, EU QUERO **ter uma página web simples para colar uma URL longa**, PARA QUE EU POSSA **obter o link curto de forma fácil, sem usar ferramentas de API.**

**Critérios de Aceitação (O que define "Pronto"):**
- [✔] O projeto deve conter um `index.html` ou `index.php` que renderize um formulário.
- [✔] O JavaScript deve ser capaz de fazer uma requisição `POST` para o endpoint `/api/link`.
- [✔] O link curto resultante deve ser exibido em um campo de texto fácil de copiar.
- [✔] Deve haver tratamento visual de erros (ex: alerta se a URL for inválida).

---

### [Feature 2] Verificação do Status do Link

**Prioridade:** MÉDIA
**Status:** ✅ **CONCLUÍDO**

**História de Usuário:**
COMO UM **usuário**, EU QUERO **saber quantos cliques um link curto específico recebeu**, PARA QUE EU POSSA **monitorar a performance das minhas campanhas.**

**Critérios de Aceitação:**
- [✔] Criação de um novo endpoint `GET /api/stats/{short_code}`.
- [✔] O endpoint deve retornar um JSON com `clicks: <número>` e `original_url: <url_longa>`.
- [✔] Se o link não existir, deve retornar `404 Not Found`.
- [✔] Testes de Serviço criados e passando.

---

### [Feature 3] Definição de Validade/Expiração do Link (Obrigatório para Não Logados)

**Prioridade:** ALTA
**Status:** Pendente

**História de Usuário:**
COMO UM **usuário não logado**, EU QUERO **definir um tempo de expiração para o meu link**, PARA QUE EU POSSA **garantir que ele pare de funcionar após um período de tempo.**

**Critérios de Aceitação:**
- [ ] O `LinkService::createLink` deve aceitar um campo opcional `valid_until` (formato `YYYY-MM-DD HH:MM:SS`).
- [ ] A regra de expiração deve ser obrigatória para links criados via frontend (usuários não autenticados).
- [ ] O `LinkService::getAndIncrementClicks` deve verificar se o tempo atual (`NOW()`) é **menor** que `valid_until` antes de redirecionar.
- [ ] Testes Unitários criados para expiração.

---

### [Feature 4] Documentação Pública da API

**Prioridade:** BAIXA
**Status:** Pendente

**História de Usuário:**
COMO UM **desenvolvedor**, EU QUERO **acessar uma documentação pública dos endpoints**, PARA QUE EU POSSA **integrar a API sem usar a interface web.**

**Critérios de Aceitação:**
- [ ] Criação de um arquivo `api_docs.html` ou similar.
- [ ] O `Router` deve ter uma rota `GET /api/docs` que exibe a documentação.

---

## 2. 🔐 Épico: Robustez e Manutenção (Refatoração de Código)

### [Feature 5] Roteamento por Tabela e Regex (Refatoração)

**Prioridade:** ALTA
**Status:** ✅ **CONCLUÍDO**

**História de Usuário:**
COMO UM **mantenedor da API**, EU QUERO **que o roteamento seja baseado em tabela e Regex**, PARA QUE EU POSSA **facilmente adicionar rotas complexas com parâmetros (ex: URLs personalizadas) e garantir a escalabilidade.**

**Critérios de Aceitação:**
- [✔] `Router::run()` utiliza lógica de `foreach` e `preg_match` em vez de `switch/if`.
- [✔] `routes.php` é o único local para registro de rotas.
- [✔] Rotas com parâmetros (`/api/stats/(\w+)`) funcionam.

---

### [Feature 6] Geração de Hash de Tamanho Fixo

**Prioridade:** MÉDIA
**Status:** Pendente

**História de Usuário:**
COMO UM **mantenedor da API**, EU QUERO **garantir que todos os códigos curtos tenham exatamente 6 caracteres**, PARA QUE EU POSSA **manter um padrão consistente no banco de dados e na aparência das URLs.**

**Critérios de Aceitação:**
- [ ] A lógica de geração de hash em `LinkService` deve ser revisada para garantir um tamanho fixo (Ex: 6 caracteres).
- [ ] O teste unitário `LinkServiceTest::testLinkCreationAndRedirectionSuccess` deve ser atualizado para incluir a validação do tamanho do código curto.

---

### [Feature 7] Implementação de Exceções Dedicadas

**Prioridade:** MÉDIA
**Status:** Pendente

**História de Usuário:**
COMO UM **desenvolvedor front-end que consome a API**, EU QUERO **receber códigos de erro HTTP e mensagens claras para cada tipo de falha**, PARA QUE EU POSSA **tratar a resposta de forma programática e mostrar mensagens amigáveis.**

**Critérios de Aceitação:**
- [ ] Criar a classe `App\Exceptions\DatabaseException`.
- [ ] Criar a classe `App\Exceptions\ValidationException` (ou usá-la se já existir).
- [ ] O `Router` deve usar um bloco `try/catch` centralizado para capturar essas exceções e retornar JSON formatado (`400` para validação, `500` para DB).

---

## 3. 🛡️ Épico: Autenticação e Personalização (Novas Ideias)

### [Feature 8] Criação de Contas de Usuário (Registro/Login)

**Prioridade:** ALTA
**Status:** Pendente

**História de Usuário:**
COMO UM **usuário recorrente**, EU QUERO **ter uma conta para gerenciar meus links**, PARA QUE EU POSSA **acessar funcionalidades avançadas como links que não expiram e URLs personalizadas.**

**Critérios de Aceitação:**
- [ ] Criação da tabela `users` (nome, email, password_hash).
- [ ] Criação de endpoints `POST /api/register` e `POST /api/login`.
- [ ] Implementação de *Hashing* seguro de senhas (ex: `password_hash()`).
- [ ] Implementação de autenticação baseada em *Token* (Ex: JWT) ou Sessão.

---

### [Feature 9] Links Permanentes e URLs Personalizadas para Usuários

**Prioridade:** MÉDIA
**Status:** Pendente

**História de Usuário:**
COMO UM **usuário logado**, EU QUERO **criar links que nunca expiram e escolher o hash do meu link**, PARA QUE EU POSSA **manter URLs estáveis e fáceis de lembrar.**

**Critérios de Aceitação:**
- [ ] Usuários logados podem omitir a regra de expiração (`valid_until` = `NULL`).
- [ ] Usuários logados podem fornecer um `short_code` personalizado ao criar o link (se não estiver em uso).
- [ ] Rotas como `/api/link` devem exigir autenticação se o campo `custom_short_code` for fornecido.