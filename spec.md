# ESPECIFICAÇÃO FINAL DO SISTEMA — HOUSEPET

> **Atividade Acadêmica:** Curso Técnico em Informática Integrado ao Ensino Médio  
> **Instituição:** Instituto Federal de Rondônia (IFRO)  
> **Disciplina:** Desenvolvimento de Sistemas Web  
> **Metodologia:** Programar com IA de forma responsável (Planejamento, Revisão Humana, Teste e Publicação)

---

## 1. INFORMAÇÕES DO PROJETO E OBJETIVO

- **Nome da Aplicação:** HousePet
- **Propósito:** Plataforma web integrada de proteção e bem-estar animal que centraliza denúncias de maus-tratos, catálogo de animais disponíveis para adoção responsável e mural de animais desaparecidos.
- **Público-alvo:** Tutores, resgatistas independentes, pessoas que presenciam animais em risco, adotantes voluntários e moderadores.
- **Objetivo Principal:** Substituir a divulgação dispersa e não moderada em redes sociais e grupos de mensagens por uma aplicação estruturada com banco de dados relacional, controle de acesso e auditoria.

---

## 2. USUÁRIOS E PERFIS DE ACESSO

1. **Visitante (Não Autenticado):**
   - Visualiza a página inicial, notícias institucionais e canais oficiais de denúncia.
   - Consulta o catálogo de animais para adoção e o mural de animais desaparecidos com filtros.
   - Não pode publicar ou solicitar adoção sem login.
2. **Usuário Comum (`usuario`):**
   - Registra denúncias com garantia de sigilo de identidade.
   - Cadastra animais para adoção e pets desaparecidos (entrando na fila de moderação).
   - Manifesta interesse formal de adoção enviando mensagem motivacional ao tutor.
   - Gerencia suas publicações, contatos de interessados e atualiza dados do seu perfil.
   - Pode marcar pet desaparecido como "Encontrado".
3. **Moderador e Administrador (`moderador`, `admin`):**
   - Acesso exclusivo ao Painel de Moderação (`/admin/`).
   - Avalia, aprova ou rejeita denúncias, anúncios de adoção e pets desaparecidos.
   - Registra justificativa em cada ação, mantendo trilha de auditoria na tabela `moderacoes`.
   - Administradores (`admin`) possuem gestão completa de usuários (ativação e papéis).

---

## 3. INSTALAÇÃO E EXECUÇÃO (GUIA DO PROFESSOR / AVALIADOR)

Esta seção foi concebida para que qualquer avaliador consiga clonar e colocar o sistema para rodar em um computador novo sem necessidade de intervenção externa.

### 3.1 Ambiente
- **Linguagem Backend:** PHP 8.2 ou superior (testado em PHP 8.2.12).
- **Servidor Web Local:** Apache via **XAMPP** (ou servidor embutido do PHP: `php -S localhost:8000`).
- **Extensões PHP obrigatórias:** `pdo_mysql`, `fileinfo`, `mbstring`, `session`.
- **Bibliotecas / Dependências externas:** Nenhuma dependência local necessária via Composer ou npm. O sistema utiliza PHP puro moderno e bibliotecas de interface consumidas diretamente via CDN confiável (Google Fonts Poppins e Font Awesome 6.5).
- **Sistema Operacional Testado:** Windows 10 / Windows 11 (compatível com Linux e macOS).

### 3.2 Banco de Dados
- **SGBD Utilizado:** MySQL 8.0+ ou MariaDB 10.4+.
- **Nome do Banco de Dados:** `housepet_db`
- **Charset / Collation:** `utf8mb4` / `utf8mb4_unicode_ci`
- **Caminho do Script SQL:** `database/housepet_db.sql` (ou `database/schema.sql`).
- **Como Executar:** Pode ser importado via phpMyAdmin (`http://localhost/phpmyadmin/`), MySQL Workbench ou pelo terminal com:
  ```bash
  mysql -u root -p housepet_db < database/housepet_db.sql
  ```
- **Dados Iniciais:** O script já insere automaticamente o usuário Administrador padrão do sistema.

#### Modelo de Dados e Relacionamentos:

