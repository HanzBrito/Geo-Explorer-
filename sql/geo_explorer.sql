-- ============================================================
-- GEO-EXPLORER — Script SQL Completo
-- Banco de dados, tabelas e dados de teste
-- ============================================================

CREATE DATABASE IF NOT EXISTS geo_explorer
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE geo_explorer;

-- ------------------------------------------------------------
-- USUÁRIOS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  acesso_nivel TINYINT NOT NULL DEFAULT 3 COMMENT '1=Admin, 2=Professor, 3=Aluno',
  foto VARCHAR(255) DEFAULT NULL,
  data_nascimento DATE DEFAULT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- CURSOS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cursos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(150) NOT NULL,
  descricao TEXT DEFAULT NULL,
  professor_id INT NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- MÓDULOS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS modulos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  ordem INT NOT NULL DEFAULT 0,
  FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- AULAS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aulas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  modulo_id INT NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  conteudo LONGTEXT DEFAULT NULL,
  video_url VARCHAR(300) DEFAULT NULL,
  ordem INT NOT NULL DEFAULT 0,
  FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- DESAFIOS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS desafios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aula_id INT DEFAULT NULL,
  professor_id INT NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  descricao TEXT DEFAULT NULL,
  nivel ENUM('basico','intermediario','avancado') NOT NULL DEFAULT 'basico',
  codigo_inicial TEXT DEFAULT NULL,
  resposta_esperada TEXT DEFAULT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE SET NULL,
  FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- NOTAS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aluno_id INT NOT NULL,
  desafio_id INT NOT NULL,
  professor_id INT NOT NULL,
  nota DECIMAL(5,2) NOT NULL,
  comentario TEXT DEFAULT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (desafio_id) REFERENCES desafios(id) ON DELETE CASCADE,
  FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- PROGRESSO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS progresso (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aluno_id INT NOT NULL,
  aula_id INT NOT NULL,
  concluido TINYINT(1) NOT NULL DEFAULT 0,
  data_conclusao DATETIME DEFAULT NULL,
  UNIQUE KEY uk_progresso (aluno_id, aula_id),
  FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- CERTIFICADOS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS certificados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aluno_id INT NOT NULL,
  curso_id INT NOT NULL,
  nota_final DECIMAL(5,2) NOT NULL,
  emitido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  codigo_verificacao VARCHAR(64) NOT NULL UNIQUE,
  FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- CONFIGURAÇÕES DE USUÁRIO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS configuracoes_usuario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL UNIQUE,
  tema ENUM('claro','escuro') NOT NULL DEFAULT 'claro',
  fonte VARCHAR(50) NOT NULL DEFAULT 'Inter',
  tamanho_fonte ENUM('pequeno','medio','grande') NOT NULL DEFAULT 'medio',
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DADOS DE TESTE
-- Senhas: admin123 | prof123 | aluno123  (password_hash PHP)
-- ============================================================

INSERT INTO usuarios (nome, email, senha, acesso_nivel, ativo) VALUES
('Administrador', 'admin@geo-explorer.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
-- senha: admin123
('Prof. Maria Silva', 'professor@geo-explorer.com',
 '$2y$10$fSAGnDRJQVkQnp5oVbJlhOtXD6fV0vAo8NQ9HuQ2R3kG1g5xdGMKS', 2, 1),
-- senha: prof123
('João Aluno', 'aluno@geo-explorer.com',
 '$2y$10$TKh8H1.PuxVGRRBiEMQ9C.jE.XomWGCB5pXdT5mRH2.aDuP3Y7JyS', 3, 1);
-- senha: aluno123

-- Configurações padrão para cada usuário
INSERT INTO configuracoes_usuario (usuario_id, tema, fonte, tamanho_fonte) VALUES
(1, 'claro', 'Inter', 'medio'),
(2, 'claro', 'Inter', 'medio'),
(3, 'claro', 'Inter', 'medio');

-- Curso de exemplo
INSERT INTO cursos (titulo, descricao, professor_id, ativo) VALUES
('Desenvolvimento Web Completo', 'Aprenda HTML, CSS, JavaScript, PHP e MySQL do zero ao avançado.', 2, 1),
('PHP Avançado com MySQL', 'Aprofunde seus conhecimentos em PHP orientado a objetos e banco de dados.', 2, 1);

-- Módulos do curso 1
INSERT INTO modulos (curso_id, titulo, ordem) VALUES
(1, 'HTML — Estrutura da Página', 1),
(1, 'CSS — Estilização', 2),
(1, 'JavaScript — Interatividade', 3),
(1, 'PHP — Servidor', 4),
(1, 'PHP + MySQL — Banco de Dados', 5),
(1, 'Projeto Final', 6);

-- Módulos do curso 2
INSERT INTO modulos (curso_id, titulo, ordem) VALUES
(2, 'Orientação a Objetos em PHP', 1),
(2, 'Conexão e CRUD com MySQL', 2);

-- Aulas do módulo 1 (HTML)
INSERT INTO aulas (modulo_id, titulo, conteudo, ordem) VALUES
(1, 'Estrutura Básica do HTML',
 '<h2>Estrutura Básica do HTML</h2><p>Todo documento HTML começa com a declaração <code>&lt;!DOCTYPE html&gt;</code>...</p><pre><code>&lt;!DOCTYPE html&gt;\n&lt;html lang="pt-BR"&gt;\n  &lt;head&gt;\n    &lt;meta charset="UTF-8"&gt;\n    &lt;title&gt;Minha Página&lt;/title&gt;\n  &lt;/head&gt;\n  &lt;body&gt;\n    &lt;h1&gt;Olá, Mundo!&lt;/h1&gt;\n  &lt;/body&gt;\n&lt;/html&gt;</code></pre>',
 1),
(1, 'Tags e Elementos HTML',
 '<h2>Tags HTML Essenciais</h2><p>Conheça as principais tags: headings, parágrafos, listas, links e imagens.</p>',
 2),
(1, 'Formulários HTML',
 '<h2>Formulários</h2><p>Aprenda a criar formulários com inputs, selects, checkboxes e botões de submit.</p>',
 3);

-- Aulas do módulo 2 (CSS)
INSERT INTO aulas (modulo_id, titulo, conteudo, ordem) VALUES
(2, 'Seletores CSS',
 '<h2>Seletores CSS</h2><p>Aprenda a selecionar elementos por tag, classe, ID e atributo.</p>',
 1),
(2, 'Flexbox',
 '<h2>Flexbox</h2><p>O modelo de layout Flexbox facilita a criação de layouts responsivos e alinhamentos complexos.</p>',
 2);

-- Desafios de exemplo
INSERT INTO desafios (aula_id, professor_id, titulo, descricao, nivel, codigo_inicial, resposta_esperada) VALUES
(1, 2, 'Criar estrutura HTML básica',
 'Crie uma página HTML completa com doctype, html, head e body. Adicione um título "Minha Primeira Página" e um parágrafo de boas-vindas.',
 'basico',
 '<!DOCTYPE html>\n<html>\n  <head>\n    <!-- Adicione o título aqui -->\n  </head>\n  <body>\n    <!-- Adicione o parágrafo aqui -->\n  </body>\n</html>',
 '<!DOCTYPE html>\n<html lang="pt-BR">\n  <head>\n    <meta charset="UTF-8">\n    <title>Minha Primeira Página</title>\n  </head>\n  <body>\n    <p>Bem-vindo!</p>\n  </body>\n</html>'),
(3, 2, 'Formulário de Contato',
 'Crie um formulário com campos: nome (text), email (email) e mensagem (textarea), com um botão de envio.',
 'intermediario',
 '<form>\n  <!-- Adicione os campos aqui -->\n</form>',
 '<form>\n  <input type="text" name="nome" placeholder="Seu nome">\n  <input type="email" name="email" placeholder="Seu e-mail">\n  <textarea name="mensagem"></textarea>\n  <button type="submit">Enviar</button>\n</form>'),
(4, 2, 'Centralizando com Flexbox',
 'Use Flexbox para centralizar um elemento div tanto horizontal quanto verticalmente dentro de um container.',
 'intermediario',
 '.container {\n  /* Adicione as propriedades Flexbox */\n  width: 100vw;\n  height: 100vh;\n}\n.caixa {\n  width: 200px;\n  height: 200px;\n  background: blue;\n}',
 '.container {\n  display: flex;\n  justify-content: center;\n  align-items: center;\n  width: 100vw;\n  height: 100vh;\n}\n.caixa {\n  width: 200px;\n  height: 200px;\n  background: blue;\n}');

-- Nota de exemplo
INSERT INTO notas (aluno_id, desafio_id, professor_id, nota, comentario) VALUES
(3, 1, 2, 9.5, 'Excelente trabalho! Estrutura HTML correta e bem organizada.');

-- Progresso do aluno
INSERT INTO progresso (aluno_id, aula_id, concluido, data_conclusao) VALUES
(3, 1, 1, NOW()),
(3, 2, 1, NOW()),
(3, 3, 0, NULL);

-- Certificado de exemplo
INSERT INTO certificados (aluno_id, curso_id, nota_final, codigo_verificacao) VALUES
(3, 1, 9.5, SHA2(CONCAT('3-1-', NOW()), 256));
