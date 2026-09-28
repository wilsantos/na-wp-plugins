# NA Reuniões Embed — Plugin WordPress

Plugin WordPress que exibe reuniões **online** de Narcóticos Anônimos via shortcode, com dados do BMLT processados em PHP.

## Instalação

1. Copie a pasta `na-reunioes-embed` para `wp-content/plugins/`
2. Ative o plugin em **Plugins** no painel WordPress
3. Insira o shortcode em qualquer página ou post

## Uso

```
[na_reunioes]
```

O shortcode renderiza a listagem de reuniões online nas seções:

- **Em andamento** — reuniões acontecendo agora
- **Em breve** — começam nos próximos 60 minutos
- **Próximas** — demais reuniões nas próximas 24h

## Desenvolvimento local (Docker)

Na raiz do monorepo:

```bash
docker compose up -d
```

- WordPress: http://localhost:8080
- O plugin é montado automaticamente via volume

Ative o plugin, crie uma página com `[na_reunioes]` e publique.

## API REST

```
GET /wp-json/na-reunioes/v1/reunioes?type=online
GET /wp-json/na-reunioes/v1/reunioes?type=online&format=html
```

Resposta JSON:

```json
{
  "now": [],
  "soon": [],
  "next24h": [],
  "meta": {
    "timestamp": "...",
    "timezone": "America/Sao_Paulo",
    "current_time": "21:00",
    "cache_hit": true,
    "cache_layer": "transient",
    "total_count": 0
  }
}
```

## Cache

Dados brutos do BMLT são cacheados via `wp_transient` por **600 segundos** (10 minutos).

## CSS

O plugin carrega três folhas de estilo:

1. **Geist** (Google Fonts) — mesma fonte do app Next.js
2. **`na-reunioes-scoped.css`** — tokens de design, reset e tipografia fixa dentro de `#na-reunioes-embed` (não herda do tema WP)
3. **`na-reunioes.css`** — utilitários Tailwind compilados do app Next.js

Para recompilar a partir do app Next.js:

```bash
cd apps/nareunioes
pnpm build:widget
cp public/embed/na-reunioes-widget.css ../../wp-content/plugins/na-reunioes-embed/assets/css/na-reunioes.css
```

## Funcionalidades do embed JS

- Botão **Atualizar** (fetch HTML via REST)
- Auto-refresh a cada **120 segundos**
- **Copiar** ID/senha Zoom
- **Compartilhar** reunião (Web Share API ou clipboard) — nunca compartilha link da sala com credencial

## Estrutura

```
na-reunioes-embed/
├── na-reunioes-embed.php
├── includes/
│   ├── services/          # Lógica BMLT, normalização, timezone SP
│   ├── rest/              # REST API
│   └── class-*.php        # Plugin, shortcode, renderer
├── templates/partials/    # HTML dos cards e seções
└── assets/
    ├── css/na-reunioes.css
    └── js/embed.js
```
