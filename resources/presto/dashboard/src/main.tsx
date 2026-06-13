/* @refresh reload */
import { render } from 'solid-js/web';

const root = document.getElementById('admin-app');

if (root) {
    render(() => <div>PrestoWorld Admin Dashboard</div>, root);
}
