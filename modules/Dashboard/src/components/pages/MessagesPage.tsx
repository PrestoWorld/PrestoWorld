import React from 'react';

export const MessagesPage: React.FC = () => {
  const initialConversations = [
    {
      id: 'conv-1',
      customerName: 'Nguyễn Văn A',
      customerAvatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=100',
      lastMessage: 'Chào shop, đang muốn hỏi về cuốn Sach Ngu Ngau...',
      time: 'Vừa xong',
      unreadCount: 3,
      source: 'Website',
    },
    {
      id: 'conv-2',
      customerName: 'Trần Thị B',
      customerAvatar: 'https://images.unsplash.com/photo-1544005313-94ddf08d00e2?auto=format&fit=crop&q=80&w=100',
      lastMessage: 'Đơn hàng của tôi vẫn chưa được giao, khi được giao?',
      time: '2 tiếng trước',
      unreadCount: 0,
      source: 'Facebook',
    },
    {
      id: 'conv-3',
      customerName: 'Lê Văn C',
      customerAvatar: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&q=80&w=100',
      lastMessage: 'Mình muốn hủy đơn hàng ord-882910',
      time: '5 tiếng trước',
      unreadCount: 2,
      source: 'Website',
    },
  ];

  const unreadMessages = initialConversations.filter((c) => c.unreadCount > 0).reduce((acc, c) => acc + c.unreadCount, 0);

  return (
    <div className="space-y-4 animate-in fade-in duration-200 max-w-7xl mx-auto">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl sm:text-2xl font-bold text-slate-900">Tin Nhắn & Hỗ trợ</h1>
          <p className="text-sm text-slate-500 mt-1">Hộp thư đa kênh SNS - Quản lý tin khách hàng</p>
        </div>
        <div className="flex items-center gap-2">
          <span className="text-xs font-medium text-slate-600 bg-blue-100 text-blue-800 px-2 py-0.5 rounded">
            {unreadMessages} mới
          </span>
        </div>
      </div>

      {/* Filters */}
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 mb-4">
        <div>
          <select
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30"
          >
            <option value="all">Tất cả kênh</option>
            <option value="website">Website</option>
            <option value="facebook">Facebook</option>
            <option value="zalo">Zalo</option>
            <option value="shopee">Shopee</option>
          </select>
        </div>

        <div>
          <select
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30"
          >
            <option value="all">Tất cả trạng thái</option>
            <option value="unread">Chưa đọc</option>
            <option value="read">Đã đọc</option>
            <option value="urgent">Khẩn cấp</option>
          </select>
        </div>

        <div className="sm:col-span-2">
          <select
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30"
          >
            <option value="all">Tất cả chủ shop</option>
          </select>
        </div>
      </div>

      {/* Messages list */}
      <div className="overflow-x-auto bg-white rounded-2xl border border-slate-200 shadow-sm">
        <table className="min-w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 bg-slate-50">
              <th className="p-3 text-left text-xs font-medium text-slate-600">Kênh</th>
              <th className="p-3 text-left text-xs font-medium text-slate-600">Khách hàng</th>
              <th className="p-3 text-left text-xs font-medium text-slate-600">Tin nhắn mới</th>
              <th className="p-3 text-left text-xs font-medium text-slate-600">Thời gian</th>
            </tr>
          </thead>
          <tbody>
            {initialConversations.map((conv) => (
              <tr key={conv.id} className="border-b border-slate-200 hover:bg-slate-50">
                <td className="p-3">
                  <span
                    className={`px-2 py-0.5 rounded text-xs font-medium ${
                      conv.source === 'Website' ? 'bg-blue-100 text-blue-800' :
                      conv.source === 'Facebook' ? 'bg-amber-100 text-amber-800' :
                      conv.source === 'Zalo' ? 'bg-emerald-100 text-emerald-800' :
                      conv.source === 'Shopee' ? 'bg-purple-100 text-purple-800' :
                      'bg-slate-100 text-slate-600'
                    }`}
                  >
                    {conv.source}
                  </span>
                </td>
                <td className="p-3 font-medium">
                  <div>
                    <img
                      src={conv.customerAvatar}
                      alt={conv.customerName}
                      className="w-5 h-5 rounded-full object-cover mr-2"
                    />
                    {conv.customerName}
                  </div>
                </td>
                <td className="p-3">
                  <span
                    className={`px-2 py-0.5 rounded text-xs font-medium ${
                      conv.unreadCount > 0 ? 'bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-500'
                    }`}
                  >
                    {conv.unreadCount}
                  </span>
                </td>
                <td className="p-3 text-sm text-slate-500">{conv.time}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* No messages state */}
      {!initialConversations.length && (
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
          <p className="text-slate-500 text-sm">Chưa có tin nhắn. Tất cả kênh đang yên tĩnh.</p>
        </div>
      )}
    </div>
  );
};