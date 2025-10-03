<?php
include("conexao.php");

$sql = (
"create database if not exists pi_0392
default character set utf8mb4
default collate utf8mb4_unicode_ci;

create table cargo (
id_cargo int auto_increment primary key,
nome_cargo varchar(100) not null
salario_bruto decimal(10, 2) not null,
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
assiduidade float not null,
data_admissao date not null,
conta_ativa boolean default true,
data_demissao date,
foreign key (id_cargo) references cargo (id_cargo)
);

create table ponto (
id_ponto int auto_increment primary key,
id_usuario int not null,
inicio_ponto datetime not null,
inicio_almoco time not null,
fim_almoco time not null,
fim_ponto datetime not null,
foreign key (id_usuario) references usuario(id_usuario)
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

create table pagamento (
id_pagamento int auto_increment primary key,
id_usuario int not null,
data_pagamento datetime not null,
descontos decimal(10, 2),
adicionais decimal(10, 2),
salario_liquido decimal(10, 2),
foreign key (id_usuario) references usuario(id_usuario)
);

create table horas (
id_hora int auto_increment primary key,
id_usuario int,
fds_feriado int,
dia_he int,
noite_he int,
noite_hf int,
dia_hf int,
foreign key (id_usuario) references usuario(id_usuario)
);");
$conn->query($sql);

?>