| Tabela | Campos Principais | Chave Primária | Relacionamentos (Chaves Estrangeiras) |
|---|---|---|---|
| **usuarios** | id, nome, email, senha, tipo_usuario, ativo, data_cadastro | `id` | — |
| **denuncias** | id, usuario_id, descricao, tipo_ocorrencia, localizacao, data_ocorrencia, informacoes_animal, imagem, status, data_cadastro | `id` | `usuario_id` → `usuarios.id` (ON DELETE SET NULL) |
| **animais** | id, usuario_id, nome, especie, raca, idade, sexo, porte, descricao, imagem, localizacao, contato, status, data_cadastro | `id` | `usuario_id` → `usuarios.id` (ON DELETE SET NULL) |
| **adocoes** | id, animal_id, usuario_interessado_id, mensagem, status, data_cadastro | `id` | `animal_id` → `animais.id` (ON DELETE CASCADE)<br>`usuario_interessado_id` → `usuarios.id` (ON DELETE CASCADE) |
| **desaparecidos** | id, usuario_id, nome_animal, especie, raca, caracteristicas, imagem, local_desaparecimento, data_desaparecimento, contato, status, data_cadastro | `id` | `usuario_id` → `usuarios.id` (ON DELETE SET NULL) |
| **moderacoes** | id, moderador_id, tipo_conteudo, conteudo_id, decisao, observacao, data_moderacao | `id` | `moderador_id` → `usuarios.id` (ON DELETE CASCADE) |

