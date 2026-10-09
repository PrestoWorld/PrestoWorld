import React from 'react';

export const AnalyticsPage: React.FC = () => {
  const performanceData = useMemo(() => {
    return [
      { label: 'CVR (Conversion Rate)', data: [65, 68, 72, 60, 67, 75, 80], color: 'text-emerald-600' },
      { label: 'Tốc độ trang', data: [2.1, 1.8, 2.5, 2.3, 1.9, 2.0, 1.7], color: 'text-blue-600', unit: 'giây' },
      { label: 'Tráfik organic', data: [1200, 1350, 1100, 1400, 1300, 1500, 1350], color: 'text-green-600' },
      { label: 'Tỷ lệ thoát', data: [45, 42, 38, 40, 45, 35, 30], color: 'text-rose-600' },
    ];
  }, []);

  const revenueData = useMemo(() => {
    return [
      { label: 'Doanh thu ngày', data: [2500000, 2800000, 2200000, 3100000, 2700000, 3500000, 2900000], color: 'text-indigo-600', currency: 'đ' },
      { label: 'Đơn hàng', data: [45, 52, 38, 62, 50, 68, 55], color: 'text-amber-600', unit: 'đơn' },
    ];
  }, []);

  return (
    <div className="space-y-4 animate-in fade-in duration-200 max-w-7xl mx-auto">
      <div>
        <h1 className="text-xl sm:text-2xl font-bold text-slate-900">Phân Tích & Tối Ưu</h1>
        <p className="text-sm text-slate-500 mt-1">Theo dõi hiệu suất và tìm cơ hội cải thiện</p>
      </div>

      {/* Performance metrics */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
        {performanceData.map((metric) => (
          <div
            key={metric.label}
            className="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 shadow-sm"
          >
            <div className="flex items-center justify-between mb-3">
              <div>
                <p className="text-sm text-slate-500">{metric.label}</p>
                <p className="text-lg font-medium text-slate-900">{metric.data[Math.floor(metric.data.length / 2)]} {metric.unit || ''}</p>
              </div>
              <div className={`w-4 h-4 rounded-full ${metric.color}`} />
            </div>
            <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
              <div
                className={`h-full rounded-full bg-${metric.color === 'text-emerald-600' ? 'emerald' : metric.color === 'text-blue-600' ? 'blue' : metric.color === 'text-green-600' ? 'green' : 'rose'} transition-colors`}
                style={{ width: `${((metric.data[0] || 0) / Math.max(...metric.data) * 100) || 0}%` }}
              />
            </div>
          </div>
        ))}
      </div>

      {/* Revenue chart */}
      <div className="mt-6 bg-white rounded-xl border border-slate-200 p-5 sm:p-6 shadow-sm">
        <h2 className="text-sm text-slate-500 mb-3">Doanh thu tuần qua</h2>
        <div className="h-64 bg-slate-100 rounded-lg overflow-hidden">
          {revenueData[0].data.map((val, i) => (
            <div
              key={i}
              className="flex items-end justify-between h-full"
              style={{ height: `${val}px` }}
            >
              <div className={`w-2 rounded-tl ${revenueData[0].color === 'text-indigo-600' ? 'indigo' : 'blue'} h-full absolute left-0 style={{ background: 'linear-gradient(to top, ' + revenueData[0].color + ', transparent 50%)' }}`} />
              <div className={`w-2 rounded-tr ${revenueData[0].color === 'text-indigo-600' ? 'indigo' : 'blue'} h-full absolute right-0 style={{ background: 'linear-gradient(to bottom, ' + revenueData[0].color + ', transparent 50%)' }}`} />
              <span className="absolute bottom-1 left-1/2 -translate-x-1/2 text-[8px] text-slate-500 font-mono">{val.toLocaleString('vi-VN')}đ</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

import { useMemo } from 'react';