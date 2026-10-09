import React from 'react';
import { Users, FileText, ShoppingBag, TrendingUp } from 'lucide-react';

interface StatCardProps {
  content: string;
}

export const StatCard: React.FC<StatCardProps> = () => {
  const stats = [
    { label: 'Tổng Bài Viết', value: '1,234', icon: FileText, color: 'text-blue-600 bg-blue-50', change: '+12%', trend: 'up' },
    { label: 'Người Dùng', value: '567', icon: Users, color: 'text-emerald-600 bg-emerald-50', change: '+8%', trend: 'up' },
    { label: 'Đơn Hàng', value: '89', icon: ShoppingBag, color: 'text-amber-600 bg-amber-50', change: '+23%', trend: 'up' },
    { label: 'Doanh Thu', value: '₫2.4M', icon: TrendingUp, color: 'text-purple-600 bg-purple-50', change: '+15%', trend: 'up' },
  ];

  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
      {stats.map((stat, index) => (
        <div key={index} className="bg-white border border-slate-200 rounded-xl p-4 hover:shadow-md transition-shadow">
          <div className="flex items-center justify-between">
            <div className={`p-2 rounded-lg ${stat.color}`}>
              <stat.icon className="w-5 h-5" />
            </div>
            <span className={`text-xs font-semibold ${stat.trend === 'up' ? 'text-emerald-600' : 'text-rose-600'}`}>
              {stat.change}
            </span>
          </div>
          <div className="mt-3">
            <p className="text-2xl font-bold text-slate-900">{stat.value}</p>
            <p className="text-xs text-slate-500 mt-0.5">{stat.label}</p>
          </div>
        </div>
      ))}
    </div>
  );
};