import React from 'react';
import ReactDOM from 'react-dom/client';
import './index.css';
import { SellerCenterPage } from './components/SellerCenterPage';
import { api } from './api/client';

 // Fetch initial state from Admin module API
 async function initializeApp() {
   try {
     const initialState = await api.getInitialState();
     console.log('Initial state loaded', initialState.user);
   } catch (err) {
     console.error('Failed to load initial state', err);
   }
 }

 initializeApp();

const root = ReactDOM.createRoot(document.getElementById('root')!);
root.render(
  <React.StrictMode>
    <SellerCenterPage
      initialState={{ user: { id: '1', name: 'Admin', role: 'admin' }, screens: [], menuSections: [], widgets: [], page: { title: 'Dashboard', screenId: 'overview' } } }
      onBackToMain={() => console.log('Back to main')}
    />
  </React.StrictMode>
);