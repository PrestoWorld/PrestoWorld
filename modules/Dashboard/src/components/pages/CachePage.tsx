import React, { useCallback, useEffect, useState } from 'react';
import {
  AlertCircle,
  Braces,
  CheckCircle2,
  Clock,
  FileCode2,
  HardDrive,
  LayoutTemplate,
  Link2,
  Loader2,
  RefreshCw,
  Server,
  Trash2,
  Zap,
} from 'lucide-react';

interface CacheTypeCard {
  label: string;
  driver: string;
  location: string;
  description: string;
  enabled: boolean;
  ttl: number | null;
  entries: number;
  bytes: number;
  hits: number;
  misses: number;
  hit_rate: number;
  clears: string[];
}

interface FrameworkCacheItem {
  id: string;
  label: string;
  entries: number;
  bytes: number;
}

interface TimingRow {
  count: number;
  avg_load: number;
  avg_parse: number;
  avg_render: number;
  last: number;
}

interface CacheStats {
  types: Record<string, CacheTypeCard>;
  framework: FrameworkCacheItem[];
  timing: Record<string, TimingRow>;
  counters: Record<string, number>;
  generated_at: string;
}

const TYPE_ICONS: Record<string, React.ComponentType<{ className?: string }>> = {
  page: FileCode2,
  fragment: LayoutTemplate,
  parse: Braces,
  hooks: Link2,
};

const TYPE_ORDER = ['page', 'fragment', 'parse', 'hooks'];

