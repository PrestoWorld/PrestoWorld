import React from 'react';
import { Clock, MessageSquare, CheckCircle2, Edit, FileText } from 'lucide-react';

interface ActivityLogProps {
  content: string;
}

const activities = [
  { id: 1, user: 'Admin', action: 'đã tạo bài viết mới', target: '"Hướng dẫn sử dụng PrestoWorld"', time: '5 phút trước', type: 'create' },
  { id: 2, user: 'Editor', action: 'đã cập nhật', target: '"Cài đặt SEO"', time: '15 phút trước', type: 'update' },
  { id: 3, user: 'Admin', action: 'đã phê duyệt bình luận', target: 'trên bài "Tính năng mới"', time: '1 giờ trước', type: 'approve' },
  { id: 4, user: 'System', action: 'đã sao lưu cơ sở dữ liệu', target: '', time: '3 giờ trước', type: 'system' },
  { id: 5, user: 'Editor', action: 'đã tải lên hình ảnh', target: 'banner-trang-chu.jpg', time: '5 giờ trước', type: 'upload' },
];

export const ActivityLog: React.FC<ActivityLogProps> = () => {
  return (
    <div className="space-y-3">
      {activities.map((activity) => (
        <div key={activity.id} className="flex items-start gap-3 p-3 bg-slate-50/50 rounded-xl hover:bg-slate-50 transition-colors">
          <div className={`p-1.5 rounded-lg ${activity.type === 'create' ? 'bg-blue-100 text-blue-600' : activity.type === 'update' ? 'bg-amber-100 text-amber-600' : activity.type === 'approve' ? 'bg-emerald-100 text-emerald-600' : activity.type === 'upload' ? 'bg-purple-100 text-purple-600' : 'bg-slate-100 text-slate-600'}`}>
            {activity.type === 'create' && <CheckCircle2 className="w-4 h-4" />}
            {activity.type === 'update' && <Edit className="w-4 h-4" />}
            {activity.type === 'approve' && <MessageSquare className="w-4 h-4" />}
            {activity.type === 'upload' && <FileText className="w-4 h-4" />}
            {activity.type === 'system' && <Clock className="w-4 h-4" />}
          </div>
          <div className="flex-1 min-w-0">
            <p className="text-sm text-slate-900">
              <span className="font-semibold">{activity.user}</span> {' '}
              {activity.action} {' '}
              <span className="font-medium text-blue-600">{activity.target}</span>
            </p>
            <p className="text-xs text-slate-500 mt-0.5">{activity.time}</p>
          </div>
        </div>
      ))}
      <div className="text-center pt-2">
        <button className="text-sm text-blue-600 hover:text-blue-700 font-medium">
          Xem tất cả hoạt động →
        </button>
      </div>
    </div>
  );
};