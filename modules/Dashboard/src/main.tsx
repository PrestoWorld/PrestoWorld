import React from 'react';
import ReactDOM from 'react-dom/client';
import './index.css';
import { SellerCenterPage } from './components/SellerCenterPage';
import { api } from './api/client';
import './screens';
import type { InitialState } from './types';

const fallbackInitialState: InitialState = {
  user: { id: '1', name: 'Administrator', role: 'admin' },
  screens: [],
  menuSections: [],
  widgets: [],
  screenOptions: [],
  adminBar: { items: [] },
  page: { path: '/dashboard', title: 'Dashboard', screenId: 'overview' },
};

async function boot() {
  let initialState = fallbackInitialState;

  try {
    initialState = await api.getInitialState();
  } catch (err) {
    console.error('Failed to load initial state, using fallback', err);
  }

  // Preload module bundles that register menus so menus appear immediately
  if (initialState.menuBundles?.length) {
    await Promise.all(
      initialState.menuBundles.map((url) =>
        import(/* @vite-ignore */ url).catch((err) =>
          console.warn(`Failed to preload menu bundle ${url}:`, err),
        ),
      ),
    );
  }

  const root = ReactDOM.createRoot(document.getElementById('root')!);
  root.render(
    <React.StrictMode>
      <SellerCenterPage
        initialState={initialState}
        onBackToMain={() => {
          window.location.href = '/';
        }}
      />
    </React.StrictMode>,
  );
}

boot();