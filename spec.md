# ESPECIFICAÇÃO FINAL DO SISTEMA (SPEC FINAL) — HOUSEPET

> **Atividade Acadêmica:** Curso Técnico em Informática Integrado ao Ensino Médio  
> **Instituição:** Instituto Federal de Rondônia (IFRO)  
> **Disciplina:** Desenvolvimento de Sistemas Web  
> **Metodologia:** "Programar com IA de forma responsável" (Planejamento, Revisão Humana Ativa, Teste e Documentação)

---

## 1. OBJETIVO DO PROJETO

O **HousePet** é uma plataforma web completa de proteção e bem-estar animal que centraliza três frentes essenciais:
1. **Canal de Denúncias de Maus-Tratos:** Registro seguro, confidencial e catalogado de casos de violência e negligência animal, com orientações sobre os canais oficiais (190, 181 e IBAMA).
2. **Catálogo de Adoção Responsável:** Divulgação transparente de animais resgatados, com filtros de busca e manifestação formal de interesse entre adotante e tutor.
3. **Mural de Animais Desaparecidos:** Canal ágil para divulgação comunitária de pets perdidos com alertas de localização e recurso para marcar o animal como encontrado.

**Objetivo Central:** Substituir a comunicação dispersa e efêmera de redes sociais por um sistema com dados estruturados, controle de acesso baseado em papéis, moderação de conteúdo e garantia de privacidade aos voluntários e denunciantes.

---

## 2. USUÁRIOS E PERFIS DE ACESSO

| Perfil | Nível de Acesso | Permissões no Sistema |
|---|---|---|
| **Visitante** | Não Autenticado | Acesso à Home, visualização do catálogo público de adoção, consulta ao mural de desaparecidos, leitura das diretrizes de denúncias e links institucionais. |
| **Usuário (`usuario`)** | Autenticado Comum | Cadastro de animais para adoção, cadastro de pets desaparecidos, envio de manifestação de interesse em adoção, registro de denúncias confidenciais, acompanhamento de publicações no painel pessoal e atualização do próprio perfil. |
| **Moderador (`moderador`)** | Administrativo | Acesso ao Painel de Moderação (`/admin/`). Triagem, aprovação e rejeição de denúncias, anúncios de adoção e registros de desaparecidos, com registro obrigatório de justificativa. |
| **Administrador (`admin`)** | Total / Superuser | Todas as permissões do moderador, acrescidas da gestão central de usuários (ativação/desativação de contas e promoção/rebaixamento de papéis). |

---

## 3. CASOS DE USO PRINCIPAIS

- **UC01 — Autenticar Usuário:** Permite acesso ao sistema validando e-mail e hash da senha via `password_verify()`.
- **UC02 — Cadastrar Nova Conta Pública:** Cria conta com validação de dados únicos (e-mail) e atribui perfil automático de `usuario`.
- **UC03 — Cadastrar Animal para Adoção:** Tutor envia dados (espécie, porte, localização, foto, contato) que entram com status `pendente`.
- **UC04 — Manifestar Interesse em Adoção:** Usuário interessado envia mensagem motivacional ao tutor responsável.
- **UC05 — Cadastrar Animal Desaparecido:** Tutor publica pet perdido com ponto geográfico e contato de urgência.
- **UC06 — Marcar Animal como Encontrado:** Tutor ou moderador atualiza o status do pet para `encontrado`, finalizando a busca.
- **UC07 — Registrar Ocorrência de Maus-Tratos:** Envio confidencial de relato, endereço e evidência fotográfica com proteção à identidade do denunciante.
- **UC08 — Acompanhar Minhas Denúncias:** Usuário visualiza o histórico e status das suas ocorrências (`pendente`, `em_analise`, `aprovada`, `rejeitada`).
- **UC09 — Moderar Publicações:** Moderador avalia anúncios e denúncias, decidindo por aprovação ou rejeição fundamentada.
- **UC10 — Gerenciar Contas de Usuários:** Administrador ativa, desativa e altera perfis de acesso.

---

## 4. REQUISITOS DO SISTEMA

