SET NAMES utf8mb4;
/* SQL schema for the medical records system (MySQL).
   This file defines the core tables and relationships for o prontuário web. */
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS pessoa (
    pessoa_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cpf CHAR(11) DEFAULT NULL UNIQUE,
    email VARCHAR(255),
    telefone VARCHAR(32),
    data_nascimento DATE,
    logradouro VARCHAR(255),
    numero VARCHAR(16),
    complemento VARCHAR(128),
    bairro VARCHAR(128),
    cidade VARCHAR(128),
    estado VARCHAR(64),
    cep VARCHAR(16),
    pais VARCHAR(64) DEFAULT 'Brasil',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS paciente (
    paciente_id BIGINT UNSIGNED PRIMARY KEY,
    peso DECIMAL(5,2),
    altura DECIMAL(4,2),
    tipo_sanguineo VARCHAR(4),
    alergias TEXT,
    condicoes_cronicas TEXT,
    observacoes TEXT,
    FOREIGN KEY (paciente_id) REFERENCES pessoa(pessoa_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS medico (
    medico_id BIGINT UNSIGNED PRIMARY KEY,
    crm VARCHAR(32) NOT NULL UNIQUE,
    especialidade VARCHAR(128),
    instituicao VARCHAR(255),
    contato TEXT,
    FOREIGN KEY (medico_id) REFERENCES pessoa(pessoa_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS consulta (
    consulta_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id BIGINT UNSIGNED NOT NULL,
    medico_id BIGINT UNSIGNED,
    data_consulta DATE NOT NULL,
    hora_inicio TIME,
    hora_fim TIME,
    tipo_consulta VARCHAR(64) DEFAULT 'presencial',
    motivo TEXT,
    diagnostico TEXT,
    status ENUM('agendada','realizada','cancelada','remarcada') DEFAULT 'agendada',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES paciente(paciente_id),
    FOREIGN KEY (medico_id) REFERENCES medico(medico_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS exame (
    exame_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    unidade VARCHAR(64),
    referencia_min DECIMAL(6,2),
    referencia_max DECIMAL(6,2),
    tipo VARCHAR(64) DEFAULT 'laboratorial',
    laboratorio VARCHAR(255),
    frequencia VARCHAR(128),
    observacoes TEXT,
    slug VARCHAR(255) UNIQUE,
    data_realizacao DATE,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS resultado_exame (
    resultado_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exame_id BIGINT UNSIGNED NOT NULL,
    paciente_id BIGINT UNSIGNED NOT NULL,
    data_coleta DATE NOT NULL,
    valor DECIMAL(10,2),
    unidade VARCHAR(64),
    referencia_min DECIMAL(6,2),
    referencia_max DECIMAL(6,2),
    laboratorio VARCHAR(255),
    observacoes TEXT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exame_id) REFERENCES exame(exame_id),
    FOREIGN KEY (paciente_id) REFERENCES paciente(paciente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS prontuario (
    prontuario_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id BIGINT UNSIGNED NOT NULL,
    responsavel_medico_id BIGINT UNSIGNED,
    data_abertura DATE NOT NULL,
    status ENUM('ativo','inativo','arquivado') DEFAULT 'ativo',
    descricao TEXT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES paciente(paciente_id),
    FOREIGN KEY (responsavel_medico_id) REFERENCES medico(medico_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS prontuario_consulta (
    prontuario_id BIGINT UNSIGNED NOT NULL,
    consulta_id BIGINT UNSIGNED NOT NULL,
    observacoes TEXT,
    PRIMARY KEY (prontuario_id, consulta_id),
    FOREIGN KEY (prontuario_id) REFERENCES prontuario(prontuario_id) ON DELETE CASCADE,
    FOREIGN KEY (consulta_id) REFERENCES consulta(consulta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS prontuario_exame (
    prontuario_id BIGINT UNSIGNED NOT NULL,
    resultado_id BIGINT UNSIGNED NOT NULL,
    anexado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (prontuario_id, resultado_id),
    FOREIGN KEY (prontuario_id) REFERENCES prontuario(prontuario_id) ON DELETE CASCADE,
    FOREIGN KEY (resultado_id) REFERENCES resultado_exame(resultado_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS prescricao (
    prescricao_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prontuario_id BIGINT UNSIGNED NOT NULL,
    medico_id BIGINT UNSIGNED NOT NULL,
    medicamento VARCHAR(255) NOT NULL,
    dosagem VARCHAR(128),
    frequencia VARCHAR(128),
    duracao VARCHAR(64),
    orientacoes TEXT,
    criada_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prontuario_id) REFERENCES prontuario(prontuario_id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES medico(medico_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS anexo (
    anexo_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prontuario_id BIGINT UNSIGNED NOT NULL,
    tipo VARCHAR(128) NOT NULL,
    caminho_arquivo VARCHAR(1024) NOT NULL,
    descricao TEXT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prontuario_id) REFERENCES prontuario(prontuario_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS historico_vacina (
    vacina_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(255) NOT NULL,
    fabricante VARCHAR(255),
    lote VARCHAR(128),
    data_aplicacao DATE NOT NULL,
    proxima_dose DATE,
    observacoes TEXT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES paciente(paciente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
