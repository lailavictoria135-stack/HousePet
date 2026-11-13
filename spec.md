# ESPECIFICAÇÃO COMPLETA DO SISTEMA — HOUSEPET

## 1. INFORMAÇÕES DO PROJETO

- **Nome do Sistema:** HousePet
- **Propósito:** Plataforma web de proteção e bem-estar animal que reúne registro de denúncias de maus-tratos, divulgação de animais disponíveis para adoção e divulgação de animais desaparecidos.
- **Público-alvo:** Tutores de animais, pessoas que resgatam ou encontram animais em risco, voluntários, protetores independentes, interessados em adoção responsável e equipe de moderação/administração.
- **Contexto:** Atividade acadêmica do Instituto Federal de Educação, Ciência e Tecnologia de Rondônia (IFRO), Curso Técnico em Informática Integrado ao Ensino Médio.

---

## 2. STACK TECNOLÓGICA E REQUISITOS TÉCNICOS

- **Linguagem Backend:** PHP 8+ (sem frameworks externos)
- **Banco de Dados:** MySQL / MariaDB via extensão PDO com prepared statements
- **Frontend:** HTML5 semântico, CSS3 responsivo (Mobile First), JavaScript puro (ES6+)
- **Bibliotecas/CDNs:** Google Fonts (Poppins), Font Awesome 6.5 (ícones)
- **Segurança:**
  - Hashing de senhas via `password_hash()` com algoritmo padrão Bcrypt / Argon2
  - Validação e sanitização no servidor (`htmlspecialchars()`, `trim()`, tipos estritos)
  - Prevenção contra SQL Injection via Prepared Statements em 100% das consultas
  - Prevenção contra CSRF via tokens criptográficos por sessão
  - Proteção e ocultação dos dados pessoais de denunciantes na área pública
  - Validação estrita de upload de imagens (MIME type, extensão, limite de 5MB, renomeação com hash/UUID aleatório)
  - Controle de acesso baseado em papéis (`usuario`, `moderador`, `admin`)
  - Gestão segura de sessões PHP (`session_regenerate_id()`, destruição completa em logout)
- **Versionamento:** Git na branch principal `main`, conectado ao repositório remoto `https://github.com/lailavictoria135-stack/HousePet.git`

---

## 3. ARQUITETURA E ESTRUTURA DE DIRETÓRIOS

```
housePet2/
│
├── config/
│   └── database.php                # Conexão PDO segura e configuração de ambiente
│
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css           # Estilos globais, temas, componentes e responsividade
│   │   ├── js/
│   │   │   └── main.js             # Validações, interatividade, alertas, preview de imagens
│   │   └── images/                 # Assets gráficos e imagens estáticas
│   └── uploads/                    # Imagens enviadas por usuários (animais, denúncias)
│       ├── animais/
│       ├── denuncias/
│       └── desaparecidos/
│
├── includes/
│   ├── header.php                  # Cabeçalho global, navbar responsiva, flash messages
│   ├── footer.php                  # Rodapé com links institucionais e scripts
│   ├── auth.php                    # Middlewares de autorização (requireLogin, requireAdmin)
│   └── functions.php               # Utilitários de sanitização, uploads, CSRF e formatação
│
├── auth/
│   ├── login.php                   # Autenticação de usuários
│   ├── cadastro.php                # Registro de novos usuários comuns
│   └── logout.php                  # Encerramento seguro de sessão
│
├── denuncias/
│   ├── index.php                   # Visualização informativa pública e aviso oficial
│   ├── cadastrar.php               # Formulário de registro de denúncias
│   └── minhas_denuncias.php        # Acompanhamento exclusivo do denunciante
│
├── adocao/
│   ├── index.php                   # Catálogo público com filtros e busca
│   ├── cadastrar.php               # Cadastro de animal para doação
│   ├── detalhes.php                # Perfil completo e manifestação de interesse
│   └── editar.php                  # Edição e atualização de status pelo tutor
│
├── desaparecidos/
│   ├── index.php                   # Mural público com filtros de busca
│   ├── cadastrar.php               # Registro de animal desaparecido
│   ├── detalhes.php                # Informações, contato e botão "Marcar como Encontrado"
│   └── editar.php                  # Edição das informações pelo tutor
│
├── usuario/
│   ├── dashboard.php               # Painel pessoal, publicações, denúncias e mensagens
│   └── editar_perfil.php           # Atualização de dados cadastrais e senha
│
├── admin/
│   ├── dashboard.php               # Métricas, contadores e atalhos de moderação
│   ├── usuarios.php                # Gestão de usuários, papéis e ativação
│   ├── denuncias.php               # Moderação de denúncias (aprovar/rejeitar/análise)
│   ├── adocoes.php                 # Moderação e acompanhamento de adoções
│   ├── desaparecidos.php           # Moderação de registros de desaparecidos
│   └── moderacao.php               # Log e histórico de ações de moderadores
│
├── database/
│   └── housepet_db.sql             # Script DDL/DML completo e dados iniciais
│
├── .env.example                    # Exemplo de configuração de variáveis de ambiente
├── .gitignore                      # Regras de exclusão do controle de versão
├── README.md                       # Documentação técnica e guia de instalação
├── spec.md                         # Este documento de especificação
└── index.php                       # Página inicial institucional e destaques
```

---

## 4. MODELO DE DADOS (MYSQL)

### Tabelas do Sistema:

