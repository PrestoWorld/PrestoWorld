# 07. Cloudflare - R2 & Workers Infrastructure

## 7.1 Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                   Cloudflare Edge Network               │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │ Cloudflare    │  │ Cloudflare    │  │ Cloudflare    │    │
│  │ Workers       │  │ R2           │  │ CDN           │    │
│  │ (API Logic)   │  │ (Storage)    │  │ (Cache)      │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
│          │                 │                 │              │
│          └─────────────────┴─────────────────┘              │
│                            │                               │
└────────────────────────────┼───────────────────────────────┘
                             │
┌────────────────────────────┼───────────────────────────────┐
│                    Application Server                   │
│                  (PrestoWorld Instance)                  │
└─────────────────────────────────────────────────────────────┘
```

---

## 7.2 Cloudflare Workers

### 7.2.1 Worker API Endpoints

**Plugin Marketplace API**:
```typescript
// Cloudflare Worker: api/worker.ts
export default {
  async fetch(request: Request, env: Env): Promise<Response> {
    const url = new URL(request.url);
    
    // CORS headers
    const corsHeaders = {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
    };

    if (request.method === 'OPTIONS') {
      return new Response(null, { headers: corsHeaders });
    }

    // Plugin list endpoint
    if (url.pathname === '/api/plugins') {
      return handlePluginList(request, env);
    }

    // Plugin details endpoint
    if (url.pathname.startsWith('/api/plugins/')) {
      const slug = url.pathname.split('/')[3];
      return handlePluginDetail(slug, env);
    }

    // Plugin download endpoint
    if (url.pathname === '/api/plugins/download') {
      return handlePluginDownload(request, env);
    }

    // Error report endpoint
    if (url.pathname === '/api/error-report') {
      return handleErrorReport(request, env);
    }

    return new Response('Not Found', { status: 404 });
  }
};
```

### 7.2.2 Plugin List Handler

```typescript
async function handlePluginList(request: Request, env: Env): Promise<Response> {
  const url = new URL(request.url);
  const search = url.searchParams.get('search');
  const category = url.searchParams.get('category');

  // Query metadata from R2
  const metadata = await env.PLUGIN_METADATA.get('plugins-index.json');
  const plugins = JSON.parse(await metadata.text());

  // Filter
  let filtered = plugins;
  if (search) {
    filtered = filtered.filter((p: any) => 
      p.name.toLowerCase().includes(search.toLowerCase()) ||
      p.description.toLowerCase().includes(search.toLowerCase())
    );
  }
  if (category) {
    filtered = filtered.filter((p: any) => p.category === category);
  }

  return new Response(JSON.stringify(filtered), {
    headers: {
      'Content-Type': 'application/json',
      'Access-Control-Allow-Origin': '*',
    },
  });
}
```

### 7.2.3 Plugin Download Handler

```typescript
async function handlePluginDownload(request: Request, env: Env): Promise<Response> {
  const { slug, version } = await request.json();

  // Get plugin from R2
  const object = await env.PLUGIN_BUCKET.get(`${slug}/${slug}-${version}.zip`);

  if (!object) {
    return new Response('Plugin not found', { status: 404 });
  }

  // Stream response
  return new Response(object.body, {
    headers: {
      'Content-Type': 'application/zip',
      'Content-Disposition': `attachment; filename="${slug}.zip"`,
    },
  });
}
```

### 7.2.4 Error Report Handler

```typescript
async function handleErrorReport(request: Request, env: Env): Promise<Response> {
  const report = await request.json();

  // Validate report
  if (!report.system_info || !report.error_data) {
    return new Response('Invalid report format', { status: 400 });
  }

  // Store in R2 for analysis
  const timestamp = new Date().toISOString();
  const key = `error-reports/${timestamp}-${report.system_info.core_version}.json`;
  
  await env.ERROR_BUCKET.put(key, JSON.stringify(report));

  // Aggregate with similar errors
  await aggregateError(report, env);

  return new Response(JSON.stringify({ success: true }), {
    headers: { 'Content-Type': 'application/json' },
  });
}

