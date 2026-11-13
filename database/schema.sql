-- ============================================================
-- HousePet Database Schema (schema.sql)
-- Plataforma de Proteção e Bem-Estar Animal
-- ============================================================

CREATE DATABASE IF NOT EXISTS housepet_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE housepet_db;

-- ============================================================
-- Tabela: usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT          NOT NULL AUTO_INCREMENT,
    nome            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    senha           VARCHAR(255) NOT NULL,
    tipo_usuario    ENUM('usuario','moderador','admin') NOT NULL DEFAULT 'usuario',
    ativo           TINYINT(1)   NOT NULL DEFAULT 1,
    data_cadastro   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email (email),
    INDEX idx_tipo_usuario (tipo_usuario),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabela: denuncias
-- ============================================================
CREATE TABLE IF NOT EXISTS denuncias (
    id               INT          NOT NULL AUTO_INCREMENT,
    usuario_id       INT              NULL,
    descricao        TEXT         NOT NULL,
    tipo_ocorrencia  VARCHAR(100) NOT NULL,
    localizacao      VARCHAR(255) NOT NULL,
    data_ocorrencia  DATE             NULL,
    informacoes_animal TEXT            NULL,
    imagem           VARCHAR(255)     NULL,
    status           ENUM('pendente','em_analise','aprovada','rejeitada') NOT NULL DEFAULT 'pendente',
    data_cadastro    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_denuncia_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_denuncia_status   (status),
    INDEX idx_denuncia_usuario  (usuario_id),
    INDEX idx_denuncia_data     (data_cadastro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabela: animais
-- ============================================================
CREATE TABLE IF NOT EXISTS animais (
    id            INT          NOT NULL AUTO_INCREMENT,
    usuario_id    INT              NULL,
    nome          VARCHAR(100) NOT NULL,
    especie       VARCHAR(50)  NOT NULL,
    raca          VARCHAR(100)     NULL,
    idade         VARCHAR(50)      NULL,
    sexo          ENUM('macho','femea','nao_informado') NOT NULL DEFAULT 'nao_informado',
    porte         ENUM('pequeno','medio','grande','gigante') NOT NULL DEFAULT 'medio',
    descricao     TEXT             NULL,
    imagem        VARCHAR(255)     NULL,
    localizacao   VARCHAR(255)     NULL,
    contato       VARCHAR(255)     NULL,
    status        ENUM('pendente','disponivel','em_adocao','adotado','rejeitado') NOT NULL DEFAULT 'pendente',
    data_cadastro TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_animal_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_animal_status   (status),
    INDEX idx_animal_especie  (especie),
    INDEX idx_animal_usuario  (usuario_id),
    INDEX idx_animal_data     (data_cadastro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabela: adocoes
-- ============================================================
CREATE TABLE IF NOT EXISTS adocoes (
    id                     INT  NOT NULL AUTO_INCREMENT,
    animal_id              INT  NOT NULL,
    usuario_interessado_id INT  NOT NULL,
    mensagem               TEXT     NULL,
    status                 ENUM('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente',
    data_cadastro          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_adocao_animal   FOREIGN KEY (animal_id)              REFERENCES animais(id)   ON DELETE CASCADE,
    CONSTRAINT fk_adocao_usuario  FOREIGN KEY (usuario_interessado_id) REFERENCES usuarios(id)  ON DELETE CASCADE,
    INDEX idx_adocao_animal   (animal_id),
    INDEX idx_adocao_usuario  (usuario_interessado_id),
    INDEX idx_adocao_status   (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabela: desaparecidos
-- ============================================================
CREATE TABLE IF NOT EXISTS desaparecidos (
    id                    INT          NOT NULL AUTO_INCREMENT,
    usuario_id            INT              NULL,
    nome_animal           VARCHAR(100) NOT NULL,
    especie               VARCHAR(50)  NOT NULL,
    raca                  VARCHAR(100)     NULL,
    caracteristicas       TEXT             NULL,
    imagem                VARCHAR(255)     NULL,
    local_desaparecimento VARCHAR(255) NOT NULL,
    data_desaparecimento  DATE             NULL,
    contato               VARCHAR(255) NOT NULL,
    status                ENUM('pendente','desaparecido','encontrado','rejeitado') NOT NULL DEFAULT 'pendente',
    data_cadastro         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_desaparecido_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_desap_status   (status),
    INDEX idx_desap_especie  (especie),
    INDEX idx_desap_usuario  (usuario_id),
    INDEX idx_desap_data     (data_cadastro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabela: moderacoes
-- ============================================================
CREATE TABLE IF NOT EXISTS moderacoes (
    id              INT  NOT NULL AUTO_INCREMENT,
    moderador_id    INT  NOT NULL,
    tipo_conteudo   ENUM('denuncia','animal','desaparecido') NOT NULL,
    conteudo_id     INT  NOT NULL,
    decisao         ENUM('aprovado','rejeitado','em_analise') NOT NULL,
    observacao      TEXT     NULL,
    data_moderacao  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_moderacao_moderador FOREIGN KEY (moderador_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_mod_moderador    (moderador_id),
    INDEX idx_mod_tipo         (tipo_conteudo),
    INDEX idx_mod_conteudo     (conteudo_id),
    INDEX idx_mod_data         (data_moderacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Dados Iniciais — Usuário Administrador Padrão
-- E-mail: admin@housepet.com
-- Senha:  Admin123!
-- ============================================================
INSERT INTO usuarios (nome, email, senha, tipo_usuario, ativo)
VALUES (
    'Administrador HousePet',
    'admin@housepet.com',
    '$2y$10$nC5cW/uhxPu8gyHp/CAtDubf8avYmOAmysgDdEZsSE56LomAeFtyq',
    'admin',
    1
) ON DUPLICATE KEY UPDATE id=id;

