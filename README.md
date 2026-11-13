# 🐾 HousePet — Plataforma de Proteção e Bem-Estar Animal

O **HousePet** é uma plataforma web completa voltada à proteção, acolhimento e bem-estar animal. O sistema integra, em um único ambiente moderno e acessível:
- O registro confidencial de **denúncias de maus-tratos**;
- A divulgação transparente de **animais disponíveis para adoção** responsável;
- O mural comunitário de **animais desaparecidos**.

O projeto foi desenvolvido como atividade acadêmica do **Instituto Federal de Educação, Ciência e Tecnologia de Rondônia (IFRO)**, no Curso Técnico em Informática Integrado ao Ensino Médio.

---

## 🎯 Objetivo Principal

Centralizar e organizar informações sobre proteção animal, substituindo a divulgação dispersa em redes sociais por um sistema com banco de dados estruturado, busca com filtros, validação de segurança e moderação de conteúdo.

---

## 🚀 Tecnologias Utilizadas

- **Linguagem Backend:** PHP 8+ (processamento, sessões, regras de negócio)
- **Banco de Dados:** MySQL / MariaDB via extensão PDO (segurança contra SQL Injection)
- **Frontend:** HTML5 semântico, CSS3 responsivo (Mobile-First) e JavaScript moderno (ES6+)
- **Ícones e Tipografia:** Font Awesome 6.5 (CDN) e Google Fonts (Poppins)
- **Versionamento:** Git e GitHub (`main`)

---

## 🛡️ Segurança Implementada

- **Senhas Criptografadas:** Armazenadas exclusivamente com hash seguro via `password_hash()` e autenticadas via `password_verify()`.
- **Prepared Statements (PDO):** 100% das consultas utilizam parâmetros preparados, eliminando vulnerabilidades a SQL Injection.
- **Proteção Contra CSRF:** Todos os formulários de submissão (POST) exigem e validam tokens criptográficos por sessão.
- **Sanitização Contra XSS:** Aplicação consistente de `htmlspecialchars()` em todo output HTML de variáveis dinâmicas.
- **Proteção ao Denunciante:** Os dados pessoais de quem registra denúncias são ocultados e nunca exibidos na área pública.
- **Upload Seguro:** Validação de formato MIME (JPEG, PNG, WEBP, GIF), limite de tamanho (5MB) e renomeação aleatória de arquivos.
- **Controle de Acesso (RBAC):** Proteção estrita de rotas com base no perfil do usuário (`usuario`, `moderador`, `admin`).

---

## 📂 Estrutura de Pastas

```text
housePet2/
│
├── config/
│   └── database.php                # Conexão PDO segura com MySQL
│
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css           # Folha de estilos completa e responsiva
│   │   ├── js/
│   │   │   └── main.js             # Validações, interações e manipulação do DOM
│   │   └── images/
│   │       └── uploads/            # Pastas de arquivos enviados por upload
│   │           ├── animais/
│   │           ├── denuncias/
│   │           └── desaparecidos/
│
├── includes/
│   ├── header.php                  # Navbar responsiva e cabeçalho global
│   ├── footer.php                  # Rodapé e scripts globais
│   ├── auth.php                    # Middlewares de controle de login e permissões
│   └── functions.php               # Utilitários globais (sanitização, CSRF, upload)
│
├── auth/
│   ├── login.php                   # Autenticação de usuários
│   ├── cadastro.php                # Criação de conta pública (perfil 'usuario')
│   └── logout.php                  # Encerramento seguro de sessão
│
├── denuncias/
│   ├── index.php                   # Canal público com orientações legais e avisos
│   ├── cadastrar.php               # Formulário para envio de denúncia
│   ├── minhas_denuncias.php        # Acompanhamento das denúncias do usuário
│   └── view.php                    # Visualização protegida de ocorrência
│
├── adocao/
│   ├── index.php                   # Catálogo de adoção com filtros
│   ├── cadastrar.php               # Formulário para cadastrar animal
│   ├── detalhes.php                # Perfil completo e envio de interesse de adoção
│   ├── editar.php                  # Edição dos dados pelo tutor
│   └── excluir.php                 # Exclusão segura de publicação
│
├── desaparecidos/
│   ├── index.php                   # Mural público com filtros de busca
│   ├── cadastrar.php               # Registro de pet desaparecido
│   ├── detalhes.php                # Informações, contato e botão "Encontrado"
│   └── editar.php                  # Edição dos dados pelo tutor
│
├── usuario/
│   ├── dashboard.php               # Painel pessoal com resumo de publicações e mensagens
│   └── editar_perfil.php           # Atualização de nome, e-mail e senha
│
├── admin/
│   ├── dashboard.php               # Painel de moderação com contadores e fila de triagem
│   ├── usuarios.php                # Gestão de contas, ativação e atribuição de papéis
│   ├── denuncias.php               # Triagem e aprovação de denúncias
│   ├── adocoes.php                 # Moderação de animais para adoção
│   ├── desaparecidos.php           # Moderação de registros de pets perdidos
│   └── moderacao.php               # Histórico e auditoria de ações de moderadores
│
├── database/
│   └── housepet_db.sql             # Estrutura completa DDL e dados iniciais
│
├── .env.example                    # Modelo de configuração de ambiente
├── .gitignore                      # Regras de exclusão do Git
├── README.md                       # Documentação principal
├── spec.md                         # Especificação técnica do sistema
└── index.php                       # Página inicial pública
```

