import React, { useMemo, useState } from 'react';
import { Boxes, Book, DollarSign } from 'lucide-react';
import type { DashboardWidget } from '../../types';

interface InventoryPageProps {
  widgets: DashboardWidget[];
}

export const InventoryPage: React.FC<InventoryPageProps> = ({ widgets }) => {
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [categoryFilter, setCategoryFilter] = useState('all');

  const categories = ['Văn học', 'Kinh tế', 'Self-help', 'Thiếu nhi', 'Tâm lý', 'Lịch sử'];

  const books = [
    {
      id: 'INV-001',
      sku: 'SKU-001',
      title: 'Đắc Nhân Tâm',
      author: 'Dale Carnegie',
      price: 89000,
      costPrice: 65000,
      stock: 45,
      location: 'Kệ A1 - Lô 1',
      category: 'Văn học',
      status: 'In Stock',
      tags: ['Bestseller'],
    },
    {
      id: 'INV-002',
      sku: 'SKU-002',
      title: 'Nhà Giả Kim',
      author: 'Paulo Coelho',
      price: 78000,
      costPrice: 55000,
      stock: 12,
      location: 'Kệ B2 - Lô 3',
      category: 'Self-help',
      status: 'Low Stock',
      tags: ['Classic'],
    },
    {
      id: 'INV-003',
      sku: 'SKU-003',
      title: 'Cây Cam Ngọt Của Tôi',
      author: 'José Mauro de Vasconcelos',
      price: 108000,
      costPrice: 82000,
      stock: 89,
      location: 'Kệ C1 - Lô 2',
      category: 'Văn học',
      status: 'In Stock',
      tags: ['Fiction'],
    },
  ];

  const filteredBooks = useMemo(() => {
    let list = [...books];
    const q = searchQuery.toLowerCase().trim();
    if (q) {
      list = list.filter(
        (item) =>
          item.title.toLowerCase().includes(q) ||
          item.author.toLowerCase().includes(q) ||
          item.sku.toLowerCase().includes(q) ||
          item.tags.some((t) => t.toLowerCase().includes(q))
      );
    }
    if (statusFilter !== 'all') {
      list = list.filter((item) => item.status === statusFilter);
    }
    if (categoryFilter !== 'all') {
      list = list.filter((item) => item.category === categoryFilter);
    }
    return list;
  }, [searchQuery, statusFilter, categoryFilter]);

  const totalStock = filteredBooks.reduce((acc, item) => acc + item.stock, 0);

  return (
    <div className="space-y-4 animate-in fade-in duration-200 max-w-7xl mx-auto">
      <div>
        <h1 className="text-xl sm:text-2xl font-bold text-slate-900">Kho Hàng & Sách</h1>
        <p className="text-sm text-slate-500 mt-1">Quản lý tồn kho và danh sách sách</p>
      </div>

      {/* Filters */}
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4 mb-4">
        <div>
          <div className="text-sm text-slate-600 mb-1">Tìm kiếm</div>
          <input
            type="text"
            placeholder="Tìm theo tên, SKU, tác giả..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30"
          />
        </div>

        <div>
          <div className="text-sm text-slate-600 mb-1">Lọc theo trạng thái</div>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30"
          >
            <option value="all">Tất cả</option>
            <option value="In Stock">Còn hàng</option>
            <option value="Low Stock">Hết kho (cảnh báo)</option>
            <option value="Out of Stock">Hết hàng</option>
          </select>
        </div>

        <div>
          <div className="text-sm text-slate-600 mb-1">Lọc theo thể loại</div>
          <select
            value={categoryFilter}
            onChange={(e) => setCategoryFilter(e.target.value)}
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30"
          >
            <option value="all">Tất cả</option>
            {categories.map((cat) => (
              <option key={cat} value={cat}>{cat}</option>
            ))}
          </select>
        </div>
      </div>

      {/* Summary cards */}
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3 mb-4">
        <div className="bg-white border border-slate-200 rounded-xl p-4 sm:p-5 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-xs font-medium text-slate-500">Tổng số cuốn</p>
              <p className="text-2xl font-bold text-slate-900">{totalStock}</p>
            </div>
            <Boxes className="w-6 h-6 text-blue-600" />
          </div>
        </div>
        <div className="bg-white border border-slate-200 rounded-xl p-4 sm:p-5 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-xs font-medium text-slate-500">Sách đang bán</p>
              <p className="text-2xl font-bold text-slate-900">{filteredBooks.length}</p>
            </div>
            <Book className="w-6 h-6 text-emerald-600" />
          </div>
        </div>
        <div className="bg-white border border-slate-200 rounded-xl p-4 sm:p-5 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-xs font-medium text-slate-500">Sách khuyến mãi</p>
              <p className="text-2xl font-bold text-amber-600">{filteredBooks.filter((b) => b.tags.includes('Bestseller')).length}</p>
            </div>
            <DollarSign className="w-6 h-6 text-amber-600" />
          </div>
        </div>
      </div>

      {/* Inventory table */}
      <div className="overflow-x-auto bg-white rounded-2xl border border-slate-200 shadow-sm">
        <table className="min-w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 bg-slate-50">
              <th className="p-3 text-left text-xs font-medium text-slate-600">Sách</th>
              <th className="p-3 text-left text-xs font-medium text-slate-600">SKU</th>
              <th className="p-3 text-left text-xs font-medium text-slate-600">Thể loại</th>
              <th className="p-3 text-left text-xs font-medium text-slate-600">Giá bán</th>
              <th className="p-3 text-left text-xs font-medium text-slate-600">Xuất xứ giá</th>
              <th className="p-3 text-left text-xs font-medium text-slate-600">Tồn kho</th>
              <th className="p-3 text-left text-xs font-medium text-slate-600">Vị trí</th>
              <th className="p-3 text-left text-xs font-medium text-slate-600">Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            {filteredBooks.map((book) => (
              <tr key={book.id} className="border-b border-slate-200 hover:bg-slate-50">
                <td className="p-3">
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded bg-slate-100 flex items-center justify-center font-bold text-sm">
                      {book.title.charAt(0)}
                    </div>
                    <div>
                      <p className="font-medium text-slate-900 line-clamp-1">{book.title}</p>
                      <p className="text-xs text-slate-500">{book.author}</p>
                    </div>
                  </div>
                </td>
                <td className="p-3 text-sm text-slate-500">{book.sku}</td>
                <td className="p-3 text-sm text-slate-500">{book.category}</td>
                <td className="p-3 font-medium">{book.price.toLocaleString('vi-VN')}đ</td>
                <td className="p-3 text-sm text-slate-500">{book.costPrice.toLocaleString('vi-VN')}đ</td>
                <td className="p-3 font-medium">
                  <span className={`px-2 py-0.5 rounded text-xs font-medium ${
                    book.status === 'In Stock' ? 'bg-emerald-100 text-emerald-600' :
                    book.status === 'Low Stock' ? 'bg-amber-100 text-amber-600' :
                    book.status === 'Out of Stock' ? 'bg-rose-100 text-rose-600' :
                    'bg-slate-100 text-slate-600'
                  }`}>
                    {book.status}
                  </span>
                </td>
                <td className="p-3 text-sm text-slate-500">{book.location}</td>
                <td className="p-3">
                  <span
                    className={`px-2 py-0.5 rounded text-xs font-medium ${
                      book.status === 'In Stock' ? 'bg-emerald-100 text-emerald-600' :
                      book.status === 'Low Stock' ? 'bg-amber-100 text-amber-600' :
                      book.status === 'Out of Stock' ? 'bg-rose-100 text-rose-600' :
                      'bg-slate-100 text-slate-600'
                    }`}
                  >
                    {book.status}
                  </span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};