async function aggregateError(report: any, env: Env): Promise<void> {
  const key = `error-aggregates/${report.error_data.plugin}`;
  const existing = await env.ERROR_BUCKET.get(key);

  let aggregate = existing ? JSON.parse(await existing.text()) : {
    plugin: report.error_data.plugin,
    error_type: report.error_data.error_type,
    count: 0,
    occurrences: [],
  };

  aggregate.count++;
  aggregate.occurrences.push({
    timestamp: new Date().toISOString(),
    core_version: report.system_info.core_version,
  });

  await env.ERROR_BUCKET.put(key, JSON.stringify(aggregate));
}
```

---

## 7.3 Cloudflare R2

### 7.3.1 Bucket Structure

```
prestoworld-r2/
├── plugins/
│   ├── contact-form-7/
│   │   ├── contact-form-7-5.8.0.zip
│   │   ├── contact-form-7-5.8.1.zip
│   │   ├── metadata.json
│   │   └── screenshot.png
│   ├── yoast-seo/
│   │   └── ...
│   └── akismet/
│       └── ...
├── translations/
│   ├── mysql-to-postgres-v1.json
│   ├── mysql-to-postgres-v2.json
│   └── custom-rules.json
├── error-reports/
│   ├── 2024-01-01-report1.json
│   └── ...
└── backups/
    ├── sqlite-backups/
    └── postgres-backups/
```

### 7.3.2 Plugin Metadata Format

```json
{
  "slug": "contact-form-7",
  "name": "Contact Form 7",
  "description": "Just another contact form plugin. Simple but flexible.",
  "version": "5.8.0",
  "author": "Takayuki Miyoshi",
  "homepage": "https://contactform7.com/",
  "category": "forms",
  "requires": "5.6",
  "tested": "6.4",
  "downloaded": 1000000,
  "rating": 4.8,
  "num_ratings": 5000,
  "last_updated": "2024-01-01",
  "compatibility": {
    "presto_world": {
      "status": "compatible",
      "notes": "Fully compatible with SQLite",
      "issues": []
    }
  },
  "assets": {
    "icon": "https://ps.w.org/plugins/cf7/icon.png",
    "screenshot": "https://ps.w.org/plugins/cf7/screenshot.png"
  }
}
```

### 7.3.3 Upload Process

```typescript
// Cloudflare Worker: upload/worker.ts
export async function uploadPlugin(request: Request, env: Env): Promise<Response> {
  const formData = await request.formData();
  const file = formData.get('file') as File;
  const metadata = formData.get('metadata') as string;

  // Store plugin zip
  const slug = JSON.parse(metadata).slug;
  const version = JSON.parse(metadata).version;
  
  await env.PLUGIN_BUCKET.put(
    `plugins/${slug}/${slug}-${version}.zip`,
    file.stream()
  );

  // Update metadata
  await updatePluginMetadata(slug, JSON.parse(metadata), env);

  return new Response(JSON.stringify({ success: true }), {
    headers: { 'Content-Type': 'application/json' },
  });
}

