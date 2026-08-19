<?php
include("conexao.php");

$sql = ("
create database if not exists pi_0392
default character set utf8mb4
default collate utf8mb4_unicode_ci;

use pi_0392;

create table cargo (
id_cargo int auto_increment primary key,
nome_cargo varchar(100) not null,
salario_bruto decimal(10, 2) not null,
ativo TINYINT(1) DEFAULT 1,
nivel int not null
);

create table usuario(
id_usuario int auto_increment primary key,
nome_usuario varchar(100) not null,
cpf_usuario char(11) not null unique,
rg_usuario char(11) not null unique,
genero enum('Masculino', 'Feminino', 'Outro', 'Não Declarado'),
email_usuario varchar(100) not null,
senha_usuario varchar(255) not null,
telefone char(20),
cep char(8) not null,
id_cargo int,
assiduidade float not null default 100,
data_admissao date not null,
conta_ativa boolean default true,
data_demissao date,
foto_usuario VARCHAR(255) DEFAULT 'user_padrao.png',
foreign key (id_cargo) references cargo (id_cargo)
);

create table login (
id_login int auto_increment primary key,
email_login varchar(100) not null,
data_inicio datetime default current_timestamp,
data_fim datetime,
id_usuario int,
id_cargo int,
foreign key (id_cargo) references cargo (id_cargo),
foreign key (id_usuario) references usuario (id_usuario)
);  

CREATE TABLE ponto_dia (
id_ponto INT AUTO_INCREMENT PRIMARY KEY,
id_usuario INT NOT NULL,
data_ponto DATE NOT NULL default (current_date),
inicio_ponto TIME DEFAULT (current_time()),
fim_ponto TIME DEFAULT NULL,
status ENUM('Em Andamento', 'Finalizado', 'Aprovado', 'Revisar')
NOT NULL DEFAULT 'Em Andamento',
criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
UNIQUE KEY ux_usuario_data (id_usuario, data_ponto),
FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

CREATE TABLE notificacoes_ponto (
id_notificacao INT AUTO_INCREMENT PRIMARY KEY,
id_usuario INT NOT NULL,
id_ponto INT NOT NULL,
mensagem VARCHAR(255) NOT NULL,
data_notificacao DATETIME DEFAULT CURRENT_TIMESTAMP,
lida TINYINT DEFAULT 0,
FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
FOREIGN KEY (id_ponto) REFERENCES ponto_dia(id_ponto)
);


CREATE TABLE IF NOT EXISTS pausa_config (
  id_config INT AUTO_INCREMENT PRIMARY KEY,
  descricao_pausa VARCHAR(100) NOT NULL,
  tempo_min INT DEFAULT 0,
  tempo_max INT DEFAULT 0,
  limite_pausa_diario INT DEFAULT 1,
  ativo TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS pausa (
  id_pausa INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_config INT NOT NULL,
  inicio TIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fim TIME DEFAULT NULL,
  data DATE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  duracao_minutos INT DEFAULT NULL,
  FOREIGN KEY (id_config) REFERENCES pausa_config(id_config),
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

CREATE TABLE ajustes_ponto (
id_ajuste INT AUTO_INCREMENT PRIMARY KEY,
id_ponto INT NOT NULL,
id_usuario INT NOT NULL,
id_pausa INT,
campo VARCHAR(30) NOT NULL, -- entrada / almoço saída...
valor_antigo DATETIME,
valor_novo DATETIME,
motivo TEXT NOT NULL,
status ENUM('Pendente', 'Aprovado', 'Recusado') DEFAULT 'Pendente',
data_solicitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
data_resposta DATETIME NULL,
id_rh INT NULL,
FOREIGN KEY (id_ponto) REFERENCES ponto_dia(id_ponto),
FOREIGN KEY (id_pausa) REFERENCES pausa(id_pausa),
FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
FOREIGN KEY (id_rh) REFERENCES usuario(id_usuario)
);

create table dados_bancarios (
id_dados_bancarios int auto_increment primary key,
id_usuario int not null,
agencia char(6) not null,
numero_conta char(12) not null,
nome_titular varchar(100) not null,
chave_pix varchar(100),
foreign key (id_usuario) references usuario(id_usuario) on delete cascade
);

CREATE TABLE eventos (
	id_evento INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    tipo ENUM('provento', 'desconto'),
    descricao VARCHAR(100) DEFAULT 'Não Informado',
    valor DECIMAL(10,2),
	mes_competencia DATE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
);

CREATE TABLE folhas (
	id_folha INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    mes_competencia DATE NOT NULL,
    salario_bruto DECIMAL(10,2) NOT NULL,
    total_proventos DECIMAL(10,2) DEFAULT 0,
    total_descontos DECIMAL(10,2) DEFAULT 0,
    fgts DECIMAL(10,2) DEFAULT 0,
    inss DECIMAL(10,2) DEFAULT 0,
    irrf DECIMAL(10,2) DEFAULT 0,
    vt DECIMAL(10,2) DEFAULT 0,
    salario_liquido DECIMAL(10,2) DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revisado BOOL NOT NULL DEFAULT FALSE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
    UNIQUE KEY uq_usuario_mes (id_usuario, mes_competencia)
);

CREATE TABLE banco_horas (
    id_banco INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    saldo_minutos INT DEFAULT 0,
    ultima_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
    UNIQUE KEY (id_usuario)
);

CREATE TABLE banco_horas_historico (
    id_historico INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_banco INT NOT NULL,
    data DATE NOT NULL,
    minutos INT NOT NULL,
    tipo ENUM('hora_extra', 'tempo_faltante', 'compensacao', 'ajuste_manual', 'horario_completo', 'falta') NOT NULL,
    descricao VARCHAR(255),
    saldo_anterior INT NOT NULL,
    saldo_novo INT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
	FOREIGN KEY (id_banco) REFERENCES banco_horas(id_banco),
    INDEX idx_usuario_data (id_usuario, data)
);

CREATE TABLE feriados (
  data DATE PRIMARY KEY,
  descricao VARCHAR(100)
);

create table tempo_jornada(
	id_tempo INT PRIMARY KEY AUTO_INCREMENT,
    descricao char(100),
    jornada time not null default '08:00:00',
    maximo_hora_extra time not null default '02:00:00',
    ativo TINYINT(1) DEFAULT 1,
	dias_semana JSON NOT NULL DEFAULT (JSON_ARRAY(1,2,3,4,5,6,7)) -- seg até dom
 );

create table setor (
    id_setor INT PRIMARY KEY AUTO_INCREMENT,
    id_tempo INT NOT NULL,
    nome_setor char(50) UNIQUE,
    ativo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_tempo) REFERENCES tempo_jornada(id_tempo)
);

create table grupo_setor (
    id_setor INT,
    id_usuario INT,
    PRIMARY KEY (id_setor, id_usuario),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
	FOREIGN KEY (id_setor) REFERENCES setor(id_setor) on delete cascade
);

CREATE TABLE empresas (
    -- Identificação Interna
    id_empresa INT NOT NULL UNIQUE,
    id_modificador INT NOT NULL PRIMARY KEY,
    data_modificacao DATE NOT NULL,
    
    -- Dados para o Holerite (Visual)
    razao_social VARCHAR(150) NOT NULL, -- nome jurídico e oficial da empresa
    nome_fantasia VARCHAR(100) NOT NULL, -- nome visual da empresa(ex: MCdonalds)
    cnpj CHAR(14) NOT NULL UNIQUE,
    cep CHAR(8) NOT NULL,
    uf CHAR(2) NOT NULL, -- estado em que se encontra
    cidade VARCHAR(100) NOT NULL,
    bairro VARCHAR(100) NOT NULL,
    numero VARCHAR(20) NOT NULL,
FOREIGN KEY (id_modificador ) REFERENCES usuario(id_usuario) on delete cascade
);

insert into tempo_jornada(jornada, maximo_hora_extra) values
("08:00:00", "02:00:00");

create table grupo_setor_pausa (
    id_setor INT,
    id_config INT,
    PRIMARY KEY (id_setor, id_config),
	FOREIGN KEY (id_setor) REFERENCES setor(id_setor) on delete cascade,
	FOREIGN KEY (id_config) REFERENCES pausa_config(id_config) on delete cascade
);

set global event_scheduler = ON;

create event limpar_tempo
	on schedule
    every 30 day
    starts (timestamp(current_date() + interval 30 day))
DO
	update banco_horas
    set saldo_minutos = 0;

");
?>
