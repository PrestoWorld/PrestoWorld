import React, { useEffect, useMemo, useState } from 'react';
import {
  ArrowLeft,
  ChevronLeft,
  ChevronRight,
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
  X,
  Layers,
} from 'lucide-react';
import type { MenuSection } from '../types';
import {
  normalizeMenuSections,
  resolveMenuItemId,
  type SidebarMenuNode,
} from '../nav/menuConcept';

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
  items?: CoreItem[];
  priority?: number;
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

type GroupKind = 'nav' | 'section';

interface SidebarGroup {
  key: string;
  kind: GroupKind;
  title: string;
  subtitle?: string;
  icon: React.ComponentType<{ className?: string }>;
  navId?: string;
  section?: MenuSection;
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
  Layers,
};

const resolveIcon = (name?: string): React.ComponentType<{ className?: string }> =>
  (name && coreIcons[name]) || Circle;

const itemId = (item: { id?: string; screenId?: string }) => resolveMenuItemId(item);

const containsActive = (items: CoreItem[] | undefined, isTabActive: (id: string) => boolean): boolean => {
  for (const item of items ?? []) {
    const id = itemId(item);
    if (id && isTabActive(id)) return true;
    if (containsActive(item.items, isTabActive)) return true;
  }
  return false;
};

export const SellerCenterSidebar: React.FC<SellerCenterSidebarProps> = ({
  activeTab,
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
  const menuSections = useMemo(() => {
    const normalized = normalizeMenuSections(coreSections);
    const sections: MenuSection[] = [];
    for (const section of normalized) {
      if (section.id === 'overview') continue;
      if (section.id === 'content') {
        for (const item of section.items ?? []) {
          const id = resolveMenuItemId(item as any);
          const nested = (item as any).items ?? [];
          sections.push({
            id,
            title: item.label,
            priority: item.priority ?? section.priority,
            icon: item.icon ?? section.icon,
            screenId: (item as any).screenId,
            items: nested.length ? (nested as any) : [item],
          } as MenuSection);
        }
        continue;
      }
      sections.push(section);
    }
    return sections;
  }, [coreSections]);

  const groups = useMemo<SidebarGroup[]>(() => {
    const navGroups: SidebarGroup[] = navItems.map((item) => ({
      key: `nav:${item.id}`,
      kind: 'nav',
      title: item.label,
      subtitle: item.subLabel,
      icon: item.icon,
      navId: item.id,
    }));
    const sectionGroups: SidebarGroup[] = menuSections.map((section) => ({
      key: `section:${section.id}`,
      kind: 'section',
      title: section.title,
      subtitle: `${(section.items ?? []).length} mục`,
      icon: resolveIcon(section.icon),
      section,
    }));
    return [...navGroups, ...sectionGroups];
  }, [navItems, menuSections]);

  const groupForTab = (tab: string): string | null => {
    for (const group of groups) {
      if (group.kind === 'nav' && group.navId && isTabActive(group.navId)) return group.key;
      if (group.kind === 'nav' && group.navId === tab) return group.key;
      if (group.kind === 'section' && containsActive(group.section?.items as CoreItem[] | undefined, isTabActive)) {
        return group.key;
      }
      if (group.kind === 'section' && group.section?.id === tab) return group.key;
    }
    return groups[0]?.key ?? null;
  };

  const [activeGroupKey, setActiveGroupKey] = useState<string | null>(() => groupForTab(activeTab));
  const [isLevel2Open, setIsLevel2Open] = useState<boolean>(true);

  useEffect(() => {
    const next = groupForTab(activeTab);
    if (next) setActiveGroupKey(next);
  }, [activeTab, groups]); // eslint-disable-line react-hooks/exhaustive-deps

  const activeGroup = groups.find((g) => g.key === activeGroupKey) ?? groups[0] ?? null;
  const overflowActive = navItems.slice(4).some((item) => isTabActive(item.id));

  const handleGroupClick = (group: SidebarGroup) => {
    if (group.kind === 'nav' && group.navId) {
      onTabChange(group.navId);
      if (activeGroupKey === group.key) {
        setIsLevel2Open((v) => !v);
      } else {
        setActiveGroupKey(group.key);
        setIsLevel2Open(true);
      }
      return;
    }
    if (activeGroupKey === group.key) {
      setIsLevel2Open((v) => !v);
    } else {
      setActiveGroupKey(group.key);
      setIsLevel2Open(true);
    }
  };

  const renderSectionItems = (items: CoreItem[] | SidebarMenuNode[] | undefined, depth = 0): React.ReactNode => {
    return (items ?? []).map((item: any) => {
      const id = itemId(item);
      const active = id ? isTabActive(id) : false;
      const ItemIcon = resolveIcon(item.icon ?? (activeGroup?.section ? activeGroup.section.icon : undefined));
      const children = (item.items ?? item.children ?? []) as CoreItem[];
      const hasChildren = children.length > 0;
      const pad = depth === 0 ? 'px-3 py-2' : 'px-2.5 py-1.5';
      return (
        <div key={item.id ?? id} className={depth > 0 ? 'ml-3 border-l border-slate-200 pl-2' : ''}>
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
            {hasChildren && <ChevronRight className="w-3.5 h-3.5 text-slate-300 shrink-0 mt-0.5" />}
          </button>
          {hasChildren && <div className="mt-1 space-y-1">{renderSectionItems(children, depth + 1)}</div>}
        </div>
      );
    });
  };

  return (
    <>
      <div className="md:hidden bg-white border-b border-slate-200 px-2 py-1.5 shadow-2xs flex-shrink-0 z-20">
        <div className="grid grid-cols-5 gap-1">
          {navItems.slice(0, 4).map((item) => (
            <button
              key={item.id}
              onClick={() => onTabChange(item.id)}
              className={`flex flex-col items-center justify-center py-1.5 px-1 rounded-xl text-[10.5px] font-bold transition-all cursor-pointer ${
                isTabActive(item.id) ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'
              }`}
            >
              <item.icon className="w-4 h-4 mb-0.5" />
              <span className="truncate max-w-full">{item.label}</span>
            </button>
          ))}
          <button
            onClick={() => setShowMobileMoreMenu(true)}
            className={`flex flex-col items-center justify-center py-1.5 px-1 rounded-xl text-[10.5px] font-bold transition-all cursor-pointer ${
              overflowActive || showMobileMoreMenu ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'
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
              <button onClick={() => setShowMobileMoreMenu(false)} className="p-1.5 text-slate-400 hover:text-slate-600 rounded-full cursor-pointer">
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="space-y-3">
              {groups.map((group) => {
                const Icon = group.icon;
                const active = activeGroupKey === group.key;
                return (
                  <div key={group.key} className="rounded-2xl border border-slate-200 overflow-hidden">
                    <button
                      onClick={() => {
                        if (group.kind === 'nav' && group.navId) {
                          mobileMoreClick(group.navId);
                        } else {
                          setActiveGroupKey(group.key);
                          setIsLevel2Open(true);
                        }
                      }}
                      className={`w-full flex items-center gap-3 p-3 text-left ${active ? 'bg-blue-50 text-blue-700' : 'bg-slate-50 text-slate-700'}`}
                    >
                      <div className={`p-2 rounded-xl ${active ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 shadow-2xs'}`}>
                        <Icon className="w-4 h-4" />
                      </div>
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-xs font-extrabold">{group.title}</p>
                        {group.subtitle && <p className="text-[10px] text-slate-500 truncate">{group.subtitle}</p>}
                      </div>
                      {group.kind === 'section' && <ChevronRight className="w-4 h-4 text-slate-400" />}
                    </button>
                    {group.kind === 'section' && active && (
                      <div className="p-2 bg-white border-t border-slate-100 space-y-1">
                        {(group.section?.items ?? []).map((item: any) => {
                          const id = item.screenId ?? item.id;
                          const ItemIcon = resolveIcon(item.icon ?? group.section?.icon);
                          const itemActive = id ? isTabActive(id) : false;
                          return (
                            <button
                              key={item.id}
                              onClick={() => {
                                if (id) {
                                  onSelectScreen(id);
                                  setShowMobileMoreMenu(false);
                                }
                              }}
                              className={`w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-left text-xs font-semibold ${
                                itemActive ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100'
                              }`}
                            >
                              <ItemIcon className="w-4 h-4 text-slate-400" />
                              <span className="truncate">{item.label}</span>
                            </button>
                          );
                        })}
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

      <aside className="hidden md:flex h-[calc(100vh-53px)] shrink-0 select-none">
        <div className="w-[72px] bg-slate-900 text-slate-300 border-r border-slate-800 flex flex-col items-center justify-between py-3 px-2 shrink-0">
          <div className="flex flex-col items-center gap-3 w-full">
            <button
              onClick={onBackToMain}
              title="Về Trang Chủ"
              className="w-11 h-11 rounded-xl bg-blue-600 hover:bg-blue-500 text-white flex items-center justify-center shadow-md transition-all cursor-pointer"
            >
              <LayoutDashboard className="w-5 h-5" />
            </button>
            <div className="w-8 h-px bg-slate-800" />
            <div className="space-y-2.5 w-full flex flex-col items-center overflow-visible pb-2">
              {groups.map((group) => {
                const Icon = group.icon;
                const isActive = activeGroup?.key === group.key;
                const isGroupActive =
                  group.kind === 'nav' && group.navId
                    ? isTabActive(group.navId)
                    : containsActive(group.section?.items as CoreItem[] | undefined, isTabActive);
                return (
                  <div key={group.key} className="relative group/tooltip flex flex-col items-center">
                    <button
                      onClick={() => handleGroupClick(group)}
                      aria-label={group.title}
                      className={`w-11 h-11 rounded-xl flex items-center justify-center transition-all cursor-pointer relative ${
                        isActive || isGroupActive
                          ? 'bg-blue-500/20 text-blue-300 border border-blue-500/40'
                          : 'text-slate-400 hover:text-white hover:bg-slate-800/80'
                      }`}
                    >
                      <Icon className="w-5 h-5" />
                      {isActive && <span className="absolute left-[-8px] top-2.5 bottom-2.5 w-1 bg-blue-400 rounded-r-md" />}
                    </button>
                    <div className="absolute left-full top-1/2 ml-3 -translate-y-1/2 translate-x-1 scale-95 px-2.5 py-1.5 rounded-md bg-slate-950 text-white text-xs whitespace-nowrap shadow-lg border border-slate-800 pointer-events-none opacity-0 transition-all duration-150 ease-out group-hover/tooltip:opacity-100 group-hover/tooltip:translate-x-0 group-hover/tooltip:scale-100 z-50">
                      <div className="font-semibold">{group.title}</div>
                      {group.subtitle && <div className="text-[10px] text-slate-400">{group.subtitle}</div>}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          <div className="flex flex-col items-center gap-2 w-full pt-3 border-t border-slate-800">
            <button
              onClick={() => setIsLevel2Open((v) => !v)}
              className="w-9 h-9 rounded-lg bg-slate-800/90 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition-colors cursor-pointer"
              title={isLevel2Open ? 'Thu gọn menu cấp 2' : 'Mở rộng menu cấp 2'}
            >
              {isLevel2Open ? <ChevronLeft className="w-4 h-4" /> : <ChevronRight className="w-4 h-4" />}
            </button>
          </div>
        </div>

        {isLevel2Open && activeGroup && (
          <div className="w-72 bg-white border-r border-slate-200 flex flex-col h-full shadow-sm">
            <div className="p-4 border-b border-slate-200 flex items-center justify-between">
              <div className="min-w-0">
                <div className="text-[11px] font-semibold text-blue-600 uppercase tracking-wider">Phân hệ</div>
                <h2 className="text-sm font-bold text-slate-900 truncate">{activeGroup.title}</h2>
                {activeGroup.subtitle && <p className="text-[11px] text-slate-400 truncate">{activeGroup.subtitle}</p>}
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
              {activeGroup.kind === 'nav' && activeGroup.navId ? (
                <div className="space-y-2">
                  <div className="px-2 py-1 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Màn hình</div>
                  <button
                    onClick={() => onTabChange(activeGroup.navId!)}
                    className={`w-full text-left p-3 rounded-lg border transition-colors cursor-pointer ${
                      isTabActive(activeGroup.navId)
                        ? 'bg-blue-50 border-blue-200 text-blue-700 shadow-2xs'
                        : 'border-transparent hover:bg-slate-100 text-slate-700'
                    }`}
                  >
                    <div className="flex items-center gap-2.5">
                      <activeGroup.icon className={`w-4 h-4 ${isTabActive(activeGroup.navId) ? 'text-blue-600' : 'text-slate-400'}`} />
                      <div className="min-w-0">
                        <div className="text-xs font-bold truncate">{activeGroup.title}</div>
                        {activeGroup.subtitle && <div className="text-[10px] text-slate-500 truncate">{activeGroup.subtitle}</div>}
                      </div>
                    </div>
                  </button>
                  <p className="px-2 text-[11px] text-slate-400 leading-relaxed">
                    Phân hệ này mở trực tiếp màn hình tương ứng. Các phân hệ khác và nhóm nội dung nằm ở thanh bên trái.
                  </p>
                </div>
              ) : (
                <div className="space-y-1.5">
                  <div className="px-2 py-1 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Chức năng chi tiết</div>
                  {renderSectionItems(activeGroup.section?.items as CoreItem[] | undefined)}
                  {(!activeGroup.section?.items || activeGroup.section.items.length === 0) && (
                    <div className="px-2 py-3 text-xs text-slate-400">Không có mục con.</div>
                  )}
                </div>
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