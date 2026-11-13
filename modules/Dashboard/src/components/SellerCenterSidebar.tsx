import React, { useEffect, useState, useRef } from 'react';
import {
  ArrowLeft,
  ChevronLeft,
  ChevronRight,
  Home,
  Menu,
  X,
  Layers,
} from 'lucide-react';
import type { NavGroup, NavItem } from '../types';
import { resolveSubsystemIcon } from '../nav/subsystemIcons';

interface SellerCenterSidebarProps {
  activeScreenId: string;
  onSelectScreen: (screenId: string) => void;
  groups: NavGroup[];
  showMobileMoreMenu: boolean;
  setShowMobileMoreMenu: (show: boolean) => void;
  onBackToMain: () => void;
}

/** Resolve the icon for a group: prefer pre-resolved iconComponent, fall back to iconName string lookup */
const resolveGroupIcon = (group: NavGroup) =>
  (group as any).iconComponent ?? resolveSubsystemIcon(group.iconName);

const resolveIcon = (iconName?: string) =>
  resolveSubsystemIcon(iconName);

const itemId = (item: NavItem): string => item.screenId ?? item.id ?? '';

const containsActive = (
  items: NavItem[] | undefined,
  isActive: (id: string) => boolean,
): boolean => {
  for (const item of items ?? []) {
    const id = itemId(item);
    if (id && isActive(id)) return true;
    if (containsActive(item.items, isActive)) return true;
  }
  return false;
};