### 4.1 Requisitos Funcionais (RF)
- **RF01:** O sistema deve permitir cadastro de usuários com nome, e-mail e senha criptografada.
- **RF02:** O sistema não deve permitir e-mails duplicados no cadastro.
- **RF03:** O sistema deve manter sessões de usuário ativas e permitir logout seguro.
- **RF04:** O sistema deve permitir o upload de fotografias nos formatos JPEG, PNG, WEBP e GIF até o limite de 5MB.
- **RF05:** O catálogo de adoção deve permitir filtros por espécie, porte e localização.
- **RF06:** O sistema deve ocultar dados cadastrais do denunciante nas áreas públicas.
- **RF07:** O tutor deve conseguir editar as informações dos seus próprios animais cadastrados.
- **RF08:** O tutor deve poder marcar o animal desaparecido como "Encontrado".
- **RF09:** O sistema deve impedir que usuários manifestem interesse repetido no mesmo animal.
- **RF10:** A área administrativa deve ser inacessível para usuários com perfil comum (`usuario`).
- **RF11:** O moderador deve poder alterar o status de denúncias para: `pendente`, `em_analise`, `aprovada` ou `rejeitada`.
- **RF12:** Toda ação de moderação deve registrar o ID do moderador, data/hora e justificativa em `moderacoes`.
- **RF13:** O administrador deve poder ativar ou desativar contas de usuários.
- **RF14:** O usuário autenticado deve poder atualizar seu nome, e-mail e trocar sua senha informando a senha atual.
- **RF15:** A página inicial deve exibir contadores dinâmicos e cards reais do banco de dados.

### 4.2 Requisitos Não Funcionais (RNF)
- **RNF01 (Segurança):** 100% das consultas SQL devem utilizar Prepared Statements com PDO.
- **RNF02 (Segurança):** Senhas devem ser armazenadas com hash forte via `password_hash()` (Bcrypt).
- **RNF03 (Segurança):** Proteção obrigatória contra CSRF em todas as submissões POST.
- **RNF04 (Segurança):** Sanitização contra XSS em todas as saídas de variáveis em HTML via `htmlspecialchars()`.
- **RNF05 (Performance):** Índices em banco de dados em colunas de alta consulta (`email`, `status`, `usuario_id`, `data_cadastro`).
- **RNF06 (Usabilidade):** Interface responsiva compatível com dispositivos móveis, tablets e computadores (Mobile-First).
- **RNF07 (Compatibilidade):** Sistema compatível com PHP 8.0+ e MySQL 8.0+ / MariaDB 10.4+.
- **RNF08 (Arquitetura):** Código organizado de forma modular, separando configuração, includes, regras de negócio e assets.

---

## 5. DECISÃO DE STACK E ARQUITETURA

- **Linguagem Backend:** PHP 8.2 puro (sem frameworks pesados para garantir portabilidade acadêmica e compreensão didática do código).
- **Banco de Dados:** MySQL 8.0 / MariaDB com extensão PDO.
- **Frontend:** HTML5 semântico, CSS3 com variáveis customizadas (tons de verde `#2d8c4e`, `#1a5c32`, `#e8f5e9`) e JavaScript Vanilla.
- **Bibliotecas CDN:** Google Fonts (Poppins 300, 400, 500, 600, 700) e Font Awesome 6.5.

---

## 6. INSTALAÇÃO E EXECUÇÃO (GUIA PASSO A PASSO PARA O PROFESSOR)

### 6.1 Ambiente
- **Linguagem:** PHP 8.2 ou superior (testado e homologado no PHP 8.2.12).
- **Servidor Web Local:** Apache através do **XAMPP** (ou servidor embutido do PHP: `php -S localhost:8000`).
- **Dependências / Bibliotecas:** **Nenhuma dependência local via Composer ou npm é necessária.** O sistema é autocontido e pronto para uso imediato após o clone.
- **Sistema Operacional Homologado:** Windows 10 / Windows 11 (totalmente compatível com distribuições Linux e macOS).

### 6.2 Banco de Dados
- **SGBD:** MySQL 8.0+ ou MariaDB 10.4+.
- **Nome do Banco:** `housepet_db`
- **Charset / Collation:** `utf8mb4` / `utf8mb4_unicode_ci`
- **Script SQL:** Localizado no repositório em `database/housepet_db.sql` (e cópia em `database/schema.sql`).
- **Dados Iniciais:** O script já cria a estrutura completa e insere o usuário Administrador padrão do sistema.

#### Modelo de Dados e Relacionamentos:

