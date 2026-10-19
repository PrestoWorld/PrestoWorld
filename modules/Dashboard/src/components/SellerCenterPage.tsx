import React, { useState, useMemo, useEffect, lazy, Suspense } from 'react';
import type { InitialState, SubsystemNavItem } from '../types';
import { resolveSubsystemIcon } from '../nav/subsystemIcons';
import { screenFromPath, pushScreen, replaceScreen, isKnownScreen } from '../router';
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
    Đang tải phân hệ…
  </div>
);

export const SellerCenterPage: React.FC<SellerCenterPageProps> = ({
  initialState,
  onBackToMain,
}) => {
  const [activeTab, setActiveTab] = useState<string>(initialState.page.screenId || 'overview');
  const [marketingSubTab, setMarketingSubTab] = useState<'bookpress' | 'promotions' | 'events'>('bookpress');
  const [showMobileMoreMenu, setShowMobileMoreMenu] = useState(false);

  const merchantNav = useMemo(
    () =>
      (initialState.subsystems ?? []).map((sub: SubsystemNavItem) => ({
        id: sub.screenId ?? sub.id,
        label: sub.label,
        subLabel: sub.subLabel ?? '',
        icon: resolveSubsystemIcon(sub.icon),
      })),
    [initialState.subsystems],
  );

  // Route table built from backend state: subsystems + registered screens + menu items
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
    const collect = (items?: { id?: string; screenId?: string; items?: unknown[] }[]) => {
      for (const item of items ?? []) {
        const id = item.screenId ?? item.id;
        if (id) ids.add(id);
        collect(item.items as { id?: string; screenId?: string; items?: unknown[] }[] | undefined);
      }
    };
    for (const section of initialState.menuSections) {
      collect(section.items);
    }
    return ids;
  }, [initialState]);

  const navigateTo = (tabId: string) => {
    if (!isKnownScreen(tabId, knownScreens)) {
      setActiveTab('overview');
      replaceScreen('overview');
      return;
    }
    setActiveTab(tabId);
    pushScreen(tabId);
  };

  // Browser back/forward navigation
  useEffect(() => {
    const onPopState = () => {
      const screenId = screenFromPath(window.location.pathname);
      if (screenId !== null && !isKnownScreen(screenId, knownScreens)) {
        setActiveTab('overview');
        replaceScreen('overview');
        return;
      }
      setActiveTab(screenId ?? 'overview');
    };

    window.addEventListener('popstate', onPopState);
    return () => window.removeEventListener('popstate', onPopState);
  }, [knownScreens]);

  // Redirect direct loads of unknown screens back to the overview
  useEffect(() => {
    const screenId = screenFromPath(window.location.pathname);
    if (screenId !== null && !isKnownScreen(screenId, knownScreens)) {
      setActiveTab('overview');
      replaceScreen('overview');
    }
  }, [knownScreens]);

  const isTabActive = (tabId: string) => {
    if (tabId === 'marketing') {
      return activeTab === 'marketing' || activeTab === 'bookpress' || activeTab === 'promotions' || activeTab === 'events';
    }
    return activeTab === tabId;
  };

  const handleSelectNavTab = (tabId: string) => {
    if (tabId === 'marketing') {
      navigateTo('marketing');
      if (activeTab !== 'bookpress' && activeTab !== 'promotions' && activeTab !== 'events') {
        setMarketingSubTab('bookpress');
      }
      return;
    }
    navigateTo(tabId);
  };

  const handleSelectScreen = (screenId: string) => {
    navigateTo(screenId);
  };

  const handleMobileMoreClick = (tabId: string) => {
    handleSelectNavTab(tabId);
    setShowMobileMoreMenu(false);
  };

  const renderPageContent = () => {
    if (
      activeTab === 'marketing' ||
      activeTab === 'bookpress' ||
      activeTab === 'promotions' ||
      activeTab === 'events'
    ) {
      return (
        <MarketingPage
          subTab={activeTab === 'marketing' ? marketingSubTab : (activeTab as 'bookpress' | 'promotions' | 'events')}
          onSubTabChange={setMarketingSubTab}
        />
      );
    }

    const screen = initialState.screens.find((s) => s.id === activeTab);
    const Component = getScreenComponent(activeTab) ?? GenericScreen;

    return (
      <Component
        screenId={activeTab}
        title={screen?.title ?? activeTab}
        widgets={initialState.widgets}
        screens={initialState.screens}
        menuSections={initialState.menuSections}
        user={initialState.user}
        settings={screen?.settings}
      />
    );
  };

  return (
    <div className="min-h-screen bg-slate-100 flex flex-col font-sans transition-colors animate-in fade-in duration-200 overflow-x-hidden">
      <SellerCenterHeader
        onBackToMain={onBackToMain}
        activeTab={activeTab}
        onTabChange={handleSelectNavTab}
        user={initialState.user}
        pageTitle={initialState.page.title}
      />

      <div className="flex-1 flex flex-col md:flex-row overflow-hidden">
        <SellerCenterSidebar
          activeTab={activeTab}
          onTabChange={handleSelectNavTab}
          navItems={merchantNav}
          isTabActive={isTabActive}
          coreSections={initialState.menuSections}
          onSelectScreen={handleSelectScreen}
          showMobileMoreMenu={showMobileMoreMenu}
          setShowMobileMoreMenu={setShowMobileMoreMenu}
          onBackToMain={onBackToMain}
          mobileMoreClick={handleMobileMoreClick}
        />

        <main className="flex-1 overflow-y-auto p-3 sm:p-4 md:p-6 pb-24 md:pb-6 bg-slate-50/50">
          <Suspense fallback={<ScreenLoader />}>{renderPageContent()}</Suspense>
        </main>
      </div>
    </div>
  );
};
