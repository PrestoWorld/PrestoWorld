import React from 'react';
import {
  ArrowLeft,
  BarChart2,
  Layers,
  Sparkles,
  X,
  Circle,
  FileText,
  Image,
  File,
  MessageSquare,
  Palette,
  Puzzle,
  User,
  Wrench,
  Settings,
  LayoutDashboard,
  Activity,
  Download,
  Upload,
  Link,
  Shield,
  Edit,
  BookOpen,
  Tags,
  Plus,
  Code,
  Menu,
  Blocks,
  Globe,
} from 'lucide-react';
import type { MenuSection } from '../types';

interface NavItem {
  id: string;
  label: string;
  subLabel: string;
  icon: React.ComponentType<{ className?: string }>;
}

interface CoreItem {
  id: string;
  label: string;
  icon?: string;
  screenId?: string;
  url?: string;
}

interface SellerCenterSidebarProps {
  activeTab: string;
  onTabChange: (tab: string) => void;
  navItems: NavItem[];
  isTabActive: (tabId: string) => boolean;
  coreSections?: MenuSection[];
  onSelectScreen: (screenId: string) => void;
  showMobileMoreMenu: boolean;
  setShowMobileMoreMenu: (show: boolean) => void;
  onBackToMain: () => void;
  mobileMoreClick: (tabId: string) => void;
}

const coreIcons: Record<string, React.ComponentType<{ className?: string }>> = {
  FileText,
  Image,
  File,
  MessageSquare,
  Palette,
  Puzzle,
  User,
  Wrench,
  Settings,
  LayoutDashboard,
  Activity,
  Download,
  Upload,
  Link,
  Shield,
  Edit,
  BookOpen,
  Tags,
  Plus,
  Code,
  Menu,
  Blocks,
  Globe,
  Circle,
};

const resolveIcon = (name?: string): React.ComponentType<{ className?: string }> =>
  (name && coreIcons[name]) || Circle;

