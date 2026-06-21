CREATE DATABASE IF NOT EXISTS inventario_hospitalar
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE inventario_hospitalar;

CREATE TABLE localizacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    edificio VARCHAR(100) NOT NULL,
    piso VARCHAR(50) NOT NULL,
    servico VARCHAR(100) NOT NULL,
    sala VARCHAR(50) NOT NULL,
    observacoes TEXT
);

CREATE TABLE fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_empresa VARCHAR(150) NOT NULL,
    nif VARCHAR(20) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    email VARCHAR(100),
    morada VARCHAR(200),
    website VARCHAR(100),
    pessoa_contacto VARCHAR(100),
    telefone_contacto VARCHAR(20),
    tipo_fornecedor VARCHAR(100),
    observacoes TEXT
);

CREATE TABLE equipamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_inventario VARCHAR(50) NOT NULL UNIQUE,
    designacao VARCHAR(150) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    marca VARCHAR(100),
    modelo VARCHAR(100),
    numero_serie VARCHAR(100),
    fabricante VARCHAR(100),
    data_aquisicao DATE,
    ano_fabrico YEAR,
    custo_aquisicao DECIMAL(10,2),
    tipo_entrada VARCHAR(50),
    estado VARCHAR(50) NOT NULL,
    criticidade VARCHAR(50) NOT NULL,
    observacoes TEXT,
    localizacao_id INT,

    FOREIGN KEY (localizacao_id)
        REFERENCES localizacoes(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE TABLE notas_equipamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipamento_id INT NOT NULL,
    data_nota DATE NOT NULL,
    tipo_nota VARCHAR(50) NOT NULL,
    titulo VARCHAR(120) NOT NULL,
    descricao TEXT NOT NULL,
    responsavel VARCHAR(100) DEFAULT 'Sistema',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notas_equipamento (equipamento_id, data_nota),

    FOREIGN KEY (equipamento_id)
        REFERENCES equipamentos(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE equipamentos_fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipamento_id INT NOT NULL,
    fornecedor_id INT NOT NULL,
    tipo_relacao VARCHAR(100),

    FOREIGN KEY (equipamento_id)
        REFERENCES equipamentos(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (fornecedor_id)
        REFERENCES fornecedores(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE documentacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_documento VARCHAR(100) NOT NULL,
    nome_documento VARCHAR(150) NOT NULL,
    data_documento DATE,
    data_validade DATE,
    caminho_ficheiro VARCHAR(200),
    equipamento_id INT NOT NULL,
    fornecedor_id INT,

    FOREIGN KEY (equipamento_id)
        REFERENCES equipamentos(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (fornecedor_id)
        REFERENCES fornecedores(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE TABLE garantias_contratos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipamento_id INT NOT NULL,
    data_inicio DATE,
    data_fim DATE,
    existe_contrato BOOLEAN DEFAULT FALSE,
    tipo_contrato VARCHAR(100),
    entidade_responsavel VARCHAR(150),
    periodicidade VARCHAR(100),
    observacoes TEXT,

    FOREIGN KEY (equipamento_id)
        REFERENCES equipamentos(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


