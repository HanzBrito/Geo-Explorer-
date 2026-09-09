# Geo-Explorer MCP Server

Servidor MCP (Model Context Protocol) para integração com a plataforma **Geo-Explorer**.

## Ferramentas disponíveis

| Ferramenta | Descrição |
|---|---|
| `listar_cursos` | Lista todos os cursos da plataforma |
| `listar_aulas` | Lista aulas de um curso ou módulo |
| `verificar_certificado` | Valida autenticidade de um certificado pelo código |
| `status_aluno` | Retorna progresso detalhado de um aluno |

## Instalação

```bash
cd mcp-server
npm install
npm run build
```

## Configuração

Variáveis de ambiente (opcional — padrão: `localhost`/`root`/sem senha/`geo_explorer`):

```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=sua_senha
DB_NAME=geo_explorer
```

## Executar

```bash
npm start
```

## Registro no Bob (`.bob/mcp.json`)

```json
{
  "mcpServers": {
    "geo-explorer": {
      "command": "node",
      "args": ["caminho/para/geo-explorer/mcp-server/dist/index.js"],
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
