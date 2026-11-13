import React, { useState, useMemo, useEffect, useCallback, lazy, Suspense } from 'react';
import type { ComponentType } from 'react';
import type { InitialState, NavGroup } from '../types';
import { buildNavGroups, getRegisteredModuleMenusFromWindow } from '../nav/menuConcept';
import { screenFromPath, pathForScreen, pushScreen, replaceScreen, isKnownScreen } from '../router';
import { SellerCenterHeader } from './SellerCenterHeader';
import { SellerCenterSidebar } from './SellerCenterSidebar';
import { getScreenComponent, GenericScreen } from '../screens';

const MarketingPage = lazy(() =>
  import('./pages/MarketingPage').then((m) => ({ default: m.MarketingPage })),
);

interface SellerCenterPageProps {
  initialState: InitialState;
  onBackToMain: () => void;
}

const ScreenLoader = () => (
  <div className="flex items-center justify-center h-64 text-slate-400 text-xs font-bold animate-pulse">
    Đang tải chức năng…
  </div>
);

export const SellerCenterPage: React.FC<SellerCenterPageProps> = ({
  initialState,
  onBackToMain,
}) => {
  const [activeScreenId, setActiveScreenId] = useState<string>(
    initialState.page.screenId || 'overview',
  );
  const [marketingSubTab, setMarketingSubTab] = useState<
    'bookpress' | 'promotions' | 'events'
  >('bookpress');
  const [showMobileMoreMenu, setShowMobileMoreMenu] = useState(false);

  const [moduleComponents, setModuleComponents] = useState<
    Record<string, ComponentType<any>>
  >({});
  const [moduleFailed, setModuleFailed] = useState<Record<string, boolean>>({});

  const loadModuleScreen = useCallback(
    async (screenId: string): Promise<ComponentType<any> | null> => {
      const screen = initialState.screens.find((s) => s.id === screenId);
      if (!screen?.componentUrl) return null;
      try {
        const mod: any = await import(/* @vite-ignore */ screen.componentUrl);
        const published = (window as any).__moduleScreens?.[screenId];
        const raw =
          (mod?.default && typeof mod.default === 'object' ? mod.default : null) ??
          (window as any).EcommerceScreens ??
          mod;
        const map = raw?.default && typeof raw.default === 'object' ? raw.default : raw;
        const component =
          published ??
          (typeof map === 'object' ? map[screenId] : undefined) ??
          (typeof raw === 'object' ? raw[screenId] : undefined) ??
          (typeof mod?.default === 'function' ? mod.default : undefined);
        if (typeof component === 'function') {
          setModuleComponents((prev) => ({ ...prev, [screenId]: component }));
          return component;
        }
        console.error(
          `Module bundle loaded but screen "${screenId}" is not exported (url: ${screen.componentUrl}, keys: ` +
            `${Object.keys((window as any).__moduleScreens ?? {}).join(',') || 'none'})`,
        );
      } catch (err) {
        console.error(`Failed to load module screen "${screenId}" from ${screen.componentUrl}:`, err);
      }
      setModuleFailed((prev) => ({ ...prev, [screenId]: true }));
      return null;
    },
    [initialState.screens],
  );

  useEffect(() => {
    if (getScreenComponent(activeScreenId)) return;
    if (moduleComponents[activeScreenId] || moduleFailed[activeScreenId]) return;
    const screen = initialState.screens.find((s) => s.id === activeScreenId);
    if (screen?.componentUrl) {
      void loadModuleScreen(activeScreenId);
    }
  }, [activeScreenId, initialState.screens, moduleComponents, moduleFailed, loadModuleScreen]);

  const [menuVersion, setMenuVersion] = useState(0);

  // Listen for module menu registrations
  useEffect(() => {
    const handler = () => setMenuVersion((v) => v + 1);
    window.addEventListener('presto:module-menu-registered', handler);
    return () => window.removeEventListener('presto:module-menu-registered', handler);
  }, []);

  const groups = useMemo<NavGroup[]>(
    () => buildNavGroups(initialState.subsystems ?? [], initialState.menuSections),
    [initialState.subsystems, initialState.menuSections, menuVersion],
  );

  const knownScreens = useMemo(() => {
    const ids = new Set<string>();
    ids.add('overview');
    for (const sub of initialState.subsystems ?? []) {
      const id = sub.screenId ?? sub.id;
      if (id) ids.add(id);
    }
    for (const screen of initialState.screens) {
      if (screen.id) ids.add(screen.id);
    }
    const collect = (
      items?: { id?: string; screenId?: string; items?: unknown[] }[],
    ) => {
      for (const item of items ?? []) {
        const id = item.screenId ?? item.id;
        if (id) ids.add(id);
        collect(item.items as { id?: string; screenId?: string; items?: unknown[] }[] | undefined);
      }
    };
    for (const section of initialState.menuSections) {
      collect(section.items);
    }
    // Include screens from JS-registered module menus
    const moduleMenus = getRegisteredModuleMenusFromWindow();
    for (const menu of moduleMenus) {
      for (const section of menu.sections ?? []) {
        for (const group of section.groups ?? []) {
          for (const item of group.items ?? []) {
            if (item.screenId) ids.add(item.screenId);
            for (const child of item.children ?? []) {
              if (child.screenId) ids.add(child.screenId);
            }
          }
        }
      }
      for (const group of menu.groups ?? []) {
        for (const item of group.items ?? []) {
          if (item.screenId) ids.add(item.screenId);
          for (const child of item.children ?? []) {
            if (child.screenId) ids.add(child.screenId);
          }
        }
      }
      for (const item of menu.items ?? []) {
        if (item.screenId) ids.add(item.screenId);
        for (const child of item.children ?? []) {
          if (child.screenId) ids.add(child.screenId);
        }
      }
    }
    return ids;
  }, [initialState, menuVersion]);

  const navigateTo = (screenId: string) => {
    if (screenId === 'inventory-movements') {
      setActiveScreenId('inventory');
      const url = `${pathForScreen('inventory')}?tab=pnk`;
      if (window.location.pathname + window.location.search !== url) {
        window.history.pushState(null, '', url);
      }
      return;
    }
    if (!isKnownScreen(screenId, knownScreens)) {
      setActiveScreenId('overview');
      replaceScreen('overview');
      return;
    }
    setActiveScreenId(screenId);
    pushScreen(screenId);
  };

  useEffect(() => {
    const onPopState = () => {
      const screenId = screenFromPath(window.location.pathname);
      if (screenId !== null && !isKnownScreen(screenId, knownScreens)) {
        setActiveScreenId('overview');
        replaceScreen('overview');
        return;
      }
      setActiveScreenId(screenId ?? 'overview');
    };

    window.addEventListener('popstate', onPopState);
    return () => window.removeEventListener('popstate', onPopState);
  }, [knownScreens]);

  useEffect(() => {
    const screenId = screenFromPath(window.location.pathname);
    if (screenId !== null && !isKnownScreen(screenId, knownScreens)) {
      setActiveScreenId('overview');
      replaceScreen('overview');
    }
  }, [knownScreens]);

  useEffect(() => {
    const screenId = screenFromPath(window.location.pathname);
    if (screenId === 'inventory-movements') {
      const url = `${pathForScreen('inventory')}?tab=pnk`;
      if (window.location.pathname + window.location.search !== url) {
        replaceScreen('inventory');
        window.history.replaceState(null, '', url);
      }
      setActiveScreenId('inventory');
    }
  }, [knownScreens]);

  const handleSelectScreen = (screenId: string) => {
    if (screenId === 'marketing') {
      navigateTo('marketing');
      if (
        activeScreenId !== 'bookpress' &&
        activeScreenId !== 'promotions' &&
        activeScreenId !== 'events'
      ) {
        setMarketingSubTab('bookpress');
      }
      return;
    }
    navigateTo(screenId);
  };

  const renderPageContent = () => {
    if (
      activeScreenId === 'marketing' ||
      activeScreenId === 'bookpress' ||
      activeScreenId === 'promotions' ||
      activeScreenId === 'events'
    ) {
      return (
        <MarketingPage
          subTab={
            activeScreenId === 'marketing'
              ? marketingSubTab
              : (activeScreenId as 'bookpress' | 'promotions' | 'events')
          }
          onSubTabChange={setMarketingSubTab}
        />
      );
    }

    const screen = initialState.screens.find((s) => s.id === activeScreenId);
    const LocalComponent = getScreenComponent(activeScreenId);
    const ModuleComponent = moduleComponents[activeScreenId];
    const Component = LocalComponent ?? ModuleComponent;

    if (!Component) {
      if (screen?.componentUrl && !moduleFailed[activeScreenId]) {
        return <ScreenLoader />;
      }
      return (
        <GenericScreen
          screenId={activeScreenId}
          title={screen?.title ?? activeScreenId}
          widgets={initialState.widgets}
          screens={initialState.screens}
          menuSections={initialState.menuSections}
          user={initialState.user}
          settings={screen?.settings}
          onNavigate={handleSelectScreen}
        />
      );
    }

    return (
      <Component
        screenId={activeScreenId}
        title={screen?.title ?? activeScreenId}
        widgets={initialState.widgets}
        screens={initialState.screens}
        menuSections={initialState.menuSections}
        user={initialState.user}
        settings={screen?.settings}
        onNavigate={handleSelectScreen}
      />
    );
  };

  return (
    <div className="h-full bg-slate-100 flex flex-col font-sans transition-colors animate-in fade-in duration-200 overflow-x-hidden">
      <SellerCenterHeader
        onBackToMain={onBackToMain}
        activeTab={activeScreenId}
        onTabChange={handleSelectScreen}
        user={initialState.user}
        pageTitle={initialState.page.title}
      />

      <div className="flex-1 flex flex-col md:flex-row overflow-hidden">
        <SellerCenterSidebar
          activeScreenId={activeScreenId}
          onSelectScreen={handleSelectScreen}
          groups={groups}
          showMobileMoreMenu={showMobileMoreMenu}
          setShowMobileMoreMenu={setShowMobileMoreMenu}
          onBackToMain={onBackToMain}
        />

        <main className="flex-1 overflow-y-auto p-3 sm:p-4 md:p-6 pb-24 md:pb-6 bg-slate-50/50">
          <Suspense fallback={<ScreenLoader />}>{renderPageContent()}</Suspense>
        </main>
      </div>
    </div>
  );
};