| Tabela | Campos Principais | Chave Primária | Relacionamentos (Chaves Estrangeiras) |
|---|---|---|---|
| **usuarios** | id, nome, email, senha, tipo_usuario, ativo, data_cadastro | `id` | — |
| **denuncias** | id, usuario_id, descricao, tipo_ocorrencia, localizacao, data_ocorrencia, informacoes_animal, imagem, status, data_cadastro | `id` | `usuario_id` → `usuarios.id` (ON DELETE SET NULL) |
| **animais** | id, usuario_id, nome, especie, raca, idade, sexo, porte, descricao, imagem, localizacao, contato, status, data_cadastro | `id` | `usuario_id` → `usuarios.id` (ON DELETE SET NULL) |
| **adocoes** | id, animal_id, usuario_interessado_id, mensagem, status, data_cadastro | `id` | `animal_id` → `animais.id` (ON DELETE CASCADE)<br>`usuario_interessado_id` → `usuarios.id` (ON DELETE CASCADE) |
| **desaparecidos** | id, usuario_id, nome_animal, especie, raca, caracteristicas, imagem, local_desaparecimento, data_desaparecimento, contato, status, data_cadastro | `id` | `usuario_id` → `usuarios.id` (ON DELETE SET NULL) |
| **moderacoes** | id, moderador_id, tipo_conteudo, conteudo_id, decisao, observacao, data_moderacao | `id` | `moderador_id` → `usuarios.id` (ON DELETE CASCADE) |

