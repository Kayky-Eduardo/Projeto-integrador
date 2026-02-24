# SGRH — Sistema de Gerenciamento de Recursos Humanos (Em andamento)

## Descrição

O SGRH é um sistema web desenvolvido como projeto integrador pelo curso Técnico em Desenvolvimento de Sistemas do SENAC-MG (Turma 0392). Seu objetivo é fornecer uma solução completa e adaptável para a gestão de recursos humanos em diferentes tipos de empresas.

O sistema contempla funcionalidades como controle de ponto eletrônico, gestão de funcionários, folha de pagamento, banco de horas, férias e licenças, além da geração de relatórios e métricas. É projetado de forma modular e configurável, permitindo que cada empresa defina suas próprias regras de jornada, pausas, setores e níveis de acesso.

> **Nota:** o sistema é destinado a ambientes com setores de RH bem definidos. Não inclui integração bancária, com eSocial ou emissão de documentos fiscais oficiais.

---

## Funcionalidades Principais

- Autenticação e controle de acesso por níveis de cargo
- CRUD completo de usuários, cargos e setores
- Controle de ponto diário com registro de pausas
- Solicitação e aprovação de ajustes de ponto
- Banco de horas automatizado com histórico de movimentações
- Gestão de folha de pagamento com cálculo de INSS, IRRF e FGTS
- Consulta de holerite
- Gestão de férias, licenças e afastamentos
- Geração de relatórios e indicadores

---

## Tecnologias Utilizadas

### Linguagens
- HTML5
- CSS3
- JavaScript
- PHP
- SQL

### Bibliotecas
- [PNotify](https://sciactive.com/pnotify/) — notificações na interface
- [Google Charts](https://developers.google.com/chart) — geração de gráficos e relatórios visuais

### Banco de Dados
- **SGBD:** MySQL
- **Banco:** `pi_0392`
- **Charset:** `utf8mb4`
- **Collation:** `utf8mb4_unicode_ci`

### Ambiente
- Execução local (ambiente de desenvolvimento)
- Arquitetura estruturada com separação por diretórios entre interface (HTML/CSS/JS), lógica de negócio (PHP) e persistência de dados (MySQL)

---

## Arquitetura

O sistema segue uma arquitetura em três camadas:

1. **Interface** — HTML, CSS e JavaScript
2. **Lógica de negócio** — PHP com controle de sessões e níveis de acesso
3. **Persistência** — MySQL com modelagem relacional e integridade referencial

O controle de acesso é baseado em sessões PHP associadas ao cargo do usuário, sem uso de frameworks externos de autenticação.

---

*Projeto Integrador — SENAC-MG | Técnico em Desenvolvimento de Sistemas | Turma 0392 | Juiz de Fora, 2026*
