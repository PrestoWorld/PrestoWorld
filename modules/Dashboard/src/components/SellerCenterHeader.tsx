import React from 'react';
import { ArrowLeft, Search, MessageSquare, Globe } from 'lucide-react';
import type { User } from '../types';

interface SellerCenterHeaderProps {
  onBackToMain: () => void;
  activeTab: string;
  onTabChange: (tab: string) => void;
  user: User;
  pageTitle: string;
}

export const SellerCenterHeader: React.FC<SellerCenterHeaderProps> = ({
  onBackToMain,
  activeTab,
  onTabChange,
  user,
}) => {
  const unreadMessagesCount = 3; // Mock data - would come from API

  return (
    <header className="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-200 px-3 sm:px-4 py-2 sm:py-2.5 flex items-center justify-between shadow-xs gap-2 sm:gap-3 overflow-hidden">
      <div className="flex items-center space-x-2 sm:space-x-3 flex-shrink-0">
        <button
          onClick={onBackToMain}
          className="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all cursor-pointer shadow-2xs group"
          title="Quay về trang chính"
        >
          <ArrowLeft className="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" />
          <span className="hidden sm:inline">Về Trang Chủ</span>
        </button>

        <div className="h-5 w-px bg-slate-200 hidden sm:block" />

        <div className="flex items-center space-x-2">
          <div className="w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center font-black text-sm shadow-xs flex-shrink-0">
            P
          </div>
          <div>
            <div className="flex items-center gap-1.5">
              <span className="text-sm sm:text-base font-bold text-slate-900 tracking-tight leading-none">
                PrestoWorld
              </span>
              <span className="bg-blue-50 text-blue-700 text-[10px] font-extrabold px-1.5 py-0.5 rounded-full border border-blue-200/60 whitespace-nowrap">
                Dashboard
              </span>
            </div>
            <p className="text-[10px] text-slate-500 font-medium tracking-wide uppercase mt-0.5 hidden lg:block">
              Dashboard Quản Trị • CMS • Analytics
            </p>
          </div>
        </div>
      </div>

      <div className="flex-1 max-w-md mx-2 hidden md:block">
        <div className="relative">
          <Search className="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
          <input
            type="text"
            placeholder="Tìm kiếm nội dung, bài viết, người dùng..."
            className="w-full pl-9 pr-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50/80 hover:bg-slate-50 focus:bg-white text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-blue-500/30 transition-all"
          />
        </div>
      </div>

      <div className="flex items-center space-x-1.5 sm:space-x-2 flex-shrink-0">
        <button
          onClick={() => onTabChange('messages')}
          className={`px-2.5 sm:px-3 py-1.5 rounded-xl border text-xs font-bold flex items-center gap-1.5 shadow-2xs transition-all cursor-pointer relative ${
            activeTab === 'messages'
              ? 'bg-blue-600 text-white border-blue-600'
              : 'bg-blue-50 hover:bg-blue-100 text-blue-700 border-blue-200/60'
          }`}
          title="Tin nhắn & hỗ trợ"
        >
          <MessageSquare className="w-4 h-4" />
          <span className="hidden md:inline">Tin Nhắn</span>
          {unreadMessagesCount > 0 && (
            <span className="bg-rose-500 text-white text-[9px] font-bold px-1.5 py-0.2 rounded-full animate-pulse border border-white">
              {unreadMessagesCount}
            </span>
          )}
        </button>

        <button
          className="hidden sm:flex px-2.5 sm:px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/60 rounded-xl text-xs font-bold items-center gap-1.5 shadow-2xs transition-all cursor-pointer"
          title="Xem trang web"
        >
          <Globe className="w-4 h-4 text-emerald-600" />
          <span className="hidden lg:inline">Xem Trang Web</span>
        </button>

        <div className="flex items-center space-x-2 border-l border-slate-200 pl-2 pr-1 py-1 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors">
          <div className="relative">
            <div className="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm border border-blue-500/40 ring-2 ring-blue-500/10">
              {user.name?.charAt(0).toUpperCase() || 'A'}
            </div>
            <span className="absolute bottom-0 right-0 w-2 h-2 rounded-full bg-emerald-500 border border-white" />
          </div>
          <div className="hidden xl:block text-left">
            <p className="text-xs font-bold text-slate-900 leading-tight">{user.name || 'Administrator'}</p>
            <p className="text-[10px] text-emerald-600 font-semibold flex items-center gap-1">
              <span>● Đang hoạt động</span>
            </p>
          </div>
        </div>
      </div>
    </header>
  );
};