async function updatePluginMetadata(slug: string, metadata: any, env: Env): Promise<void> {
  const key = 'plugins-index.json';
  const existing = await env.PLUGIN_METADATA.get(key);
  const plugins = existing ? JSON.parse(await existing.text()) : {};

  plugins[slug] = metadata;

  await env.PLUGIN_METADATA.put(key, JSON.stringify(plugins));
}
```

---

## 7.4 Translation Maps

### 7.4.1 Hotfix Distribution

**Translation Map Format**:
```json
{
  "version": "2.0",
  "rules": [
    {
      "kind": "sql",
      "pattern": "/ENGINE=InnoDB/i",
      "replacement": "",
      "priority": "high",
      "description": "Remove MySQL ENGINE clause"
    },
    {
      "kind": "sql",
      "pattern": "/TINYINT\\(1\\)/i",
      "replacement": "BOOLEAN",
      "priority": "high",
      "description": "Convert TINYINT(1) to BOOLEAN"
    },
    {
      "kind": "sql",
      "pattern": "/AUTO_INCREMENT/i",
      "replacement": "SERIAL",
      "target": "postgresql",
      "description": "Convert AUTO_INCREMENT to SERIAL for PostgreSQL"
    },
    {
      "kind": "function",
      "source": "esc_html",
      "target": "PrestoWorld\\Core\\Escape::html",
      "mode": "sr",
      "since": "1.0.0",
      "description": "Function transformer mapping (shim + AST rewrite)"
    },
    {
      "kind": "class",
      "source": "WP_Query",
      "target": "PrestoWorld\\Core\\Post\\PostQuery",
      "mode": "sr",
      "structural": { "global_state": "scoped" },
      "description": "Class transformer mapping"
    }
  ],
  "created_at": "2024-01-01T00:00:00Z",
  "applies_to": "1.5.0+"
}
```

> **Tham chiếu**: `kind` ∈ `sql` | `function` | `class` — xem chi tiết mapping
> tại [10-wordpress-compiler.md](./10-wordpress-compiler.md).

### 7.4.2 Distribution Worker

```typescript
async function getTranslationMap(request: Request, env: Env): Promise<Response> {
  const clientVersion = request.headers.get('X-Presto-Version');
  
  // Get latest translation map
  const map = await env.TRANSLATIONS.get('latest-map.json');
  const translationData = JSON.parse(await map.text());

  // Check if applicable to client version
  if (clientVersion && !isVersionCompatible(clientVersion, translationData.applies_to)) {
    // Return previous version
    const previousMap = await env.TRANSLATIONS.get(`map-${clientVersion}.json`);
    return new Response(previousMap.body);
  }

  return new Response(map.body, {
    headers: { 'Content-Type': 'application/json' },
  });
}

