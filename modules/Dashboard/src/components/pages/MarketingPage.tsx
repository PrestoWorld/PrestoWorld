import React, { useState, useMemo } from 'react';
import type { DashboardWidget } from '../../types';

interface MarketingPageProps {
  subTab: 'bookpress' | 'promotions' | 'events';
  onSubTabChange: (tab: 'bookpress' | 'promotions' | 'events') => void;
}

export const MarketingPage: React.FC<MarketingPageProps> = ({ subTab, onSubTabChange }) => {
  if (subTab === 'bookpress') {
    return (
      <div>
        <h1>BookPress Sub-site</h1>
      </div>
    );
  }

  if (subTab === 'promotions') {
    return (
      <div>
        <h1>Voucher</h1>
      </div>
    );
  }

  if (subTab === 'events') {
    return (
      <div>
        <h1>Hội sách</h1>
      </div>
    );
  }

  return null;
};