function formatBytes(bytes: number): string {
  if (bytes <= 0) return '0 B';
  const units = ['B', 'KB', 'MB', 'GB'];
  const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
  return `${(bytes / 1024 ** i).toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
}

async function fetchJson<T>(url: string, init?: RequestInit): Promise<T> {
  const res = await fetch(url, {
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    ...init,
  });
  if (!res.ok) {
    let message = `HTTP ${res.status}`;
    try {
      const body = await res.json();
      if (body?.error) message = String(body.error);
    } catch {
      /* ignore */
    }
    throw new Error(message);
  }
  return res.json();
}

export const CachePage: React.FC = () => {
  const [stats, setStats] = useState<CacheStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [ttlDrafts, setTtlDrafts] = useState<Record<string, string>>({});

  const load = useCallback(async () => {
    try {
      const data = await fetchJson<CacheStats>('/api/admin/cache/stats');
      setStats(data);
      setError(null);
      const drafts: Record<string, string> = {};
      for (const [id, card] of Object.entries(data.types ?? {})) {
        if (card.ttl !== null) drafts[id] = String(card.ttl);
      }
      setTtlDrafts(drafts);
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const flash = (message: string) => {
    setNotice(message);
    window.setTimeout(() => setNotice(null), 3500);
  };

  const purge = async (type: string) => {
    setBusy(`purge:${type}`);
    try {
      await fetchJson('/api/admin/cache/purge', { method: 'POST', body: JSON.stringify({ type }) });
      flash(type === 'all' ? 'Đã xoá toàn bộ content cache' : `Đã xoá cache: ${type}`);
      await load();
    } catch (e) {
      flash(`Lỗi: ${e instanceof Error ? e.message : e}`);
    } finally {
      setBusy(null);
    }
  };

  const toggle = async (type: string, enabled: boolean) => {
    setBusy(`toggle:${type}`);
    try {
      const ttl = ttlDrafts[type];
      await fetchJson('/api/admin/cache/settings', {
        method: 'POST',
        body: JSON.stringify({ type, enabled, ttl: ttl !== undefined ? Number(ttl) : undefined }),
      });
      flash(`${type}: ${enabled ? 'đã bật' : 'đã tắt'}`);
      await load();
    } catch (e) {
      flash(`Lỗi: ${e instanceof Error ? e.message : e}`);
    } finally {
      setBusy(null);
    }
  };

  const saveTtl = async (type: string) => {
    const card = stats?.types?.[type];
    if (!card) return;
    setBusy(`ttl:${type}`);
    try {
      await fetchJson('/api/admin/cache/settings', {
        method: 'POST',
        body: JSON.stringify({ type, enabled: card.enabled, ttl: Number(ttlDrafts[type] ?? card.ttl ?? 0) }),
      });
      flash(`${type}: đã lưu TTL`);
      await load();
    } catch (e) {
      flash(`Lỗi: ${e instanceof Error ? e.message : e}`);
    } finally {
      setBusy(null);
    }
  };

  if (loading) {
    return (
      <div className="max-w-7xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm p-10 flex items-center justify-center gap-2 text-slate-400 text-sm">
        <Loader2 className="w-4 h-4 animate-spin" /> Đang tải thống kê cache...
      </div>
    );
  }

  if (error || !stats) {
    return (
      <div className="max-w-7xl mx-auto bg-white rounded-2xl border border-rose-100 shadow-sm p-8 text-center space-y-2">
        <AlertCircle className="w-6 h-6 text-rose-500 mx-auto" />
        <p className="text-sm text-slate-600">Không tải được thống kê cache.</p>
        <p className="text-xs text-slate-400">{error}</p>
        <button
          onClick={() => { setLoading(true); void load(); }}
          className="px-4 py-2 bg-slate-900 hover:bg-slate-700 text-white text-xs font-bold rounded-xl transition-colors"
        >
          Thử lại
        </button>
      </div>
    );
  }

  const cards = TYPE_ORDER.filter((id) => stats.types?.[id]).map((id) => [id, stats.types[id]] as const);
  const totalHits = Object.values(stats.types ?? {}).reduce((sum, t) => sum + t.hits, 0);
  const totalMisses = Object.values(stats.types ?? {}).reduce((sum, t) => sum + t.misses, 0);
  const invalidations = stats.counters?.invalidations ?? 0;
  const timingRows = Object.entries(stats.timing ?? {});

  return (
    <div className="space-y-4 animate-in fade-in duration-200 max-w-7xl mx-auto">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 className="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2">
            <Zap className="w-5 h-5 text-amber-500" /> Cache &amp; Bộ Nhớ Đệm
          </h1>
          <p className="text-sm text-slate-500 mt-1">
            Quản lý từng loại cache: thống kê, bật/tắt, TTL và cách xoá. Visitor không nhìn thấy cache — nội dung luôn
            khớp với bản render mới nhất.
          </p>
        </div>
        <div className="flex items-center gap-2">
          <button
            onClick={() => void load()}
            className="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl transition-colors"
          >
            <RefreshCw className="w-3.5 h-3.5" /> Làm mới
          </button>
          <button
            onClick={() => void purge('all')}
            disabled={busy !== null}
            className="inline-flex items-center gap-1.5 px-3 py-2 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition-colors"
          >
            {busy === 'purge:all' ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Trash2 className="w-3.5 h-3.5" />}
            Xoá toàn bộ content cache
          </button>
        </div>
      </div>

      {notice && (
        <div className="flex items-center gap-2 bg-emerald-50 border border-emerald-100 text-emerald-800 text-xs font-medium px-4 py-2.5 rounded-xl">
          <CheckCircle2 className="w-4 h-4" /> {notice}
        </div>
      )}

      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <SummaryTile label="Tổng lượt HIT" value={totalHits.toLocaleString()} accent="text-emerald-600" />
        <SummaryTile label="Tổng lượt MISS" value={totalMisses.toLocaleString()} accent="text-slate-600" />
        <SummaryTile label="Lần tự invalidate" value={invalidations.toLocaleString()} accent="text-amber-600" />
        <SummaryTile label="Loại cache đang bật" value={`${cards.filter(([, c]) => c.enabled).length}/${cards.length}`} accent="text-blue-600" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {cards.map(([id, card]) => {
          const Icon = TYPE_ICONS[id] ?? Zap;
          const isBusy = busy !== null && busy.endsWith(`:${id}`);
          return (
            <div key={id} className="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col gap-4">
              <div className="flex items-start justify-between gap-3">
                <div className="flex items-start gap-3">
                  <div className="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center ring-1 ring-blue-100 shrink-0">
                    <Icon className="w-5 h-5" />
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <h2 className="text-sm font-bold text-slate-900">{card.label}</h2>
                      <span className={`text-[10px] font-extrabold uppercase tracking-wider px-1.5 py-0.5 rounded-md ${card.driver === 'RAM' ? 'bg-violet-50 text-violet-600' : 'bg-sky-50 text-sky-600'}`}>
                        {card.driver}
                      </span>
                    </div>
                    <p className="text-xs text-slate-500 mt-0.5">{card.description}</p>
                    <p className="text-[11px] text-slate-400 mt-1 font-mono">{card.location}</p>
                  </div>
                </div>
                <button
                  onClick={() => void toggle(id, !card.enabled)}
                  disabled={isBusy}
                  role="switch"
                  aria-checked={card.enabled}
                  className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors shrink-0 ${card.enabled ? 'bg-emerald-500' : 'bg-slate-300'} ${isBusy ? 'opacity-50' : ''}`}
                >
                  <span className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${card.enabled ? 'translate-x-6' : 'translate-x-1'}`} />
                </button>
              </div>

              <div className="grid grid-cols-3 gap-2 text-center">
                <StatChip label="Entries" value={card.entries.toLocaleString()} />
                <StatChip label="Dung lượng" value={formatBytes(card.bytes)} />
                <StatChip label="HIT rate" value={`${card.hit_rate}%`} />
              </div>

              <div>
                <div className="flex justify-between text-[11px] text-slate-500 mb-1">
                  <span>{card.hits.toLocaleString()} hit</span>
                  <span>{card.misses.toLocaleString()} miss</span>
                </div>
                <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
                  <div
                    className="h-full bg-emerald-500 rounded-full transition-all"
                    style={{ width: `${Math.max(card.hit_rate, card.hits > 0 ? 2 : 0)}%` }}
                  />
                </div>
              </div>

              <div className="bg-slate-50 border border-slate-100 rounded-xl p-3 space-y-1.5">
                <p className="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Cache này được xoá như thế nào?</p>
                {card.clears.map((rule) => (
                  <p key={rule} className="text-[11px] text-slate-600 flex items-start gap-1.5">
                    <span className="text-slate-400 mt-px">•</span> {rule}
                  </p>
                ))}
              </div>

              <div className="flex items-center gap-2 mt-auto">
                {card.ttl !== null && (
                  <>
                    <label className="flex items-center gap-1.5 text-xs text-slate-500">
                      <Clock className="w-3.5 h-3.5" /> TTL (giây)
                    </label>
                    <input
                      type="number"
                      min={0}
                      value={ttlDrafts[id] ?? String(card.ttl)}
                      onChange={(e) => setTtlDrafts((d) => ({ ...d, [id]: e.target.value }))}
                      onBlur={() => String(ttlDrafts[id] ?? '') !== String(card.ttl) && void saveTtl(id)}
                      className="w-24 px-2 py-1.5 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100"
                    />
                  </>
                )}
                <button
                  onClick={() => void purge(id)}
                  disabled={isBusy}
                  className="ml-auto inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-900 hover:bg-slate-700 disabled:opacity-50 text-white text-xs font-bold rounded-lg transition-colors"
                >
                  {isBusy ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Trash2 className="w-3.5 h-3.5" />}
                  Clear
                </button>
              </div>
            </div>
          );
        })}
      </div>

      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <Server className="w-4 h-4 text-slate-400" />
            <h2 className="text-sm font-bold text-slate-900">Framework caches</h2>
            <span className="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">File</span>
          </div>
          <button
            onClick={() => void purge('framework')}
            disabled={busy !== null}
            className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 disabled:opacity-50 text-slate-700 text-xs font-bold rounded-lg transition-colors"
          >
            {busy === 'purge:framework' ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Trash2 className="w-3.5 h-3.5" />}
            Xoá framework caches
          </button>
        </div>
        <div className="divide-y divide-slate-100">
          {(stats.framework ?? []).map((item) => (
            <div key={item.id} className="flex items-center justify-between py-2.5">
              <div className="flex items-center gap-2">
                <HardDrive className="w-3.5 h-3.5 text-slate-400" />
                <span className="text-xs font-medium text-slate-700">{item.label}</span>
              </div>
              <div className="flex items-center gap-4 text-xs text-slate-500">
                <span>{item.entries > 0 ? `${item.entries} file` : '—'}</span>
                <span className="w-16 text-right">{item.bytes > 0 ? formatBytes(item.bytes) : '—'}</span>
              </div>
            </div>
          ))}
        </div>
        <p className="text-[11px] text-slate-400 mt-3">
          Config / route / rewrite / discovery / compiled views — tự rebuild ngay khi thiếu, xoá an toàn mọi lúc.
        </p>
      </div>

      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div className="flex items-center gap-2 mb-4">
          <Clock className="w-4 h-4 text-slate-400" />
          <h2 className="text-sm font-bold text-slate-900">Render timing theo template</h2>
          <span className="text-[11px] text-slate-400">(trung bình mỗi request, cộng dồn mọi worker)</span>
        </div>
        {timingRows.length === 0 ? (
          <p className="text-xs text-slate-400 py-4 text-center">Chưa có dữ liệu — hãy truy cập một vài trang frontend.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-xs">
              <thead>
                <tr className="text-left text-[10px] uppercase tracking-wider text-slate-400 border-b border-slate-100">
                  <th className="py-2 pr-4 font-bold">Template</th>
                  <th className="py-2 pr-4 font-bold text-right">Requests</th>
                  <th className="py-2 pr-4 font-bold text-right">Load</th>
                  <th className="py-2 pr-4 font-bold text-right">Parse</th>
                  <th className="py-2 pr-4 font-bold text-right">Render</th>
                  <th className="py-2 font-bold text-right">Lần cuối</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-50">
                {timingRows.map(([template, row]) => (
                  <tr key={template}>
                    <td className="py-2 pr-4 font-mono text-slate-700">{template}</td>
                    <td className="py-2 pr-4 text-right text-slate-600">{row.count.toLocaleString()}</td>
                    <td className="py-2 pr-4 text-right text-slate-600">{row.avg_load.toFixed(2)} ms</td>
                    <td className="py-2 pr-4 text-right text-slate-600">{row.avg_parse.toFixed(2)} ms</td>
                    <td className="py-2 pr-4 text-right font-semibold text-slate-900">{row.avg_render.toFixed(2)} ms</td>
                    <td className="py-2 text-right text-slate-400">{row.last ? new Date(row.last * 1000).toLocaleString() : '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      <p className="text-[11px] text-slate-400 text-right">
        Thống kê cập nhật lúc {new Date(stats.generated_at).toLocaleString()}
      </p>
    </div>
  );
};

const SummaryTile: React.FC<{ label: string; value: string; accent: string }> = ({ label, value, accent }) => (
  <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
    <p className="text-xs text-slate-500">{label}</p>
    <p className={`text-lg font-bold mt-1 ${accent}`}>{value}</p>
  </div>
);

const StatChip: React.FC<{ label: string; value: string }> = ({ label, value }) => (
  <div className="bg-slate-50 border border-slate-100 rounded-lg py-2">
    <p className="text-[10px] uppercase tracking-wider text-slate-400 font-bold">{label}</p>
    <p className="text-sm font-semibold text-slate-800 mt-0.5">{value}</p>
  </div>
);