1. **usuarios**
   - `id` (INT PK AUTO_INCREMENT)
   - `nome` (VARCHAR(100) NOT NULL)
   - `email` (VARCHAR(150) NOT NULL UNIQUE)
   - `senha` (VARCHAR(255) NOT NULL - hash Bcrypt)
   - `tipo_usuario` (ENUM('usuario','moderador','admin') NOT NULL DEFAULT 'usuario')
   - `ativo` (TINYINT(1) NOT NULL DEFAULT 1)
   - `data_cadastro` (TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)

2. **denuncias**
   - `id` (INT PK AUTO_INCREMENT)
   - `usuario_id` (INT NULL FK -> usuarios(id) ON DELETE SET NULL)
   - `descricao` (TEXT NOT NULL)
   - `tipo_ocorrencia` (VARCHAR(100) NOT NULL)
   - `localizacao` (VARCHAR(255) NOT NULL)
   - `data_ocorrencia` (DATE NULL)
   - `informacoes_animal` (TEXT NULL)
   - `imagem` (VARCHAR(255) NULL)
   - `status` (ENUM('pendente','em_analise','aprovada','rejeitada') NOT NULL DEFAULT 'pendente')
   - `data_cadastro` (TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)

3. **animais**
   - `id` (INT PK AUTO_INCREMENT)
   - `usuario_id` (INT NULL FK -> usuarios(id) ON DELETE SET NULL)
   - `nome` (VARCHAR(100) NOT NULL)
   - `especie` (VARCHAR(50) NOT NULL)
   - `raca` (VARCHAR(100) NULL)
   - `idade` (VARCHAR(50) NULL)
   - `sexo` (ENUM('macho','femea','nao_informado') NOT NULL DEFAULT 'nao_informado')
   - `porte` (ENUM('pequeno','medio','grande','gigante') NOT NULL DEFAULT 'medio')
   - `descricao` (TEXT NULL)
   - `imagem` (VARCHAR(255) NULL)
   - `localizacao` (VARCHAR(255) NULL)
   - `contato` (VARCHAR(255) NULL)
   - `status` (ENUM('pendente','disponivel','em_adocao','adotado','rejeitado') NOT NULL DEFAULT 'pendente')
   - `data_cadastro` (TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)

4. **adocoes**
   - `id` (INT PK AUTO_INCREMENT)
   - `animal_id` (INT NOT NULL FK -> animais(id) ON DELETE CASCADE)
   - `usuario_interessado_id` (INT NOT NULL FK -> usuarios(id) ON DELETE CASCADE)
   - `mensagem` (TEXT NULL)
   - `status` (ENUM('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente')
   - `data_cadastro` (TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)

5. **desaparecidos**
   - `id` (INT PK AUTO_INCREMENT)
   - `usuario_id` (INT NULL FK -> usuarios(id) ON DELETE SET NULL)
   - `nome_animal` (VARCHAR(100) NOT NULL)
   - `especie` (VARCHAR(50) NOT NULL)
   - `raca` (VARCHAR(100) NULL)
   - `caracteristicas` (TEXT NULL)
   - `imagem` (VARCHAR(255) NULL)
   - `local_desaparecimento` (VARCHAR(255) NOT NULL)
   - `data_desaparecimento` (DATE NULL)
   - `contato` (VARCHAR(255) NOT NULL)
   - `status` (ENUM('pendente','desaparecido','encontrado','rejeitado') NOT NULL DEFAULT 'pendente')
   - `data_cadastro` (TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)

6. **moderacoes**
   - `id` (INT PK AUTO_INCREMENT)
   - `moderador_id` (INT NOT NULL FK -> usuarios(id) ON DELETE CASCADE)
   - `tipo_conteudo` (ENUM('denuncia','animal','desaparecido') NOT NULL)
   - `conteudo_id` (INT NOT NULL)
   - `decisao` (ENUM('aprovado','rejeitado','em_analise') NOT NULL)
   - `observacao` (TEXT NULL)
   - `data_moderacao` (TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)

---

## 5. REGRAS DE NEGÓCIO E CONTROLE DE ACESSO

1. **Visitantes (Não Autenticados):**
   - Podem navegar pela página inicial, ver animais disponíveis para adoção, consultar animais desaparecidos e acessar a área informativa de denúncias.
   - Não podem cadastrar publicações ou manifestar interesse de adoção sem login.
2. **Usuários Autenticados (`usuario`):**
   - Podem registrar denúncias e acompanhar o status das suas próprias denúncias. Os dados do denunciante são protegidos e nunca exibidos para outros usuários comuns.
   - Podem publicar animais para adoção e cadastrar animais desaparecidos. As publicações entram inicialmente com status `pendente` para moderação.
   - Podem demonstrar interesse em animais disponíveis para adoção enviando mensagem motivacional.
   - Podem gerenciar seus animais cadastrados, visualizar os contatos dos interessados, aprovar adoção e marcar animais desaparecidos como "Encontrado".
   - Podem atualizar seu nome, e-mail e senha no painel.
3. **Moderadores e Administradores (`moderador`, `admin`):**
   - Têm acesso ao painel `/admin/`.
   - Analisam e alteram status de denúncias (`pendente`, `em_analise`, `aprovada`, `rejeitada`).
   - Aprovam ou rejeitam animais para adoção e publicações de animais desaparecidos.
   - Todas as decisões de moderação são registradas com data, moderador responsável e observação na tabela `moderacoes`.
   - Administradores (`admin`) têm controle adicional sobre a gestão de usuários (ativar/desativar e promover papéis).