export const SellerCenterSidebar: React.FC<SellerCenterSidebarProps> = ({
  onTabChange,
  navItems,
  isTabActive,
  coreSections = [],
  onSelectScreen,
  showMobileMoreMenu,
  setShowMobileMoreMenu,
  onBackToMain,
  mobileMoreClick,
}) => {
  return (
    <>
      <div className="md:hidden bg-white border-b border-slate-200 px-2 py-1.5 shadow-2xs flex-shrink-0 z-20">
        <div className="grid grid-cols-5 gap-1">
          {navItems.slice(0, 5).map((item) => (
            <button
              key={item.id}
              onClick={() => onTabChange(item.id)}
              className={`flex flex-col items-center justify-center py-1.5 px-1 rounded-xl text-[10.5px] font-bold transition-all cursor-pointer ${
                isTabActive(item.id)
                  ? 'bg-blue-600 text-white shadow-xs'
                  : 'text-slate-600 hover:bg-slate-100'
              }`}
            >
              <item.icon className="w-4 h-4 mb-0.5" />
              <span className="truncate max-w-full">{item.label}</span>
            </button>
          ))}
          <button
            onClick={() => {
              if (isTabActive('analytics')) {
                setShowMobileMoreMenu(true);
              } else {
                onTabChange('analytics');
              }
            }}
            className={`flex flex-col items-center justify-center py-1.5 px-1 rounded-xl text-[10.5px] font-bold transition-all cursor-pointer ${
              isTabActive('analytics') || isTabActive('marketing')
                ? 'bg-blue-600 text-white shadow-xs'
                : 'text-slate-600 hover:bg-slate-100'
            }`}
          >
            <BarChart2 className="w-4 h-4 mb-0.5" />
            <span className="truncate max-w-full flex items-center gap-0.5">
              {isTabActive('marketing') ? 'Kênh Bán' : 'Phân Tích'}
            </span>
          </button>
        </div>
      </div>

      {showMobileMoreMenu && (
        <div className="md:hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex flex-col justify-end animate-in fade-in">
          <div className="bg-white rounded-t-3xl max-h-[85vh] overflow-y-auto p-4 sm:p-5 shadow-2xl space-y-4 animate-in slide-in-from-bottom duration-200">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <div className="flex items-center gap-2">
                <div className="p-2 rounded-xl bg-blue-50 text-blue-600">
                  <Layers className="w-4 h-4" />
                </div>
                <div>
                  <h3 className="font-black text-sm text-slate-900">Danh Mục Phân Hệ</h3>
                  <p className="text-[10px] text-slate-400">Tối ưu gọn gàng - Không bị ẩn chức năng</p>
                </div>
              </div>
              <button
                onClick={() => setShowMobileMoreMenu(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-full cursor-pointer"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="grid grid-cols-1 gap-2">
              {navItems.map((item) => {
                const isCurrent = isTabActive(item.id);
                return (
                  <button
                    key={item.id}
                    onClick={() => mobileMoreClick(item.id)}
                    className={`flex items-center gap-3 p-3 rounded-2xl border text-left text-xs font-bold transition-all cursor-pointer ${
                      isCurrent
                        ? 'bg-blue-50 border-blue-300 text-blue-700 shadow-2xs ring-1 ring-blue-500/20'
                        : 'bg-slate-50 hover:bg-slate-100 border-slate-200/80 text-slate-700'
                    }`}
                  >
                    <div
                      className={`p-2 rounded-xl ${
                        isCurrent ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 shadow-2xs'
                      }`}
                    >
                      <item.icon className="w-4 h-4" />
                    </div>
                    <div className="min-w-0 flex-1">
                      <div className="flex items-center justify-between">
                        <p className="truncate text-xs font-extrabold">{item.label}</p>
                      </div>
                      <p className="text-[10px] text-slate-500 font-normal truncate mt-0.5">
                        {item.subLabel}
                      </p>
                    </div>
                  </button>
                );
              })}
            </div>

            <div className="pt-2 border-t border-slate-100">
              <button
                onClick={() => {
                  setShowMobileMoreMenu(false);
                  onBackToMain();
                }}
                className="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl flex items-center justify-center gap-1.5 cursor-pointer text-xs"
              >
                <ArrowLeft className="w-3.5 h-3.5" /> Chuyển sang Trang Chủ
              </button>
            </div>
          </div>
        </div>
      )}

      <aside className="hidden md:flex md:w-60 flex-shrink-0 border-r border-slate-200/90 bg-white flex-col h-[calc(100vh-53px)] shadow-2xs">
        <div className="px-3.5 pt-3 pb-1.5 flex items-center justify-between text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
          <span>Danh Mục Phân Hệ</span>
          <span className="text-[9px] bg-blue-50 text-blue-700 px-1.5 py-0.2 rounded-full font-extrabold border border-blue-200/60">
            {navItems.length} Phân Hệ
          </span>
        </div>

        <nav className="flex-1 px-2.5 py-1 space-y-1 overflow-y-auto no-scrollbar">
          {navItems.map((item) => {
            const isActive = isTabActive(item.id);
            return (
              <button
                key={item.id}
                onClick={() => onTabChange(item.id)}
                className={`w-full flex items-center space-x-2.5 px-3 py-2.5 rounded-xl text-left transition-all cursor-pointer ${
                  isActive
                    ? 'bg-blue-600 text-white shadow-xs ring-1 ring-blue-700/20'
                    : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100/90'
                }`}
              >
                <item.icon className={`w-4 h-4 flex-shrink-0 ${isActive ? 'text-white' : 'text-slate-500'}`} />
                <div className="min-w-0 flex-1">
                  <p className="text-xs font-bold truncate leading-tight">{item.label}</p>
                  <p className="text-[9.5px] truncate mt-0.5 leading-tight">{item.subLabel}</p>
                </div>
              </button>
            );
          })}

          {coreSections.length > 0 && (
            <div className="pt-3 mt-2 border-t border-slate-200/80">
              <div className="px-1.5 pb-1.5 flex items-center justify-between text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                <span>Quản Lý Nội Dung</span>
                <span className="text-[9px] bg-slate-100 text-slate-600 px-1.5 py-0.2 rounded-full font-extrabold border border-slate-200/60">
                  {coreSections.length} Nhóm
                </span>
              </div>

              {coreSections.map((section) => {
                const Icon = resolveIcon(section.icon);
                return (
                  <div key={section.id} className="mb-1.5">
                    <div className="flex items-center gap-2 px-3 py-1.5 text-[10.5px] font-extrabold text-slate-500">
                      <Icon className="w-3.5 h-3.5 text-slate-400" />
                      <span className="truncate">{section.title}</span>
                    </div>

                    <div className="space-y-0.5">
                      {(section.items ?? []).map((item: CoreItem) => {
                        const ItemIcon = resolveIcon(item.icon ?? section.icon);
                        const itemId = item.screenId ?? item.id;
                        const isActive = itemId !== '' && isTabActive(itemId);
                        return (
                          <button
                            key={item.id}
                            onClick={() => onSelectScreen(itemId)}
                            className={`w-full flex items-center space-x-2.5 px-3 py-1.5 rounded-lg text-left transition-all cursor-pointer ${
                              isActive
                                ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-200/70'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/90'
                            }`}
                          >
                            <ItemIcon className={`w-3.5 h-3.5 flex-shrink-0 ${isActive ? 'text-blue-600' : 'text-slate-400'}`} />
                            <span className="text-xs font-medium truncate">{item.label}</span>
                          </button>
                        );
                      })}
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </nav>

        <div className="p-2.5 border-t border-slate-100 space-y-1.5 bg-slate-50/50 flex-shrink-0">
          <div className="p-2 rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100/80 flex items-center justify-between shadow-2xs">
            <div className="flex items-center space-x-2">
              <div className="w-6 h-6 rounded-lg bg-blue-600 text-white flex items-center justify-center shadow-xs">
                <Sparkles className="w-3.5 h-3.5" />
              </div>
              <div>
                <p className="text-[11px] font-extrabold text-slate-900 leading-tight">PrestoWorld Pro</p>
                <p className="text-[9.5px] text-slate-500">Nâng cấp để mở khóa tính năng</p>
              </div>
            </div>
            <button className="text-[10px] font-bold text-blue-700 hover:underline cursor-pointer">
              Nâng Cấp →
            </button>
          </div>

          <button
            onClick={onBackToMain}
            className="w-full py-1.5 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 transition-colors flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs"
          >
            <ArrowLeft className="w-3.5 h-3.5" /> Về Trang Chủ
          </button>
        </div>
      </aside>
    </>
  );
};