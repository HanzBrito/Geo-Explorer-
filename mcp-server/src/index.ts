import {
  McpServer,
  ResourceTemplate,
} from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { z } from "zod";
import mysql, { RowDataPacket } from "mysql2/promise";

// ============================================================
// GEO-EXPLORER — Servidor MCP
// Ferramentas: listar_cursos, listar_aulas,
//              verificar_certificado, status_aluno
// ============================================================

// Configuração do banco (ajuste conforme seu ambiente)
const DB_CONFIG = {
  host: process.env.DB_HOST || "localhost",
  port: parseInt(process.env.DB_PORT || "3306"),
  user: process.env.DB_USER || "root",
  password: process.env.DB_PASS || "",
  database: process.env.DB_NAME || "geo_explorer",
  charset: "utf8mb4",
};

// Pool de conexões
let pool: mysql.Pool;

function getPool(): mysql.Pool {
  if (!pool) {
    pool = mysql.createPool({ ...DB_CONFIG, waitForConnections: true, connectionLimit: 5 });
  }
  return pool;
}

// ── Servidor MCP ────────────────────────────────────────────
const server = new McpServer({
  name: "geo-explorer",
  version: "1.0.0",
});

// ── Ferramenta: listar_cursos ────────────────────────────────
server.tool(
  "listar_cursos",
  "Lista todos os cursos disponíveis na plataforma Geo-Explorer",
  {
    apenas_ativos: z.boolean().optional().describe("Se true, retorna somente cursos ativos. Padrão: true"),
  },
  async ({ apenas_ativos = true }) => {
    const db = getPool();
    const whereAtivo = apenas_ativos ? "WHERE c.ativo = 1" : "";
    const [rows] = await db.query<RowDataPacket[]>(`
      SELECT c.id, c.titulo, c.descricao, c.ativo, c.criado_em,
             u.nome AS professor,
             COUNT(DISTINCT m.id) AS total_modulos,
             COUNT(DISTINCT a.id) AS total_aulas
      FROM cursos c
      JOIN usuarios u   ON u.id = c.professor_id
      LEFT JOIN modulos m ON m.curso_id = c.id
      LEFT JOIN aulas a   ON a.modulo_id = m.id
      ${whereAtivo}
      GROUP BY c.id
      ORDER BY c.titulo
    `);

    if ((rows as RowDataPacket[]).length === 0) {
      return { content: [{ type: "text", text: "Nenhum curso encontrado." }] };
    }

    const lista = (rows as RowDataPacket[]).map((r: RowDataPacket) =>
      `• [${r.id}] ${r.titulo}\n  Professor: ${r.professor}\n  Módulos: ${r.total_modulos} | Aulas: ${r.total_aulas}\n  Status: ${r.ativo ? "Ativo" : "Inativo"}`
    ).join("\n\n");

    return {
      content: [{
        type: "text",
        text: `=== Cursos Geo-Explorer (${(rows as RowDataPacket[]).length} encontrado(s)) ===\n\n${lista}`,
      }],
    };
  }
);

// ── Ferramenta: listar_aulas ─────────────────────────────────
server.tool(
  "listar_aulas",
  "Lista as aulas de um curso ou módulo específico",
  {
    curso_id: z.number().optional().describe("ID do curso para listar as aulas"),
    modulo_id: z.number().optional().describe("ID do módulo específico (tem prioridade sobre curso_id)"),
  },
  async ({ curso_id, modulo_id }) => {
    const db = getPool();
    let rows: RowDataPacket[];

    if (modulo_id) {
      [rows] = await db.query<RowDataPacket[]>(`
        SELECT a.id, a.titulo, a.ordem, a.video_url,
               m.titulo AS modulo, c.titulo AS curso
        FROM aulas a
        JOIN modulos m ON m.id = a.modulo_id
        JOIN cursos c  ON c.id = m.curso_id
        WHERE a.modulo_id = ?
        ORDER BY a.ordem
      `, [modulo_id]);
    } else if (curso_id) {
      [rows] = await db.query<RowDataPacket[]>(`
        SELECT a.id, a.titulo, a.ordem, a.video_url,
               m.titulo AS modulo, c.titulo AS curso
        FROM aulas a
        JOIN modulos m ON m.id = a.modulo_id
        JOIN cursos c  ON c.id = m.curso_id
        WHERE m.curso_id = ?
        ORDER BY m.ordem, a.ordem
      `, [curso_id]);
    } else {
      return {
        content: [{
          type: "text",
          text: "Informe curso_id ou modulo_id para listar as aulas.",
        }],
      };
    }

    if ((rows as RowDataPacket[]).length === 0) {
      return { content: [{ type: "text", text: "Nenhuma aula encontrada." }] };
    }

    let currentModulo = "";
    const linhas: string[] = [`=== Aulas (${(rows as RowDataPacket[]).length} encontrada(s)) ===\n`];

    for (const r of rows as RowDataPacket[]) {
      if (r.modulo !== currentModulo) {
        currentModulo = r.modulo;
        linhas.push(`\n📚 ${r.modulo}`);
      }
      linhas.push(`  ${r.ordem}. [${r.id}] ${r.titulo}${r.video_url ? " 🎥" : ""}`);
    }

    return { content: [{ type: "text", text: linhas.join("\n") }] };
  }
);

