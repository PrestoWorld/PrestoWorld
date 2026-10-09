import React, { useEffect, useState } from 'react';
import { CircleHelp, Loader2, ExternalLink } from 'lucide-react';
import type { ScreenProps } from './registry';

interface ScreenData {
  id: string;
  title: string;
  icon?: string | null;
  component?: string | null;
  source?: string | null;
  settings?: Record<string, unknown>;
}

type LoadStatus = 'loading' | 'ok' | 'error';

export const GenericScreen: React.FC<ScreenProps> = ({ screenId, title }) => {
  const [data, setData] = useState<ScreenData | null>(null);
  const [status, setStatus] = useState<LoadStatus>('loading');

  useEffect(() => {
    let cancelled = false;

    setStatus('loading');

    fetch(`/api/admin/screens/${encodeURIComponent(screenId)}`)
      .then((res) => {
        if (!res.ok) {
          throw new Error(`HTTP ${res.status}`);
        }
        return res.json();
      })
      .then((json) => {
        if (cancelled) return;
        setData(json?.data ?? null);
        setStatus('ok');
      })
      .catch(() => {
        if (cancelled) return;
        setData(null);
        setStatus('error');
      });

    return () => {
      cancelled = true;
    };
  }, [screenId]);

  const heading = data?.title ?? title;

  return (
    <div className="max-w-7xl mx-auto space-y-4 animate-in fade-in duration-200">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center ring-1 ring-blue-100">
            <CircleHelp className="w-4 h-4" />
          </div>
          <div>
            <h1 className="text-xl sm:text-2xl font-bold text-slate-900">{heading}</h1>
            <p className="text-sm text-slate-500 mt-1">
              {data?.source ? `Đăng ký bởi: ${data.source}` : 'Màn hình hệ thống'}
            </p>
          </div>
        </div>
      </div>

      {status === 'loading' && (
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-10 flex items-center justify-center gap-2 text-slate-400 text-sm">
          <Loader2 className="w-4 h-4 animate-spin" /> Đang tải màn hình...
        </div>
      )}

      {status === 'ok' && (
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center space-y-4">
          {data?.component ? (
            <p className="text-sm text-slate-500">
              Màn hình sử dụng component <code className="text-xs bg-slate-100 px-1.5 py-0.5 rounded">{data.component}</code>
            </p>
          ) : (
            <>
              <p className="text-sm text-slate-500 leading-relaxed">
                Màn hình này do module/plugin/theme đăng ký qua hook{' '}
                <code className="text-xs bg-slate-100 px-1.5 py-0.5 rounded">admin.screen.register</code>{' '}
                (hoặc registry <code className="text-xs bg-slate-100 px-1.5 py-0.5 rounded">DashboardScreenRegistry</code>).
                Chưa có React component riêng nên đang hiển thị giao diện mặc định này.
              </p>
              <a
                href={`/wp-admin/${screenId}.php`}
                className="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-900 hover:bg-slate-700 text-white text-xs font-bold rounded-xl transition-colors"
              >
                Mở màn hình WP cổ điển <ExternalLink className="w-3.5 h-3.5" />
              </a>
            </>
          )}

          {data?.settings && Object.keys(data.settings).length > 0 && (
            <div className="max-w-md mx-auto text-left bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-2">
              <p className="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Cấu hình màn hình</p>
              {Object.entries(data.settings).map(([key, value]) => (
                <div key={key} className="flex items-center justify-between text-xs">
                  <span className="text-slate-500 font-medium">{key}</span>
                  <code className="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded">
                    {typeof value === 'string' ? value : JSON.stringify(value)}
                  </code>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {status === 'error' && (
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center space-y-2">
          <p className="text-sm text-slate-500">
            Không tải được dữ liệu cho màn hình <code className="text-xs bg-slate-100 px-1.5 py-0.5 rounded">{screenId}</code>.
          </p>
          <p className="text-xs text-slate-400">Hãy đăng ký screen qua <code>admin.screen.register</code> hoặc tạo React component.</p>
        </div>
      )}
    </div>
  );
};