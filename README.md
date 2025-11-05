# 🚀 Simples Assim URL (URL Shortener)

## 📌 Visão Geral

Bem-vindo ao **Simples Assim URL**! Este é um encurtador de links minimalista e eficiente, construído inteiramente com **PHP Puro (sem frameworks)**. O projeto foca em alta performance, código limpo, e na prova de que é possível ter uma arquitetura robusta e testável sem a complexidade de grandes frameworks.

### Principais Funcionalidades

- 🔗 **Criação de Links Curtos:** Geração de códigos únicos para URLs longas.
- 📊 **Estatísticas Públicas:** Endpoint dedicado para visualizar cliques e URL original.
- ⏳ **Expiração de Links:** Links criados por usuários não-autenticados têm validade máxima de 7 dias, garantindo a higiene do banco de dados.
- 🧪 **Testes Unitários:** Cobertura total das regras de negócio (LinkService) utilizando PHPUnit.

---

## 🛠️ Instalação e Configuração

### 1. Requisitos

- PHP 8.1+
- Composer
- Um banco de dados compatível com PDO (ex: SQLite, MySQL/MariaDB)

### 2. Configuração do Projeto

1.  **Instalar Dependências:**
    ```bash
    composer install
    ```
2.  **Configurar Banco de Dados:**
    - Crie um arquivo `.env` na raiz (se você usar Dotenv).
    - Garanta que a classe `App\Database` esteja configurada para a sua conexão (ex: SQLite para testes ou MySQL para produção).
3.  **Estrutura do DB:**
    - A tabela principal é `links`. Ela deve conter as colunas: `id`, `short_code`, `long_url`, `clicks`, `created_at`, e **`valid_until`** (para controle de expiração).

---

## 💻 Comandos de Desenvolvimento (Composer Scripts)

Para simplificar o desenvolvimento e a execução de tarefas, utilizamos scripts no `composer.json`.

| Comando                    | Descrição                                                                                      |
| :------------------------- | :--------------------------------------------------------------------------------------------- |
| `composer serve`           | Inicia o servidor web embutido do PHP (para desenvolvimento em `localhost:8080`).              |
| `composer test`            | **Roda todos os testes unitários e de integração (PHPUnit).**                                  |
| `composer lint`            | Verifica se há erros de sintaxe (parse errors) em todos os arquivos `.php` na pasta `src/`.    |

---

## 🌐 Endpoints da API

| Método | Rota                      | Descrição                                                                                                |
| :----- | :------------------------ | :------------------------------------------------------------------------------------------------------- |
| `GET`  | `/{short_code}`           | **Redireciona** para a URL longa, incrementando o contador de cliques.                                   |
| `POST` | `/api/link`               | Cria um novo link. Recebe `long_url` (string) e `valid_until` (string no formato `YYYY-MM-DD HH:MM:SS`). |
| `GET`  | `/api/stats/{short_code}` | Retorna estatísticas (`long_url`, `clicks`, `created_at`, `valid_until`).                                |

---
