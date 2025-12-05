<?php
include("conexao.php");

$sql = (
"create database if not exists pi_0392
default character set utf8mb4
default collate utf8mb4_unicode_ci;

use pi_0392;
create table cargo (
id_cargo int auto_increment primary key,
nome_cargo varchar(100) not null,
salario_bruto decimal(10, 2) not null,
nivel int not null
);

create table usuario(
id_usuario int auto_increment primary key,
nome_usuario varchar(100) not null,
cpf_usuario char(11) not null unique,
rg_usuario char(11) not null unique,
genero enum('Masculino', 'Feminino', 'Outro', 'Não Declarado'),
email_usuario varchar(100) not null unique,
senha_usuario varchar(255) not null,
telefone varchar(15),
cep char(8) not null,
id_cargo int,
assiduidade float not null,
data_admissao date not null,
conta_ativa boolean default true,
data_demissao date,
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

CREATE TABLE ajustes_ponto (
id_ajuste INT AUTO_INCREMENT PRIMARY KEY,
id_ponto INT NOT NULL,
id_usuario INT NOT NULL,
campo VARCHAR(30) NOT NULL, -- entrada / almoço saída...
valor_antigo DATETIME,
valor_novo DATETIME,
motivo TEXT NOT NULL,
status ENUM('Pendente', 'Aprovado', 'Recusado') DEFAULT 'Pendente',
data_solicitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
data_resposta DATETIME NULL,
id_rh INT NULL,
FOREIGN KEY (id_ponto) REFERENCES ponto_dia(id_ponto),
FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
FOREIGN KEY (id_rh) REFERENCES usuario(id_usuario)
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

create table dados_bancarios (
id_dados_bancarios int auto_increment primary key,
id_usuario int not null,
agencia char(6) not null,
numero_conta char(12) not null,
nome_titular varchar(100) not null,
chave_pix varchar(100),
foreign key (id_usuario) references usuario(id_usuario) ON DELETE CASCADE
);

create table pagamento (
id_pagamento int auto_increment primary key,
id_usuario int not null,
data_pagamento datetime not null,
descontos decimal(10, 2),
adicionais decimal(10, 2),
salario_liquido decimal(10, 2),
foreign key (id_usuario) references usuario(id_usuario) ON DELETE CASCADE
);

create table horas (
id_hora int auto_increment primary key,
id_usuario int,
fds_feriado int,
dia_he int,
noite_he int,
noite_hf int,
dia_hf int,
foreign key (id_usuario) references usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pausa_config (
  id_config INT AUTO_INCREMENT PRIMARY KEY,
  descricao_pausa VARCHAR(100) NOT NULL,
  tempo_min INT DEFAULT 0,
  tempo_max INT DEFAULT 0,
  limite_pausa_diario INT DEFAULT 1
);

CREATE TABLE IF NOT EXISTS pausa (
  id_pausa INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_config INT NOT NULL,
  inicio TIME NOT NULL,
  fim TIME DEFAULT NULL,
  data DATE NOT NULL,
  duracao_minutos INT DEFAULT NULL,
  FOREIGN KEY (id_config) REFERENCES pausa_config(id_config)
);

");
$conn->query($sql);

?>