// ── Ferramenta: verificar_certificado ───────────────────────
server.tool(
  "verificar_certificado",
  "Verifica a autenticidade de um certificado Geo-Explorer pelo código de verificação",
  {
    codigo: z.string().min(8).describe("Código de verificação único do certificado"),
  },
  async ({ codigo }) => {
    const db = getPool();
    const [rows] = await db.query<RowDataPacket[]>(`
      SELECT c.id, c.nota_final, c.emitido_em, c.codigo_verificacao,
             u.nome AS aluno,
             cur.titulo AS curso,
             (SELECT nome FROM usuarios WHERE acesso_nivel = 1 LIMIT 1) AS instituicao_admin
      FROM certificados c
      JOIN usuarios u ON u.id = c.aluno_id
      JOIN cursos cur  ON cur.id = c.curso_id
      WHERE c.codigo_verificacao LIKE ?
    `, [`${codigo}%`]);

    if ((rows as RowDataPacket[]).length === 0) {
      return {
        content: [{
          type: "text",
          text: "❌ Certificado NÃO encontrado. Código inválido ou inexistente.",
        }],
      };
    }

    const cert = (rows as RowDataPacket[])[0];
    const dataEmissao = new Date(cert.emitido_em).toLocaleDateString("pt-BR");

    return {
      content: [{
        type: "text",
        text: [
          "✅ CERTIFICADO VÁLIDO — Geo-Explorer",
          "═══════════════════════════════════",
          `Aluno:        ${cert.aluno}`,
          `Curso:        ${cert.curso}`,
          `Nota Final:   ${Number(cert.nota_final).toFixed(1)}`,
          `Emitido em:   ${dataEmissao}`,
          `Código:       ${cert.codigo_verificacao}`,
          "═══════════════════════════════════",
          "Documento autêntico emitido pela plataforma Geo-Explorer.",
        ].join("\n"),
      }],
    };
  }
);

// ── Ferramenta: status_aluno ─────────────────────────────────
server.tool(
  "status_aluno",
  "Retorna o progresso detalhado de um aluno na plataforma",
  {
    aluno_id: z.number().optional().describe("ID do aluno"),
    email: z.string().email().optional().describe("E-mail do aluno (alternativa ao ID)"),
  },
  async ({ aluno_id, email }) => {
    const db = getPool();

    if (!aluno_id && !email) {
      return {
        content: [{
          type: "text",
          text: "Informe aluno_id ou email para consultar o status do aluno.",
        }],
      };
    }

    // Busca aluno
    let alunoRows: RowDataPacket[];
    if (aluno_id) {
      [alunoRows] = await db.query<RowDataPacket[]>(
        "SELECT * FROM usuarios WHERE id = ? AND acesso_nivel = 3",
        [aluno_id]
      );
    } else {
      [alunoRows] = await db.query<RowDataPacket[]>(
        "SELECT * FROM usuarios WHERE email = ? AND acesso_nivel = 3",
        [email]
      );
    }

    if ((alunoRows as RowDataPacket[]).length === 0) {
      return { content: [{ type: "text", text: "Aluno não encontrado." }] };
    }
    const aluno = (alunoRows as RowDataPacket[])[0];

    // Média
    const [mediaRows] = await db.query<RowDataPacket[]>(
      "SELECT ROUND(AVG(nota),2) AS media, COUNT(*) AS total FROM notas WHERE aluno_id = ?",
      [aluno.id]
    );
    const { media, total } = (mediaRows as RowDataPacket[])[0];

    // Aulas concluídas
    const [progRows] = await db.query<RowDataPacket[]>(`
      SELECT COUNT(*) AS concluidas FROM progresso WHERE aluno_id = ? AND concluido = 1
    `, [aluno.id]);
    const concluidas = (progRows as RowDataPacket[])[0].concluidas;

    // Certificados
    const [certRows] = await db.query<RowDataPacket[]>(`
      SELECT c.nota_final, cur.titulo AS curso
      FROM certificados c JOIN cursos cur ON cur.id = c.curso_id
      WHERE c.aluno_id = ?
    `, [aluno.id]);

    const certInfo = (certRows as RowDataPacket[]).length > 0
      ? (certRows as RowDataPacket[]).map((c: RowDataPacket) => `  • ${c.curso} (nota: ${Number(c.nota_final).toFixed(1)})`).join("\n")
      : "  Nenhum certificado ainda.";

    return {
      content: [{
        type: "text",
        text: [
          `👤 Status do Aluno — ${aluno.nome}`,
          "════════════════════════════════",
          `E-mail:          ${aluno.email}`,
          `Cadastro:        ${new Date(aluno.criado_em).toLocaleDateString("pt-BR")}`,
          `Status:          ${aluno.ativo ? "Ativo" : "Inativo"}`,
          "",
          `📊 Progresso:`,
          `  Aulas concluídas: ${concluidas}`,
          `  Notas recebidas:  ${total}`,
          `  Média geral:      ${media ?? "—"}`,
          "",
          `🎓 Certificados:`,
          certInfo,
        ].join("\n"),
      }],
    };
  }
);

// ── Inicialização ────────────────────────────────────────────
async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error("Geo-Explorer MCP Server iniciado (stdio)");
}

main().catch((err) => {
  console.error("Erro fatal no MCP Server:", err);
  process.exit(1);
});