---

## ⚙️ Requisitos para Execução

- Servidor web Apache com suporte a PHP 8.0 ou superior (ex: **XAMPP**, WampServer, Laragon).
- Sistema gerenciador de banco de dados **MySQL** ou **MariaDB**.
- Navegador web moderno (Chrome, Firefox, Edge, Safari).

---

## 🛠️ Como Instalar e Executar Localmente

### 1. Clonar ou copiar o projeto
Certifique-se de que a pasta do projeto esteja dentro do diretório `htdocs` do seu XAMPP:
```bash
# Caminho recomendado:
C:\xampp\htdocs\housePet2
```

### 2. Configurar o Banco de Dados MySQL
1. Inicie o **Apache** e o **MySQL** pelo Painel de Controle do XAMPP.
2. Acesse o **phpMyAdmin** pelo navegador: `http://localhost/phpmyadmin/`.
3. Crie um banco de dados chamado `housepet_db` com charset `utf8mb4_unicode_ci` (ou importe diretamente o arquivo).
4. Vá até a aba **Importar** e selecione o arquivo:
   ```text
   database/housepet_db.sql
   ```
5. Clique em **Executar** para criar todas as tabelas, índices e dados iniciais.

### 3. Configurar Variáveis de Ambiente
Por padrão, o arquivo `config/database.php` já possui fallback para o ambiente padrão do XAMPP (`host: localhost`, `user: root`, `password: vazio`). Se necessário, copie o arquivo `.env.example` para `.env` e configure conforme suas credenciais:
```ini
DB_HOST=localhost
DB_NAME=housepet_db
DB_USER=root
DB_PASS=
APP_URL=http://localhost/housePet2
```

### 4. Acessar o Sistema
Abra seu navegador e acesse a URL:
```text
http://localhost/housePet2/
```

---

## 🔑 Credenciais Padrão de Acesso

O script SQL já inclui um usuário administrador pré-configurado:

- **E-mail:** `admin@housepet.com`
- **Senha:** `Admin123!`
- **Perfil:** Administrador (`admin`)

> *Por questões de segurança, recomenda-se alterar a senha após o primeiro acesso na tela "Editar Perfil".*

---

## 🌐 Repositório GitHub

- **URL Remota:** [https://github.com/lailavictoria135-stack/HousePet.git](https://github.com/lailavictoria135-stack/HousePet.git)
- **Branch Principal:** `main`

---

## 🎓 Créditos e Informações Acadêmicas

- **Instituição:** Instituto Federal de Educação, Ciência e Tecnologia de Rondônia (IFRO)
- **Curso:** Técnico em Informática Integrado ao Ensino Médio
- **Disciplina:** Desenvolvimento de Sistemas Web
- **Ano:** 2025 / 2026
