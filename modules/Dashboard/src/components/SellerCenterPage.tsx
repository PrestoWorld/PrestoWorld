import React, { useState, useMemo } from 'react';
import type { InitialState, SubsystemNavItem } from '../types';
import { resolveSubsystemIcon } from '../nav/subsystemIcons';
import { SellerCenterHeader } from './SellerCenterHeader';
import { SellerCenterSidebar } from './SellerCenterSidebar';
import { MarketingPage } from './pages/MarketingPage';
import { getScreenComponent, GenericScreen } from '../screens';

interface SellerCenterPageProps {
  initialState: InitialState;
  onBackToMain: () => void;
}

export const SellerCenterPage: React.FC<SellerCenterPageProps> = ({
  initialState,
  onBackToMain,
}) => {
  const [activeTab, setActiveTab] = useState<string>('overview');
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

  const isTabActive = (tabId: string) => {
    if (tabId === 'marketing') {
      return activeTab === 'marketing' || activeTab === 'bookpress' || activeTab === 'promotions' || activeTab === 'events';
    }
    return activeTab === tabId;
  };

  const handleSelectNavTab = (tabId: string) => {
    if (tabId === 'marketing') {
      setActiveTab('marketing');
      if (activeTab !== 'bookpress' && activeTab !== 'promotions' && activeTab !== 'events') {
        setMarketingSubTab('bookpress');
      }
      return;
    }
    setActiveTab(tabId);
  };

  const handleSelectScreen = (screenId: string) => {
    setActiveTab(screenId);
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
          {renderPageContent()}
        </main>
      </div>
    </div>
  );
};