### 6.3 Variáveis de Ambiente
O repositório possui o arquivo de modelo [.env.example](file:///c:/xampp/htdocs/housePet2/.env.example):
```ini
DB_HOST=localhost
DB_NAME=housepet_db
DB_USER=root
DB_PASS=
APP_URL=http://localhost/housePet2
```

| Variável | Significado / Finalidade | Como Preencher Localmente |
|---|---|---|
| `DB_HOST` | Host do banco de dados MySQL | Preencher com `localhost` ou `127.0.0.1` |
| `DB_NAME` | Nome do schema do banco | Manter como `housepet_db` |
| `DB_USER` | Usuário de acesso ao MySQL | Padrão do XAMPP: `root` |
| `DB_PASS` | Senha do usuário do MySQL | Em branco no XAMPP padrão, ou a senha do seu ambiente (ex: `alunoifro`) |
| `APP_URL` | Caminho base da URL no navegador | `http://localhost/housePet2` |

> *Nota de Resiliência:* O arquivo `config/database.php` possui parser nativo para carregar o `.env` e inclui mecanismo de fallback de senhas locais.

### 6.4 Passo a Passo de Instalação (Do Git Clone ao Navegador)

1. **Clonar o Repositório:**  
   Abra o terminal na pasta `htdocs` do seu XAMPP (ex: `C:\xampp\htdocs\`):
   ```bash
   git clone https://github.com/lailavictoria135-stack/HousePet.git housePet2
   ```

2. **Entrar no Diretório:**
   ```bash
   cd housePet2
   ```

3. **Instalar Dependências:**
   Não há dependências externas para instalar. O projeto roda com PHP nativo e CDNs públicas.

4. **Configurar as Variáveis de Ambiente:**
   Copie o arquivo de exemplo para o arquivo definitivo `.env`:
   ```bash
   cp .env.example .env
   ```
   *(No Windows PowerShell: `Copy-Item .env.example .env`)*

5. **Criar o Banco de Dados e Importar o Script:**
   - Inicie o **Apache** e o **MySQL** pelo XAMPP Control Panel.
   - Abra o **phpMyAdmin** em `http://localhost/phpmyadmin/`.
   - Crie o banco `housepet_db` com charset `utf8mb4_unicode_ci`.
   - Clique na aba **Importar**, selecione `database/housepet_db.sql` (ou `database/schema.sql`) e execute.

6. **Abrir a Aplicação no Navegador:**
   Acesse o endereço local:
   ```text
   http://localhost/housePet2/
   ```

---

## 7. COMO TESTAR O SISTEMA

### 7.1 Usuário de Teste Pré-Configurado
- **E-mail:** `admin@housepet.com`
- **Senha:** `Admin123!`
- **Perfil:** Administrador (`admin`)

*(Você também pode clicar em "Cadastrar" na navbar para criar uma conta de usuário comum e testar a visão restrita).*

### 7.2 Roteiro do Fluxo Principal (Ordem de Teste)

1. **Navegação Pública:**
   - Acesse a Home (`/`). Veja as estatísticas dinâmicas, seção de conscientização e canais oficiais.
   - Navegue por **Adoção** (`/adocao/index.php`) e teste os filtros por espécie e localização.
   - Navegue por **Desaparecidos** (`/desaparecidos/index.php`) e consulte os registros.
   - Acesse **Denúncias** (`/denuncias/index.php`) e leia o aviso legal sobre a Polícia Militar (190) e Disque Denúncia (181).

2. **Login no Sistema:**
   - Clique em **Entrar** (`/auth/login.php`) e faça login com `admin@housepet.com` / `Admin123!`.

3. **Publicação de Conteúdo:**
   - Acesse **Cadastrar Animal** (`/adocao/cadastrar.php`), preencha os dados e anexe uma fotografia.
   - Acesse **Divulgar Desaparecido** (`/desaparecidos/cadastrar.php`) e registre um pet perdido.
   - Na página de detalhes do pet desaparecido (`/desaparecidos/detalhes.php`), teste o botão **"Marcar como Encontrado"**.

4. **Envio e Triagem de Denúncia:**
   - Acesse **Fazer Denúncia** (`/denuncias/cadastrar.php`), relate um fato e anexe evidência.
   - Acesse **Minhas Denúncias** (`/denuncias/minhas_denuncias.php`) para acompanhar o protocolo.
   - Abra os detalhes da ocorrência (`/denuncias/view.php`) e confira o sigilo dos dados do denunciante.

5. **Ações de Moderação e Auditoria:**
   - Acesse o **Painel Admin** (`/admin/dashboard.php`).
   - Vá em **Moderação de Denúncias** (`/admin/denuncias.php`) e tome uma decisão (aprovar, colocar em análise ou rejeitar com justificativa).
   - Vá em **Moderação de Adoções** (`/admin/adocoes.php`) e aprove o animal para o catálogo público.
   - Vá em **Histórico de Moderação** (`/admin/moderacao.php`) e comprove o log de auditoria registrado.
   - Vá em **Gestão de Usuários** (`/admin/usuarios.php`) e teste a ativação/desativação de contas e a promoção de papéis.

---

## 8. CHECKLIST DE SEGURANÇA (ANEXO B DA APOSTILA)

- [x] **Senhas:** Criptografia com algoritmo Bcrypt via `password_hash()` no cadastro e `password_verify()` na autenticação.
- [x] **SQL Injection:** 100% das consultas tratadas com Prepared Statements via PDO.
- [x] **XSS (Cross-Site Scripting):** Sanitização estrita em todos os outputs HTML com `htmlspecialchars(..., ENT_QUOTES | ENT_HTML5, 'UTF-8')`.
- [x] **CSRF (Cross-Site Request Forgery):** Tokens gerados com `bin2hex(random_bytes(32))` e validados com `hash_equals()` em formulários POST.
- [x] **Upload Seguro:** Validação de formato MIME no servidor (`FILEINFO_MIME_TYPE`), teto de 5MB e renomeação aleatória de arquivos.
- [x] **Controle de Sessão:** `session_regenerate_id(true)` no login e destruição segura dos cookies de sessão no logout.
- [x] **Controle de Acesso por Papel:** Verificação de permissões em todas as rotas restritas (`requireLogin()`, `requireAdmin()`).
- [x] **Proteção de Dados Sensíveis:** Ocultação de dados cadastrais de denunciantes na camada de apresentação.
- [x] **Credenciais Fora do Repositório:** Arquivo `.env` isolado no `.gitignore`, versionando apenas o [.env.example](file:///c:/xampp/htdocs/housePet2/.env.example).

---

## 9. PROBLEMAS CONHECIDOS E LIMITAÇÕES ATUAIS

- O disparo de e-mails transacionais (notificações por SMTP) ainda não foi integrado, sendo o acompanhamento realizado diretamente pelo painel do usuário.
- O upload de imagens armazena os arquivos em diretório local do servidor web (`public/assets/images/uploads/`); em um ambiente de produção em nuvem distribuída, recomenda-se migrar o armazenamento para um bucket S3/Cloud Storage.
- A geolocalização é textual (cidade, bairro, endereço); uma versão futura poderá incorporar mapas interativos via OpenStreetMap / Leaflet.
