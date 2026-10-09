import React from 'react';

export const OrdersPage: React.FC = () => {
  const INITIAL_ORDERS = [
    {
      id: 'ORD-882910',
      customerName: 'Nguyễn Văn A',
      customerPhone: '0988 123 456',
      customerEmail: 'nguyenvana@example.com',
      bookTitle: 'Đắc Nhân Tâm - Phần 1',
      price: 89000,
      quantity: 2,
      status: 'Đang giao hàng',
      date: '07/08/2026',
      shippingAddress: 'Số 123 Đường Sách, Quận 1, TP.HCM',
      paymentMethod: 'COD',
      source: 'Website',
    },
    {
      id: 'ORD-882911',
      customerName: 'Trần Thị B',
      customerPhone: '0977 456 789',
      customerEmail: 'tranthib@example.com',
      bookTitle: 'Nhà Giả Kim',
      price: 78000,
      quantity: 1,
      status: 'Đang chờ xác nhận',
      date: '06/08/2026',
      shippingAddress: 'Số 45 Hoàng Hoa Thám, Quận Hải Châu, TP.DN',
      paymentMethod: 'Chuyển Khoản',
      source: 'BookPress',
    },
    {
      id: 'ORD-882912',
      customerName: 'Lê Văn C',
      customerPhone: '0966 789 012',
      customerEmail: 'levanc@example.com',
      bookTitle: 'Tư duy nhanh và chậm',
      price: 180000,
      quantity: 3,
      status: 'Giữ đơn (3 ngày)',
      date: '05/08/2026',
      shippingAddress: 'Số 78 Nguyễn Trãi, Long Biên, HN',
      paymentMethod: 'COD',
      source: 'Website',
    },
  ];

  return (
    <div className="space-y-4 animate-in fade-in duration-200 max-w-7xl mx-auto">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl sm:text-2xl font-bold text-slate-900">Quản Lý Đơn Hàng</h1>
          <p className="text-sm text-slate-500 mt-1">Xem và chốt đơn từ các kênh bán hàng</p>
        </div>
        <div className="flex items-center gap-2">
          <button className="px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            <svg className="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M12 5v14M5 12h14" />
            </svg>
            Thêm đơn hàng
          </button>
        </div>
      </div>

      <div className="overflow-x-auto bg-white rounded-2xl border border-slate-200 shadow-sm">
        <table className="min-w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 bg-slate-50">
              <th className="p-3 text-left text-xs font-medium text-slate-600"># Đơn</th>
              <th className="p-3 text-left text-xs font-medium text-slate-600">Khách hàng</th>
              <th className="p-3 text-left text-xs font-medium text-slate-600">Sách</th>
              <th className="p-3 text-left text-xs font-medium text-slate-600">Tổng</th>
              <th className="p-3 text-left text-xs font-medium text-slate-600">Trạng thái</th>
              <th className="p-3 text-left text-xs font-medium text-slate-600">Ngày đặt</th>
              <th className="p-3 text-left text-xs font-medium text-slate-600">Thanh toán</th>
            </tr>
          </thead>
          <tbody>
            {INITIAL_ORDERS.map((order) => (
              <tr key={order.id} className="border-b border-slate-200 hover:bg-slate-50">
                <td className="p-3 font-medium">{order.id}</td>
                <td className="p-3">
                  <div className="font-medium">{order.customerName}</div>
                  <div className="text-xs text-slate-500">{order.customerPhone}</div>
                </td>
                <td className="p-3">
                  <div className="font-medium truncate">{order.bookTitle}</div>
                  <div className="text-xs text-slate-500 truncate">{order.quantity} cuốn</div>
                </td>
                <td className="p-3 font-medium">{order.price.toLocaleString('vi-VN')}đ</td>
                <td className="p-3">
                  <span
                    className={`px-2 py-0.5 rounded text-xs font-medium ${
                      order.status.includes('Giữ') ? 'bg-amber-100 text-amber-600' :
                      order.status.includes('Chờ') ? 'bg-amber-100 text-amber-600' :
                      order.status.includes('Đang giao') ? 'bg-blue-100 text-blue-600' :
                      order.status.includes('Đã') ? 'bg-green-100 text-green-600' :
                      'bg-slate-100 text-slate-600'
                    }`}
                  >
                    {order.status}
                  </span>
                </td>
                <td className="p-3 text-sm text-slate-500">{order.date}</td>
                <td className="p-3 text-sm text-slate-500">{order.paymentMethod}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};