export const SellerCenterSidebar: React.FC<SellerCenterSidebarProps> = ({
  activeScreenId,
  onSelectScreen,
  groups,
  showMobileMoreMenu,
  setShowMobileMoreMenu,
  onBackToMain,
}) => {
  const [activeGroupKey, setActiveGroupKey] = useState<string | null>(null);
  const [isLevel2Open, setIsLevel2Open] = useState<boolean>(() => activeScreenId !== 'overview');
  const [groupTooltip, setGroupTooltip] = useState<{
    title: string;
    top: number;
    left: number;
  } | null>(null);

  const prevScreenIdRef = useRef(activeScreenId);

  useEffect(() => {
    if (activeScreenId === 'overview' && prevScreenIdRef.current !== 'overview') {
      setIsLevel2Open(false);
    }
    prevScreenIdRef.current = activeScreenId;
  }, [activeScreenId]);

  useEffect(() => {
    const next = groups.find((g) => {
      if (g.screenId && activeScreenId === g.screenId) return true;
      return containsActive(g.items, (id) => activeScreenId === id);
    });
    setActiveGroupKey(next ? next.key : groups[0]?.key ?? null);
  }, [activeScreenId, groups]);

  const activeGroup = groups.find((g) => g.key === activeGroupKey) ?? groups[0] ?? null;

  const handleGroupClick = (group: NavGroup) => {
    if (group.key === 'overview') {
      setActiveGroupKey(group.key);
      setIsLevel2Open(false);
      if (group.screenId) {
        onSelectScreen(group.screenId);
      }
      return;
    }

    if (group.screenId && activeScreenId === group.screenId) {
      setIsLevel2Open((v) => !v);
      return;
    }
    setActiveGroupKey(group.key);
    setIsLevel2Open(true);
    if (group.screenId) {
      onSelectScreen(group.screenId);
    }
  };

  const renderNavItems = (
    items: NavItem[] | undefined,
    depth = 0,
  ): React.ReactNode => {
    return (items ?? []).map((item) => {
      const id = itemId(item);
      const active = id ? activeScreenId === id : false;
      const ItemIcon = resolveIcon(item.iconName);
      const children = item.items ?? [];
      const hasChildren = children.length > 0;
      const pad = depth === 0 ? 'px-3 py-2' : 'px-2.5 py-1.5';
      return (
        <div
          key={`${item.id ?? id}-${item.label}-${depth}`}
          className={depth > 0 ? 'ml-3 border-l border-slate-200 pl-2' : ''}
        >
          <button
            onClick={() => id && onSelectScreen(id)}
            className={`w-full flex items-start gap-2.5 rounded-lg text-left transition-colors cursor-pointer ${pad} ${
              active
                ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-200/70'
                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            }`}
          >
            <ItemIcon className={`w-4 h-4 shrink-0 mt-0.5 ${active ? 'text-blue-600' : 'text-slate-400'}`} />
            <div className="min-w-0 flex-1">
              <div className="text-xs font-semibold truncate">{item.label}</div>
            </div>
            {hasChildren && (
              <ChevronRight className="w-3.5 h-3.5 text-slate-300 shrink-0 mt-0.5" />
            )}
          </button>
          {hasChildren && (
            <div className="mt-1 space-y-1">
              {renderNavItems(children, depth + 1)}
            </div>
          )}
        </div>
      );
    });
  };

  return (
    <>
      <div className="md:hidden bg-white border-b border-slate-200 px-2 py-1.5 shadow-2xs flex-shrink-0 z-20">
        <div className="grid grid-cols-5 gap-1">
          {groups.slice(0, 4).map((group) => {
            const Icon = resolveGroupIcon(group);
            return (
              <button
                key={group.key}
                onClick={() => handleGroupClick(group)}
                className={`flex flex-col items-center justify-center py-1.5 px-1 rounded-xl text-[10.5px] font-bold transition-all cursor-pointer ${
                  activeGroupKey === group.key
                    ? 'bg-blue-600 text-white shadow-xs'
                    : 'text-slate-600 hover:bg-slate-100'
                }`}
              >
                <Icon className="w-4 h-4 mb-0.5" />
                <span className="truncate max-w-full">{group.label}</span>
              </button>
            );
          })}
          <button
            onClick={() => setShowMobileMoreMenu(true)}
            className={`flex flex-col items-center justify-center py-1.5 px-1 rounded-xl text-[10.5px] font-bold transition-all cursor-pointer ${
              showMobileMoreMenu ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'
            }`}
          >
            <Menu className="w-4 h-4 mb-0.5" />
            <span className="truncate max-w-full">Khác</span>
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
                  <p className="text-[10px] text-slate-400">Menu đa cấp</p>
                </div>
              </div>
              <button
                onClick={() => setShowMobileMoreMenu(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-full cursor-pointer"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="space-y-3">
              {groups.map((group) => {
                const Icon = resolveGroupIcon(group);
                const active = activeGroupKey === group.key;
                return (
                  <div key={group.key} className="rounded-2xl border border-slate-200 overflow-hidden">
                    <button
                      onClick={() => handleGroupClick(group)}
                      className={`w-full flex items-center gap-3 p-3 text-left ${active ? 'bg-blue-50 text-blue-700' : 'bg-slate-50 text-slate-700'}`}
                    >
                      <div className={`p-2 rounded-xl ${active ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 shadow-2xs'}`}>
                        <Icon className="w-4 h-4" />
                      </div>
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-xs font-extrabold">{group.label}</p>
                      </div>
                      {group.items.length > 0 && (
                        <ChevronRight className="w-4 h-4 text-slate-400" />
                      )}
                    </button>
                    {active && group.items.length > 0 && (
                      <div className="p-2 bg-white border-t border-slate-100 space-y-1">
                        {renderNavItems(group.items)}
                      </div>
                    )}
                  </div>
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

      <aside className="hidden md:flex h-full shrink-0 select-none relative">
        <div className="w-[72px] h-full bg-slate-900 text-slate-300 border-r border-slate-800 flex flex-col items-center py-3 px-2 shrink-0">
          <div className="flex flex-col items-center gap-3 w-full flex-1 min-h-0">
            <button
              onClick={onBackToMain}
              title="Về Trang Chủ"
              className="w-11 h-11 rounded-xl bg-blue-600 hover:bg-blue-500 text-white flex items-center justify-center shadow-md transition-all cursor-pointer"
            >
              <Home className="w-5 h-5" />
            </button>
            <div className="w-8 h-px bg-slate-800" />
            <div className="flex-1 min-h-0 w-full overflow-y-auto no-scrollbar overscroll-contain pb-2">
              <div className="space-y-2.5 w-full flex flex-col items-center">
                {groups.map((group) => {
                  const Icon = resolveGroupIcon(group);
                  const isActive = activeGroupKey === group.key;
                  const showTooltip = (
                    e: React.MouseEvent<HTMLButtonElement> | React.FocusEvent<HTMLButtonElement>,
                  ) => {
                    const rect = e.currentTarget.getBoundingClientRect();
                    setGroupTooltip({
                      title: group.label,
                      top: rect.top + rect.height / 2,
                      left: rect.right + 12,
                    });
                  };
                  return (
                    <div key={group.key} className="relative flex flex-col items-center">
                      <button
                        onClick={() => handleGroupClick(group)}
                        onMouseEnter={showTooltip}
                        onMouseLeave={() => setGroupTooltip(null)}
                        onFocus={showTooltip}
                        onBlur={() => setGroupTooltip(null)}
                        aria-label={group.label}
                        className={`w-11 h-11 rounded-xl flex items-center justify-center transition-all cursor-pointer relative ${
                          isActive
                            ? 'bg-blue-500/20 text-blue-300 border border-blue-500/40'
                            : 'text-slate-400 hover:text-white hover:bg-slate-800/80'
                        }`}
                      >
                        <Icon className="w-5 h-5" />
                        {isActive && (
                          <span className="absolute left-[-8px] top-2.5 bottom-2.5 w-1 bg-blue-400 rounded-r-md" />
                        )}
                      </button>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

          <div className="flex flex-col items-center gap-2 w-full pt-3 mt-2 border-t border-slate-800 shrink-0">
            <button
              onClick={() => setIsLevel2Open((v) => !v)}
              className="w-9 h-9 rounded-lg bg-slate-800/90 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition-colors cursor-pointer"
              title={isLevel2Open ? 'Thu gọn menu cấp 2' : 'Mở rộng menu cấp 2'}
            >
              {isLevel2Open ? <ChevronLeft className="w-4 h-4" /> : <ChevronRight className="w-4 h-4" />}
            </button>
          </div>
        </div>

        {groupTooltip && (
          <div
            className="fixed -translate-y-1/2 px-2.5 py-1.5 rounded-md bg-slate-950 text-white text-xs whitespace-nowrap shadow-lg border border-slate-800 pointer-events-none z-[60] transition-opacity duration-150"
            style={{ top: groupTooltip.top, left: groupTooltip.left }}
          >
            <div className="font-semibold">{groupTooltip.title}</div>
          </div>
        )}

        {isLevel2Open && activeGroup && (
          <div className="w-72 bg-white border-r border-slate-200 flex flex-col h-full shadow-sm">
            <div className="p-4 border-b border-slate-200 flex items-center justify-between">
              <div className="min-w-0">
                <div className="text-[11px] font-semibold text-blue-600 uppercase tracking-wider">
                  Phân hệ
                </div>
                <h2 className="text-sm font-bold text-slate-900 truncate">{activeGroup.label}</h2>
              </div>
              <button
                onClick={() => setIsLevel2Open(false)}
                className="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                title="Thu gọn menu cấp 2"
              >
                <ChevronLeft className="w-4 h-4" />
              </button>
            </div>

            <nav className="flex-1 overflow-y-auto p-3 space-y-1.5">
              <div className="px-2 py-1 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                Chức năng chi tiết
              </div>
              {renderNavItems(activeGroup.items)}
              {!activeGroup.items || activeGroup.items.length === 0 && (
                <div className="px-2 py-3 text-xs text-slate-400">Không có mục con.</div>
              )}
            </nav>

            <div className="p-3 border-t border-slate-200 bg-slate-50/70">
              <button
                onClick={onBackToMain}
                className="w-full py-2 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 transition-colors flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs"
              >
                <ArrowLeft className="w-3.5 h-3.5" /> Về Trang Chủ
              </button>
            </div>
          </div>
        )}
      </aside>
    </>
  );
};
