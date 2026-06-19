import { render } from 'solid-js/web';
import App from './App.tsx';
import './index.scss';

const root = document.getElementById('admin-app');

if (root) {
  render(() => <App />, root);
} else {
  console.error("Admin app root element not found");
}
