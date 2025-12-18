<?php
include("conexao.php");

$sql = (
"create database if not exists pi_0392
default character set utf8mb4
default collate utf8mb4_unicode_ci;


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


CREATE TABLE ponto (
    id_ponto INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    data_ponto DATE NOT NULL, 
    hora_entrada TIME DEFAULT NULL,
    hora_saida TIME DEFAULT NULL,
    hora_almoco_saida TIME DEFAULT NULL,
    hora_almoco_retorno TIME DEFAULT NULL,
    observacao TEXT,
    status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente',
    aprovado_por INT DEFAULT NULL,
    data_aprovacao DATETIME DEFAULT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
    FOREIGN KEY (aprovado_por) REFERENCES usuario(id_usuario)
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

create table horas_extras (
id_hora int auto_increment primary key,
id_usuario int,
data date not null default (current_date()),
tipo enum('dia_he', 'noite_he', 'dia_hf', 'noite_hf'),
minutos int not null,
foreign key (id_usuario) references usuario(id_usuario)
);

create table feriados (
data date primary key,
descricao varchar(150)
);

create table tempo_jornada(
	id_tempo INT PRIMARY KEY AUTO_INCREMENT,
    jornada time not null default '08:00:00',
    maximo_hora_extra time not null default '02:00:00'
);

CREATE TABLE jornadas_trabalho (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    horas_diarias DECIMAL(4,2),
    dias_semana JSON, -- ex: [1,2,3,4,5] para seg-sex
    data_inicio DATE,
    data_fim DATE NULL,
    created_at TIMESTAMP
);

create table grupo_setor (
    id_setor INT,
    id_usuario INT,
    PRIMARY KEY (id_setor, id_usuario),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
	FOREIGN KEY (id_setor) REFERENCES setor(id_setor)
);

create table setor (
    id_setor INT PRIMARY KEY AUTO_INCREMENT,
    nome_setor char(50)
);

insert into cargo (nome_cargo, salario_bruto, nivel)
values (“adm”, 10, 1);

");
?>