### 3.3 Variáveis de Ambiente
O repositório disponibiliza o modelo de configuração [.env.example](file:///c:/xampp/htdocs/housePet2/.env.example). Para rodar localmente, copie o arquivo para `.env` na raiz do projeto.

| Variável | Descrição / Finalidade | Exemplo de Preenchimento Local |
|---|---|---|
| `DB_HOST` | Endereço do servidor MySQL | `localhost` ou `127.0.0.1` |
| `DB_NAME` | Nome do banco de dados relacional | `housepet_db` |
| `DB_USER` | Usuário com permissão no MySQL | `root` |
| `DB_PASS` | Senha do usuário do banco | *(vazio no XAMPP padrão, ou a senha do seu SGBD)* |
| `APP_URL` | URL base da aplicação no navegador | `http://localhost/housePet2` |

> *Nota Técnica:* O arquivo `config/database.php` possui parser nativo para o arquivo `.env` e inclui fallback de conexões resiliente para ambientes escolares de laboratório.

### 3.4 Passo a Passo de Instalação Completo

1. **Clonar o Repositório:**
   Abra o terminal dentro da pasta `htdocs` do seu servidor web (ex: `C:\xampp\htdocs\`):
   ```bash
   git clone https://github.com/lailavictoria135-stack/HousePet.git housePet2
   ```

2. **Entrar na pasta do projeto:**
   ```bash
   cd housePet2
   ```

3. **Configurar as Variáveis de Ambiente:**
   Copie o arquivo `.env.example` para `.env`:
   ```bash
   cp .env.example .env
   ```
   *(No Windows PowerShell: `Copy-Item .env.example .env`)*

4. **Criar e Importar o Banco de Dados:**
   - Inicie o **Apache** e o **MySQL** no XAMPP Control Panel.
   - Abra o phpMyAdmin (`http://localhost/phpmyadmin/`).
   - Crie o banco de dados `housepet_db` (Collation: `utf8mb4_unicode_ci`).
   - Na aba **Importar**, selecione o arquivo `database/housepet_db.sql` e execute.

5. **Acessar o Sistema no Navegador:**
   Abra seu navegador no endereço:
   ```text
   http://localhost/housePet2/
   ```

---

## 4. ROTEIRO DE TESTES E FLUXO PRINCIPAL

### 4.1 Usuário Pré-Cadastrado para Avaliação
- **E-mail:** `admin@housepet.com`
- **Senha:** `Admin123!`
- **Perfil:** Administrador (`admin`)

*(Caso queira criar uma conta comum nova, utilize o botão "Cadastrar" na barra de navegação).*

### 4.2 Roteiro do Fluxo Principal (Passo a Passo)

1. **Navegação Pública:**
   - Acesse a página inicial (`/`). Veja os destaques, métricas dinâmicas, seção de conscientização e canais oficiais.
   - Acesse a página **Adoção** (`/adocao/index.php`) e use os filtros por espécie, porte e localização.
   - Acesse a página **Desaparecidos** (`/desaparecidos/index.php`) e teste a busca geográfica.
   - Acesse a página **Denúncias** (`/denuncias/index.php`) e leia o aviso legal sobre os canais oficiais (190, 181, Ibama).

2. **Autenticação e Cadastro:**
   - Clique em **Entrar** (`/auth/login.php`) e faça login com as credenciais do Administrador (`admin@housepet.com` / `Admin123!`).

3. **Publicação e Interesses:**
   - Acesse o menu do usuário e clique em **Cadastrar Animal** (`/adocao/cadastrar.php`). Preencha os campos e anexe uma foto.
   - Acesse a visualização do animal em **Adoção** e veja os botões de ação e a área de manifestação de interesse.
   - Registre um pet em **Divulgar Desaparecido** (`/desaparecidos/cadastrar.php`).
   - Na tela de detalhes do pet desaparecido, teste o botão **"Marcar como Encontrado"**.

4. **Registro de Denúncia:**
   - Acesse **Fazer Denúncia** (`/denuncias/cadastrar.php`), preencha o relato, localização e anexo.
   - Acesse **Minhas Denúncias** (`/denuncias/minhas_denuncias.php`) e veja o acompanhamento com status.
   - Abra os detalhes da denúncia (`/denuncias/view.php`) e comprove que o sigilo dos dados do denunciante é mantido.

5. **Painel de Moderação e Auditoria:**
   - Como Administrador, acesse **Painel Admin** (`/admin/dashboard.php`).
   - Acesse **Moderação de Denúncias** (`/admin/denuncias.php`) e aprove/rejeite a ocorrência cadastrada informando uma observação.
   - Acesse **Moderação de Adoções** (`/admin/adocoes.php`) e aprove o animal para o catálogo público.
   - Acesse **Histórico de Moderação** (`/admin/moderacao.php`) e audite o log com a data, moderador e justificativa gravada.
   - Acesse **Gestão de Usuários** (`/admin/usuarios.php`) e comprove a ativação/desativação e promoção de papéis.

---

## 5. CHECKLIST DE SEGURANÇA CONFERIDO (ANEXO B DA APOSTILA)

- [x] **Senhas:** Criptografadas com algoritmo forte via `password_hash()` (Bcrypt). Nenhuma senha em texto puro no banco.
- [x] **SQL Injection:** 100% das consultas utilizam Prepared Statements com PDO. Parâmetros dinâmicos nunca são concatenados diretamente na query.
- [x] **Cross-Site Scripting (XSS):** Todo output dinâmico em HTML passa por sanitização via `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- [x] **Cross-Site Request Forgery (CSRF):** Todos os formulários que alteram estado utilizam tokens criptográficos por sessão via `validateCSRFToken()`.
- [x] **Upload de Arquivos:** Restrição de tipo MIME verificado no servidor (JPEG, PNG, WEBP, GIF), limite rígido de 5MB e renomeação com hash randômico (`bin2hex(random_bytes(16))`).
- [x] **Controle de Sessão:** `session_regenerate_id(true)` no login para prevenir fixação de sessão; expiração completa no logout.
- [x] **Controle de Acesso:** Verificação rigorosa do perfil do usuário em cada tela protegida (`requireLogin()`, `requireAdmin()`).
- [x] **Privacidade de Denúncias:** Ocultação de identificadores do denunciante em consultas públicas.
- [x] **Credenciais Fora do Repositório:** O arquivo `.env` está devidamente listado no `.gitignore`. Apenas o modelo [.env.example](file:///c:/xampp/htdocs/housePet2/.env.example) é rastreado pelo Git.

---

## 6. LIMITAÇÕES E MELHORIAS FUTURAS

- Envio de notificações automáticas por e-mail (SMTP) a cada mudança de status de moderação ou interesse recebido.
- Integração com mapas interativos (Leaflet / OpenStreetMap) para geolocalização visual exata do ponto onde o animal foi visto.
- Compressão e conversão automática de imagens no servidor para o formato otimizado WebP.
