# 🌍 Geo-Explorer — Plataforma de Cursos de Programação Online

![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?logo=javascript&logoColor=black)
![Node.js](https://img.shields.io/badge/Node.js-20+-339933?logo=node.js&logoColor=white)

> Plataforma de ensino de programação web com suporte a múltiplos perfis (Admin, Professor, Aluno), desafios de código, sistema de notas, certificados e servidor MCP integrado.

---

## 📋 Sumário

1. [O que é o Geo-Explorer](#-o-que-é-o-geo-explorer)
2. [Funcionalidades](#-funcionalidades)
3. [Tecnologias Utilizadas](#-tecnologias-utilizadas)
4. [Requisitos](#-requisitos)
5. [Instalação e Configuração](#-instalação-e-configuração)
6. [Estrutura do Projeto](#-estrutura-do-projeto)
7. [Perfis de Acesso](#-perfis-de-acesso)
8. [Conteúdo Programático](#-conteúdo-programático)
9. [Servidor MCP](#-servidor-mcp)
10. [Como Executar os Testes](#-como-executar-os-testes)
11. [Melhorias Realizadas](#-melhorias-realizadas)
12. [Aprendizados](#-aprendizados)

---

## 🌍 O que é o Geo-Explorer

O **Geo-Explorer** é uma plataforma web completa de cursos de programação online, construída com PHP puro, MySQL, HTML5, CSS3 e JavaScript sem frameworks. O sistema oferece uma experiência completa de e-learning com:

- Trilhas de aprendizado estruturadas em módulos e aulas
- Sistema de desafios de código por nível de dificuldade
- Avaliação por professores com feedback personalizado
- Certificados digitais verificáveis
- Interface responsiva com suporte a modo escuro/claro
- Servidor MCP para integração com assistentes de IA

---

## ✨ Funcionalidades

### Para o Administrador
- ✅ CRUD completo de usuários (Admin, Professor, Aluno)
- ✅ CRUD completo de cursos e módulos
- ✅ CRUD completo de aulas com conteúdo HTML e vídeo
- ✅ Visualização e gerenciamento de todas as notas
- ✅ Visualização de todos os desafios criados por professores
- ✅ Emissão e gerenciamento de certificados digitais
- ✅ Configurações visuais (tema, fonte, tamanho)

### Para o Professor
- ✅ Criar, editar e excluir próprios desafios de código
- ✅ Aplicar notas com feedback (sem editar notas existentes)
- ✅ Visualizar conteúdo dos cursos e módulos
- ✅ Acompanhar progresso dos alunos
- ✅ Configurações visuais pessoais

### Para o Aluno
- ✅ Acompanhar progresso por curso com barra de progresso
- ✅ Visualizar aulas com conteúdo e vídeos
- ✅ Marcar aulas como concluídas
- ✅ Submeter desafios de código por nível
- ✅ Receber feedback e notas dos professores
- ✅ Visualizar e imprimir certificados digitais
- ✅ Editar foto de perfil
- ✅ Configurar tema, fonte e tamanho da letra

---

## 🛠️ Tecnologias Utilizadas

| Tecnologia | Versão | Uso |
|---|---|---|
| PHP | 8.1+ | Backend e templates |
| MySQL | 8.0+ | Banco de dados relacional |
| PDO | — | Abstração do banco de dados |
| HTML5 | — | Estrutura das páginas |
| CSS3 | — | Estilização e responsividade |
| JavaScript | ES6+ | Interatividade sem frameworks |
| Node.js | 20+ | Servidor MCP |
| TypeScript | 5.0+ | Tipagem estática no MCP server |
| MCP SDK | 1.0+ | Protocolo de integração com IA |

---

## 📦 Requisitos

- **PHP** 8.1 ou superior com extensões: `pdo_mysql`, `session`, `fileinfo`
- **MySQL** 8.0 ou superior (ou MariaDB 10.6+)
- **Servidor Web**: Apache (recomendado com XAMPP/WAMP/Laragon) ou Nginx
- **Node.js** 20+ e **npm** (para o servidor MCP)
- **Composer** (opcional, não utilizado neste projeto)

---

## 🚀 Instalação e Configuração

### 1. Clonar/Copiar o projeto

```bash
# Coloque a pasta geo-explorer dentro do diretório web do seu servidor
# Exemplo com XAMPP:
cp -r geo-explorer/ C:/xampp/htdocs/
```

### 2. Configurar o banco de dados

Acesse o MySQL e execute o script:

```bash
mysql -u root -p < geo-explorer/sql/geo_explorer.sql
```

Ou via phpMyAdmin: importe o arquivo `sql/geo_explorer.sql`.

### 3. Configurar a conexão

Edite `config/database.php` com suas credenciais:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'sua_senha');
define('DB_NAME', 'geo_explorer');
```

### 4. Criar pasta de uploads

```bash
mkdir -p geo-explorer/assets/uploads/avatars
chmod 755 geo-explorer/assets/uploads/avatars
```

### 5. Imagem de avatar padrão

Adicione uma imagem `default.png` em `assets/uploads/avatars/` (pode ser qualquer imagem de placeholder).

### 6. Acessar o sistema

Abra no navegador: `http://localhost/geo-explorer/`

**Contas de teste:**

| Perfil | E-mail | Senha |
|---|---|---|
| Admin | admin@geo-explorer.com | admin123 |
| Professor | professor@geo-explorer.com | prof123 |
| Aluno | aluno@geo-explorer.com | aluno123 |

> ⚠️ **Importante:** As senhas no banco são hashes bcrypt. Se importar o SQL e as senhas não funcionarem, recrie os usuários via painel admin ou atualize os hashes com `password_hash('admin123', PASSWORD_BCRYPT)`.

---

## 📁 Estrutura do Projeto

```
geo-explorer/
├── index.php                    # Landing page / redirect
├── login.php                    # Autenticação
├── cadastro.php                 # Cadastro de novos usuários
├── logout.php                   # Encerrar sessão
│
├── config/
│   └── database.php             # Configuração PDO + singleton
│
├── assets/
│   ├── css/
│   │   ├── style.css            # Estilos globais + componentes
│   │   ├── dark-mode.css        # Variáveis e overrides do modo escuro
│   │   └── responsive.css       # Media queries responsivas
│   ├── js/
│   │   ├── main.js              # Menus, alertas, confirmações
│   │   ├── theme.js             # Alternância de tema + persistência
│   │   └── challenges.js        # Editor de código + submissão
│   └── uploads/
│       └── avatars/             # Fotos de perfil dos usuários
│
├── admin/                       # Painel administrativo
│   ├── dashboard.php
│   ├── usuarios.php             # CRUD de usuários
│   ├── cursos.php               # CRUD de cursos
│   ├── aulas.php                # CRUD de aulas
│   ├── notas.php                # Visualizar/excluir notas
│   ├── certificados.php         # Emitir/visualizar certificados
│   └── desafios.php             # Visualizar desafios
│
├── professor/                   # Painel do professor
│   ├── dashboard.php
│   ├── desafios.php             # Criar/editar desafios
│   ├── notas.php                # Aplicar notas
│   └── assuntos.php             # Ver conteúdo + progresso dos alunos
│
├── aluno/                       # Painel do aluno
│   ├── dashboard.php
│   ├── aulas.php                # Visualizar e concluir aulas
│   ├── desafios.php             # Editor de código + submissão
│   ├── notas.php                # Ver próprias notas
│   ├── perfil.php               # Editar perfil + foto + configurações
│   └── certificado.php          # Visualizar/imprimir certificado
│
├── includes/
│   ├── auth.php                 # Middleware de sessão/permissão
│   ├── header.php               # Cabeçalho HTML + navegação
│   ├── footer.php               # Rodapé + scripts
│   └── functions.php            # Funções auxiliares globais
│
├── sql/
│   └── geo_explorer.sql         # Schema + dados de teste
│
├── mcp-server/                  # Servidor MCP Node.js/TypeScript
│   ├── src/index.ts
│   ├── package.json
│   ├── tsconfig.json
│   └── README.md
│
└── README.md                    # Este arquivo
```

---

## 👥 Perfis de Acesso

| Nível | Papel | `acesso_nivel` |
|---|---|---|
| 1 | Admin | Acesso total ao sistema |
| 2 | Professor | Criar desafios, aplicar notas |
| 3 | Aluno | Ver aulas, desafios, notas e certificados |

O sistema usa sessões PHP nativas. Cada página protegida chama `requireNivel($nivel)` no início do arquivo, o que garante que somente usuários com o nível adequado possam acessá-la.

---

## 📚 Conteúdo Programático

| Módulo | Tecnologia | Conteúdo |
|---|---|---|
| 1 | HTML | Estrutura, tags, formulários, semântica |
| 2 | CSS | Seletores, Flexbox, Grid, responsividade |
| 3 | JavaScript | Variáveis, funções, DOM, eventos, arrays |
| 4 | PHP | Sintaxe, sessões, funções, OOP básico |
| 5 | PHP + MySQL | CRUD, PDO, autenticação |
| 6 | Projeto Final | Sistema completo do zero |

---

## 🤖 Servidor MCP

O servidor MCP permite integração da plataforma com assistentes de IA (como o IBM Bob).

### Instalar e compilar

```bash
cd mcp-server
npm install
npm run build
```

### Ferramentas disponíveis

```
listar_cursos         — Lista cursos ativos ou todos
listar_aulas          — Lista aulas por curso_id ou modulo_id
verificar_certificado — Valida um certificado pelo código único
status_aluno          — Retorna progresso de um aluno (por ID ou e-mail)
```

### Registrar no Bob

Edite `.bob/mcp.json`:

```json
{
  "mcpServers": {
    "geo-explorer": {
      "command": "node",
      "args": ["geo-explorer/mcp-server/dist/index.js"],
      "env": {
        "DB_HOST": "localhost",
        "DB_USER": "root",
        "DB_PASS": "",
        "DB_NAME": "geo_explorer"
      }
    }
  }
}
```

---

## 🧪 Como Executar os Testes

O projeto não possui testes automatizados (sem framework de teste). Para validar manualmente:

### Checklist de testes funcionais

```
[ ] Login com cada perfil (admin, professor, aluno)
[ ] Cadastro de novo usuário
[ ] Admin: criar/editar/excluir usuário
[ ] Admin: criar curso com módulos
[ ] Admin: criar aula com conteúdo
[ ] Admin: emitir certificado
[ ] Professor: criar desafio básico/intermediário/avançado
[ ] Professor: aplicar nota para um aluno
[ ] Aluno: visualizar aula e marcar como concluída
[ ] Aluno: submeter código num desafio
[ ] Aluno: ver notas recebidas
[ ] Aluno: ver e imprimir certificado
[ ] Aluno: editar foto de perfil
[ ] Alternar tema claro/escuro
[ ] Trocar fonte e tamanho da letra
[ ] Verificar responsividade em mobile
```

### Validar banco de dados

```sql
-- Verificar tabelas criadas
SHOW TABLES FROM geo_explorer;

-- Verificar usuários de teste
SELECT id, nome, email, acesso_nivel FROM usuarios;

-- Verificar cursos
SELECT id, titulo, ativo FROM cursos;
```

---

## 🔧 Melhorias Realizadas

Durante o desenvolvimento, foram implementadas as seguintes melhorias além dos requisitos básicos:

1. **Singleton PDO** — Conexão única com o banco reutilizada em toda a requisição, evitando múltiplas conexões.

2. **Sistema de Flash Messages** — Mensagens de feedback persistem entre redirecionamentos via sessão.

3. **BASE_URL dinâmico** — A URL base é detectada automaticamente, sem necessidade de configuração manual.

4. **CSS Variables** — Todo o sistema visual usa variáveis CSS, facilitando a troca de tema com uma única linha JavaScript.

5. **Sidebar de navegação por aulas** — Navegação lateral nas aulas com indicador de progresso (✅ concluída / ⭕ pendente).

6. **Editor de código com prévia** — O editor de desafios permite visualizar HTML/CSS em tempo real num iframe sandbox.

7. **Upload seguro de fotos** — Validação de tipo MIME, limite de 2MB e renomeação do arquivo com timestamp.

8. **Proteção CSRF básica** — Formulários usam POST com verificação de método e sanitização de todas as entradas.

9. **Servidor MCP tipado** — O servidor MCP é implementado em TypeScript com validação de parâmetros via Zod.

10. **Certificado imprimível** — O certificado possui estilos específicos para `@media print`, removendo header/footer automaticamente.

---

## 📖 Aprendizados

Este projeto consolidou e aprofundou os seguintes conhecimentos:

### PHP
- Uso de **PDO com prepared statements** para prevenir SQL injection
- **Sessions** nativas para autenticação stateful
- **password_hash / password_verify** para armazenamento seguro de senhas
- Manipulação de **uploads de arquivos** com validação
- Arquitetura de **includes** reutilizáveis (header, footer, auth, functions)

### MySQL
- Design de **schema relacional** com foreign keys e integridade referencial
- **ON DELETE CASCADE** para manter consistência ao excluir registros
- **INSERT ... ON DUPLICATE KEY UPDATE** para upserts eficientes
- Uso de **JOINs** complexos para relatórios

### JavaScript
- **IIFEs** (Immediately Invoked Function Expressions) para encapsulamento
- Manipulação do **DOM** sem frameworks
- **Fetch API** para requisições assíncronas
- **localStorage** para persistência de preferências do usuário
- Gerenciamento de **eventos** delegados

### CSS
- **CSS Custom Properties** (variáveis) para theming dinâmico
- **CSS Grid** e **Flexbox** para layouts responsivos
- **Media queries** para responsividade em múltiplos dispositivos
- **@media print** para controle de impressão

### Node.js / TypeScript
- Implementação de um **servidor MCP** com o SDK oficial
- **TypeScript** com tipagem estrita para maior segurança
- **mysql2** com prepared statements no Node.js
- Integração de sistemas PHP + Node.js via protocolo padronizado

---

## 📄 Licença

Este projeto é desenvolvido para fins educacionais. Livre para uso e modificação.

---

*Feito com ❤️ pela equipe Geo-Explorer*
