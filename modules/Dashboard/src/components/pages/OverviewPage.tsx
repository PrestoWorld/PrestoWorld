import React from 'react';
import type { DashboardWidget } from '../../types';
import { StatCard } from '../StatCard';
import { QuickDraft } from '../QuickDraft';
import { ActivityLog } from '../ActivityLog';
import { EventsNews } from '../EventsNews';

interface OverviewPageProps {
  widgets: DashboardWidget[];
}

const componentMap: Record<string, React.FC<{ content: string }>> = {
  StatCards: StatCard,
  QuickDraft: QuickDraft,
  ActivityLog: ActivityLog,
  EventsNews: EventsNews,
};

export const OverviewPage: React.FC<OverviewPageProps> = ({ widgets }) => {
  const visibleWidgets = widgets.filter((w) => w.visible).sort((a, b) => a.priority - b.priority);

  return (
    <div className="space-y-4 animate-in fade-in duration-200 max-w-7xl mx-auto">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl sm:text-2xl font-bold text-slate-900">Bảng Điều Khiển</h1>
          <p className="text-sm text-slate-500 mt-1">Tổng quan trạng thái hệ thống và hoạt động gần đây</p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {visibleWidgets.map((widget) => {
          const Component = componentMap[widget.component];
          return (
            <div
              key={widget.id}
              className={`bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 ${widget.grid === 'full' ? 'lg:col-span-2' : ''}`}
            >
              <div className="flex items-center justify-between mb-4">
                <h2 className="text-base font-semibold text-slate-900">{widget.title}</h2>
              </div>
              {Component ? <Component content={widget.props.content} /> : <div className="text-slate-500 text-sm">Component not found: {widget.component}</div>}
            </div>
          );
        })}
      </div>

      {visibleWidgets.length === 0 && (
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
          <p className="text-slate-500">Chưa có widget nào. Hãy thêm widget từ Cài đặt màn hình.</p>
        </div>
      )}
    </div>
  );
};