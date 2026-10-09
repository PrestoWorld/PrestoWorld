import React from 'react';
import { Newspaper, AlertTriangle, CheckCircle2, Clock } from 'lucide-react';

interface EventsNewsProps {
  content: string;
}

const newsItems = [
  { id: 1, title: 'PrestoWorld v2.0 phát hành', description: 'Phiên bản mới với hiệu suất cải thiện 40% và UI hiện đại hơn.', date: 'Hôm nay', type: 'release', read: false },
  { id: 2, title: 'Bảo trì hệ thống vào 02:00 AM', description: 'Hệ thống sẽ tạm ngừng trong 30 phút để bảo trì định kỳ.', date: 'Ngày mai', type: 'maintenance', read: false },
  { id: 3, title: 'Hướng dẫn tích hợp API v3', description: 'Tài liệu mới cho nhà phát triển đã được cập nhật.', date: '2 ngày trước', type: 'docs', read: true },
  { id: 4, title: 'Cập nhật bảo mật quan trọng', description: 'Vui lòng cập nhật lên phiên bản mới nhất sớm nhất có thể.', date: '1 tuần trước', type: 'security', read: true },
];

export const EventsNews: React.FC<EventsNewsProps> = () => {
  return (
    <div className="space-y-3">
      {newsItems.map((item) => (
        <div key={item.id} className={`flex items-start gap-3 p-3 rounded-xl border ${!item.read ? 'bg-blue-50 border-blue-100' : 'bg-white border-slate-200'} transition-colors`}>
          <div className={`flex-shrink-0 p-2 rounded-lg ${item.type === 'release' ? 'bg-blue-100 text-blue-600' : item.type === 'maintenance' ? 'bg-amber-100 text-amber-600' : item.type === 'security' ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600'}`}>
            {item.type === 'release' && <Newspaper className="w-4 h-4" />}
            {item.type === 'maintenance' && <Clock className="w-4 h-4" />}
            {item.type === 'security' && <AlertTriangle className="w-4 h-4" />}
            {item.type === 'docs' && <CheckCircle2 className="w-4 h-4" />}
          </div>
          <div className="flex-1 min-w-0">
            <div className="flex items-center justify-between gap-2">
              <h3 className="text-sm font-semibold text-slate-900 truncate">{item.title}</h3>
              <span className="text-xs text-slate-500 whitespace-nowrap">{item.date}</span>
            </div>
            <p className="text-xs text-slate-600 mt-1 line-clamp-2">{item.description}</p>
          </div>
        </div>
      ))}
      <div className="text-center pt-2">
        <button className="text-sm text-blue-600 hover:text-blue-700 font-medium">
          Xem tất cả tin tức →
        </button>
      </div>
    </div>
  );
};