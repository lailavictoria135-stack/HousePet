# CHANGELOG — HousePet

Registro detalhado das decisões arquiteturais, correções, ajustes de segurança e revisões críticas realizadas durante o desenvolvimento da plataforma HousePet, conforme metodologia do IFRO de "Programar com IA de forma responsável".

---

## [1.0.0] — 2026-11-13

### 📋 1. Planejamento e Especificação Inicial
- **Decisão:** Manter stack pura (PHP 8.2 + MySQL/PDO + Vanilla JS + CSS3 nativo) sem frameworks pesados para garantir portabilidade acadêmica e zero atrito de instalação pelo avaliador.
- **Estruturação do Banco:** Definição do schema relacional com 6 tabelas (`usuarios`, `animais`, `adocoes`, `desaparecidos`, `denuncias`, `moderacoes`), assegurando integridade referencial com `ON DELETE CASCADE` e `ON DELETE SET NULL`.

### 🔍 2. Revisão Crítica do que a IA / Subagentes Propuseram (Supervisão Ativa)
Abaixo constam as inconsistências detectadas na proposta inicial dos subagentes e corrigidas manualmente durante a revisão:

1. **Correção de URLs Absolutas Inconsistentes:**
   - *Proposta do Agente:* Links com caminhos estáticos misturados como `/adocao/cadastrar.php`, `/dashboard/index.php`, `/denuncias/create.php` e `/auth/register.php`.
   - *Revisão Humana:* Corrigido para utilizar a variável dinâmica `$baseUrl = '/housePet2'` em todos os templates e ajustar as rotas conforme a especificação canônica (`auth/cadastro.php`, `usuario/dashboard.php`, `denuncias/cadastrar.php`).

2. **Incompatibilidade no Retorno da Função `uploadImage()`:**
   - *Proposta do Agente:* Em `adocao/cadastrar.php`, o código tentava ler `$uploadResult['success']` e `$uploadResult['filename']`.
   - *Revisão Humana:* A função utilitária em `includes/functions.php` retorna `?string` (o nome do arquivo gerado ou `null`). O código foi refatorado para tratar corretamente o retorno escalar e exibir mensagens de erro amigáveis caso o arquivo ultrapasse 5MB ou possua MIME type não permitido.

3. **Status de Moderação Incompletos nos ENUMs:**
   - *Proposta do Agente:* A tabela `animais` e `desaparecidos` não continha o status `'rejeitado'` em sua definição original de ENUM.
   - *Revisão Humana:* Adicionado `'rejeitado'` explicitamente nas tabelas `animais` e `desaparecidos` no script SQL e nas interfaces administrativas para que o fluxo de rejeição com justificativa funcionasse sem erro de truncamento de coluna.

4. **Compatibilidade de Chaves de Sessão:**
   - *Proposta do Agente:* O arquivo de login preenchia `$_SESSION['user_name']`, enquanto os includes verificavam `$_SESSION['user_nome']`.
   - *Revisão Humana:* Padronizado através da função centralizadora `loginUser()` em `includes/auth.php`, alimentando ambos os índices por garantia de compatibilidade regressiva.

5. **Parser Nativo de Arquivo `.env` com Fallback Resiliente:**
   - *Proposta do Agente:* Dependência de variáveis de ambiente de sistema via `getenv()` direto, que falhava caso o servidor web Apache não estivesse configurado com SetEnv.
   - *Revisão Humana:* Implementada função `loadEnvFile()` nativa em PHP em `config/database.php` que lê o `.env` linha a linha e tenta fallbacks inteligentes de senhas locais (ex: senha em branco do XAMPP e `alunoifro` do laboratório escolar).

6. **Implementação de Telas Faltantes:**
   - Foram identificadas telas essenciais que não haviam sido completadas pelos subagentes:
     - `desaparecidos/detalhes.php` (com botão interativo "Marcar como Encontrado")
     - `desaparecidos/editar.php`
     - `adocao/detalhes.php` (com fluxo formal de interesse e listagem de contatos para o tutor)
     - `adocao/editar.php` e `adocao/excluir.php`
     - `denuncias/view.php` (com histórico de auditoria)
     - `usuario/editar_perfil.php`
     - `admin/usuarios.php`, `admin/denuncias.php`, `admin/adocoes.php`, `admin/desaparecidos.php` e `admin/moderacao.php`
     - `index.php` (com métricas dinâmicas, cards reais e conscientização)

### 🔒 3. Implementação e Validação de Segurança (Anexo B)
- **Hash Bcrypt:** Implementado `password_hash($senha, PASSWORD_DEFAULT)` no cadastro e `password_verify()` no login.
- **Tokens CSRF:** Implementado `generateCSRFToken()` e `validateCSRFToken()` em todos os formulários POST.
- **Sanitização XSS:** Aplicação da função `sanitize()` (`htmlspecialchars(..., ENT_QUOTES | ENT_HTML5, 'UTF-8')`).
- **Sigilo do Denunciante:** Consultas da área pública e listagens anônimas nunca expõem nome, e-mail ou dados de contato do usuário que reportou maus-tratos.
- **Credenciais Protegidas:** O arquivo `.env` foi isolado no `.gitignore` e mantido fora do histórico público do GitHub.

### 🧪 4. Testes e Validação Técnica
- **Sintaxe PHP:** Executado `php -l` em todos os 30 arquivos PHP do projeto — 100% aprovados sem erros.
- **Banco de Dados Real:** Banco `housepet_db` criado, populado e testado com consultas reais no MySQL 8.0.
- **Fluxo do Administrador:** Usuário `admin@housepet.com` testado com sucesso no Painel de Moderação.