function isVersionCompatible(clientVersion: string, appliesTo: string): boolean {
  // Simple semver comparison
  const client = parseVersion(clientVersion);
  const required = parseVersion(appliesTo);
  
  return client.major >= required.major && 
         client.minor >= required.minor;
}
```

---

## 7.5 CDN Configuration

### 7.5.1 Cache Rules

**Public Assets**:
```
pattern: /assets/*
cache_ttl: 31536000 (1 year)
edge_ttl: 86400 (1 day)
bypass_cache: false
```

**API Responses**:
```
pattern: /api/plugins/*
cache_ttl: 300 (5 minutes)
edge_ttl: 60 (1 minute)
bypass_cache: false
```

**Plugin Downloads**:
```
pattern: /api/plugins/download
cache_ttl: 3600 (1 hour)
edge_ttl: 600 (10 minutes)
bypass_cache: false
```

### 7.5.2 Cache Purge

```typescript
// Cloudflare Worker: purge/worker.ts
export async function purgeCache(request: Request, env: Env): Promise<Response> {
  const { urls } = await request.json();

  for (const url of urls) {
    await env.CACHE.purge(url);
  }

  return new Response(JSON.stringify({ success: true }), {
    headers: { 'Content-Type': 'application/json' },
  });
}
```

---

## 7.6 Security

### 7.6.1 API Authentication

```typescript
// Cloudflare Worker: auth/worker.ts
async function authenticateRequest(request: Request, env: Env): Promise<boolean> {
  const authHeader = request.headers.get('Authorization');
  
  if (!authHeader) {
    return false;
  }

  const token = authHeader.replace('Bearer ', '');
  
  // Validate token
  const isValid = await validateToken(token, env);
  
  return isValid;
}

async function validateToken(token: string, env: Env): Promise<boolean> {
  // Check against allowed tokens in KV
  const allowed = await env.AUTH_KV.get(`token:${token}`);
  return allowed !== null;
}
```

### 7.6.2 Rate Limiting

```typescript
const rateLimit = new Map<string, { count: number; resetTime: number }>();

async function checkRateLimit(ip: string, limit: number = 100): Promise<boolean> {
  const now = Date.now();
  const record = rateLimit.get(ip) || { count: 0, resetTime: now + 60000 };

  if (now > record.resetTime) {
    record.count = 0;
    record.resetTime = now + 60000;
  }

  if (record.count >= limit) {
    return false;
  }

  record.count++;
  rateLimit.set(ip, record);
  return true;
}
```

---

## 7.7 Monitoring

### 7.7.1 Analytics

```typescript
// Cloudflare Worker: analytics/worker.ts
async function trackEvent(request: Request, env: ENV): Promise<void> {
  const analytics = await request.json();
  
  await env.ANALYTICS.put(
    `events/${Date.now()}-${analytics.event}`,
    JSON.stringify({
      event: analytics.event,
      plugin: analytics.plugin,
      version: analytics.version,
      timestamp: new Date().toISOString(),
    })
  );
}
```

### 7.7.2 Health Check

```typescript
export async function healthCheck(request: Request, env: Env): Promise<Response> {
  const checks = {
    workers: 'healthy',
    r2: 'unknown',
    kv: 'unknown',
  };

  try {
    await env.PLUGIN_BUCKET.list();
    checks.r2 = 'healthy';
  } catch (e) {
    checks.r2 = 'unhealthy';
  }

  try {
    await env.AUTH_KV.get('test');
    checks.kv = 'healthy';
  } catch (e) {
    checks.kv = 'unhealthy';
  }

  return new Response(JSON.stringify(checks), {
    headers: { 'Content-Type': 'application/json' },
  });
}
```

---

## 7.8 Deployment

### 7.8.1 Wrangler Configuration

```toml
# wrangler.toml
name = "prestoworld-api"
main = "api/worker.ts"
compatibility_date = "2024-01-01"

[vars]
ENVIRONMENT = "production"

[[r2_buckets]]
binding = "PLUGIN_BUCKET"
bucket_name = "prestoworld-plugins"

[[r2_buckets]]
binding = "PLUGIN_METADATA"
bucket_name = "prestoworld-metadata"

[[r2_buckets]]
binding = "ERROR_BUCKET"
bucket_name = "prestoworld-errors"

[[r2_buckets]]
binding = "TRANSLATIONS"
bucket_name = "prestoworld-translations"

[[kv_namespaces]]
binding = "AUTH_KV"
id = "prestoworld-auth"

[[kv_namespaces]]
binding = "ANALYTICS"
id = "prestoworld-analytics"
```

### 7.8.2 Deployment Commands

```bash
# Deploy to Cloudflare Workers
wrangler deploy

# Deploy specific worker
wrangler deploy api/worker.ts

# View logs
wrangler tail

# Trigger cache purge
wrangler cache purge --url="https://api.prestoworld.io/plugins/*"
```

---

## 7.9 Cost Optimization

### 7.9.1 R2 Cost Estimation

**Storage**:
- Plugin zips: ~100MB average
- 10,000 plugins: ~1TB
- R2 cost: $0.015/GB/month → ~$15/month

**Requests**:
- 1M API requests/month
- Class A operations: $4.50/Million
- Estimated: $4.50/month

**Total**: ~$20/month for basic usage

### 7.9.2 Workers Cost Estimation

**Requests**:
- 1M requests/month
- Free tier: 100,000 requests/day
- Paid: $5/1M requests

**CPU Time**:
- 10ms average per request
- 10M ms total/month
- Included in free tier

**Total**: ~$5/month for moderate usage

---

## 7.10 Backup Strategy

### 7.10.1 Automated Backups

```typescript
// Cloudflare Worker: backup/worker.ts
export async function backupToR2(request: Request, env: Env): Promise<Response> {
  const { type, data } = await request.json();

  const timestamp = new Date().toISOString();
  const key = `backups/${type}/${timestamp}.json`;

  await env.BACKUP_BUCKET.put(key, JSON.stringify(data));

  return new Response(JSON.stringify({ success: true, key }), {
    headers: { 'Content-Type': 'application/json' },
  });
}
```

### 7.10.2 Retention Policy

```typescript
async function cleanupOldBackups(env: Env): Promise<void> {
  const retentionDays = 30;
  const cutoffDate = new Date();
  cutoffDate.setDate(cutoffDate.getDate() - retentionDays);

  const listed = await env.BACKUP_BUCKET.list({ prefix: 'backups/' });

  for (const object of listed.objects) {
    const objectDate = new Date(object.uploaded);
    if (objectDate < cutoffDate) {
      await env.BACKUP_BUCKET.delete(object.key);
    }
  }
}
```
