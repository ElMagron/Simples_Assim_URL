# 🚀 Backlog do Produto: Simples Assim URL (URL Shortener)

Este documento lista as funcionalidades, melhorias e tarefas futuras para o projeto, priorizadas por valor para o usuário e esforço de implementação.

---

## 1. 🌐 Épico: Usabilidade e UX (Experiência do Usuário)

### [Feature 1] Página de Criação de Links (Frontend)

**Prioridade:** ALTA
**Status:** Planejado

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
**Status:** Planejado

**História de Usuário:**
COMO UM **usuário**, EU QUERO **saber quantos cliques um link curto específico recebeu**, PARA QUE EU POSSA **monitorar a performance das minhas campanhas.**

**Critérios de Aceitação:**
- [ ] Criação de um novo endpoint `GET /api/stats/{short_code}`.
- [ ] O endpoint deve retornar um JSON com `clicks: <número>` e `original_url: <url_longa>`.
- [ ] Se o link não existir, deve retornar `404 Not Found`.

---

## 2. 🔐 Épico: Robustez e Manutenção

### [Feature 3] Geração de Hash de Tamanho Fixo

**Prioridade:** MÉDIA
**Status:** Planejado

**História de Usuário:**
COMO UM **mantenedor da API**, EU QUERO **garantir que todos os códigos curtos tenham exatamente 6 caracteres**, PARA QUE EU POSSA **manter um padrão consistente no banco de dados e na aparência das URLs.**

**Critérios de Aceitação:**
- [ ] A lógica de geração de hash em `LinkService` deve ser revisada para garantir um tamanho fixo (Ex: 6 caracteres).
- [ ] O teste unitário `LinkServiceTest::testLinkCreationAndRedirectionSuccess` deve ser atualizado para incluir a validação do tamanho do código curto.

---

### [Feature 4] Implementação de Exceções Dedicadas

**Prioridade:** MÉDIA
**Status:** Planejado

**História de Usuário:**
COMO UM **desenvolvedor front-end que consome a API**, EU QUERO **receber códigos de erro HTTP e mensagens claras para cada tipo de falha**, PARA QUE EU POSSA **tratar a resposta de forma programática e mostrar mensagens amigáveis.**

**Critérios de Aceitação:**
- [ ] Criar a classe `App\Exceptions\DatabaseException`.
- [ ] Criar a classe `App\Exceptions\ValidationException` (ou usá-la se já existir).
- [ ] O `Router` deve usar um bloco `try/catch` centralizado para capturar essas exceções e retornar JSON formatado (`400` para validação, `500` para DB).