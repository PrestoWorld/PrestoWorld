# Dashboard Menu (2-Level Sidebar) — JS Concept & PHP Registration

Tài liệu mô tả menu sidebar mới của Dashboard SPA (`modules/Dashboard`), contract dữ liệu từ PHP, và cách module/plugin đăng ký menu.

## 1. Tổng quan UI

Sidebar desktop dùng mô hình **2 cấp** (tham chiếu phong cách `nhanhsync-hub`):

- **Level 1 — Icon rail (nhóm/phân hệ):** thanh dọc cố định hiển thị icon các nhóm. Hover hiện tooltip label (absolute, không chiếm layout). Click chọn nhóm và mở/đóng Level 2; nhóm đang active được sync theo `activeTab`.
- **Level 2 — Drawer (chi tiết):** panel hiện submenu/mục con của nhóm đang chọn, hỗ trợ **lồng nhiều cấp** (`items`/`children` đệ quy). Có nút thu gọn.

Mobile giữ thanh điều hướng gọn và nhóm/mục con trong sheet "Khác".

Nguồn UI: `modules/Dashboard/src/components/SellerCenterSidebar.tsx` (build ra `public/assets/dashboard/*`, serve bởi `App\Http\Controllers\Admin\SpaController`).

## 2. JS Concept / Contract

- Contract & normalizer: `modules/Dashboard/src/nav/menuConcept.ts`
- Types/re-export: `modules/Dashboard/src/types.ts`

```ts
import {
  PhpMenuRegistrationConcept,
  normalizeMenuSections,
  normalizeSubsystemNav,
  resolveMenuItemId,
} from '../types';
```

### Nguồn dữ liệu Level 1 (Initial State)
SPA đọc `window.__INITIAL_DASHBOARD_STATE__` (hoặc `GET /api/admin/initial-state`), key liên quan:

| Key | Vai trò | Level |
|---|---|---|
| `subsystems` | Phân hệ/top-level entries (mở trực tiếp screen) | 1 (rail) |
| `menuSections` | Nhóm nội dung/menu có mục con lồng nhau | 1 (rail) + 2..n (drawer) |
| `screens` | Registry màn hình, dùng resolve route/title/component | — |

### `subsystems[]` (SubsystemNavItem)
```ts
{ id, label, subLabel?, icon?, screenId?, priority?, section? }
```
- Thường tạo bởi `DashboardSubsystem::register(...)` (PHP). `subLabel` đánh dấu group là subsystem và hiển thị dưới tiêu đề.

### `menuSections[]` (MenuSection → nested items)
```ts
{
  id, title, priority, icon?, screenId?,
  items: MenuItem[]
}
// MenuItem (đệ quy)
{ id, label, url, icon?, priority, screenId?, items?: MenuItem[], children?: MenuItem[] }
```
- JS chấp nhận **`items` hoặc `children`** cho node con (`normalizeMenuNode` hợp nhất, sort theo `priority`, dedupe cơ bản).
- `screenId` (hoặc `id`) dùng để điều hướng/active.

`PhpMenuRegistrationConcept` tóm tắt keys/fields chuẩn để PHP tuân theo.

## 3. Đăng ký từ PHP

### 3.1 Subsystem (Level 1 rail, screen trực tiếp)
```php
use PrestoWorld\Foundation\Admin\DashboardSubsystem;

DashboardSubsystem::register(
    $app,
    id: 'commerce',
    label: 'Bán hàng',
    subLabel: 'Đơn hàng & kho',
    icon: 'ShoppingBag',      // tên icon Lucide (map ở JS resolveIcon)
    priority: 20,
    source: 'module',
    section: 'overview',      // section nội bộ menu context
);
```
- Yêu cầu container có `MenuContextRepository`; tự đăng ký screen nếu có `DashboardScreenRegistryInterface`.
- `SpaController::buildSubsystemNav()` thu thập các group có `attributes.subLabel` (trừ section `modules`) → `initialState.subsystems`.

### 3.2 Menu sections / nhóm / mục lồng nhau
Làm việc với `PrestoWorld\Contracts\Admin\Menu\MenuContextRepository` (binding sẵn trong admin context):

```php
$menu = $app->make(\PrestoWorld\Contracts\Admin\Menu\MenuContextRepository::class);

// Section (category) — core sections có sẵn: overview, ecommerce, analytics, crm, modules, appearance, tools, settings
$menu->registerSection('integrations', 'Tích hợp', icon: 'Puzzle', priority: 55);

// Group trong section (attributes['section'])
$menu->registerGroup(
    id: 'sync',
    label: 'Đồng bộ',
    icon: 'RefreshCw',
    priority: 10,
    attributes: ['section' => 'integrations']
);

// Item (Level 2+) thuộc group
$menu->registerItem(
    parentGroup: 'sync',
    label: 'Kênh bán',
    url: '',
    icon: 'Link',
    priority: 10,
    id: 'sync-channels',
    screenId: 'sync-channels'
);
```

- `MenuItem::toArray()` serialize children thành cả `children` và `items`.
- `SpaController::buildMenuSections()` chuyển tree nội bộ thành `{id,title,priority,icon,screenId,items}`; group children thành `items` lồng nhau; section `content` được "flatten" thành các nhóm riêng; `overview`/`modules` có xử lý đặc biệt (subsystem tách ra `subsystems`).

### 3.3 Hooks (đăng ký động trước khi serialize SPA state)
`SpaController::dispatchAdminHooks()` gọi (một lần/request):
```php
do_action('admin.dashboard.nav', $menu);              // MenuContextRepository
do_action('admin.screen.register', $screens);         // DashboardScreenRegistryInterface
do_action('admin.dashboard.widgets', $widgets);       // DashboardWidgetRepository
```
Đăng ký menu/screen/widget trong callback các hook này để xuất hiện trong initial state/API.

## 4. API/State endpoints liên quan
- `GET /api/admin/initial-state` → `user, screens, menuSections, subsystems, widgets, screenOptions, adminBar, page`
- `GET /api/admin/menu` → `{ menuSections, subsystems }`
- `GET /api/admin/menu/tree` → tree nội bộ (`getTreeAsArray()`)
- `GET /api/admin/dashboard/screens` → screens registry + fallback từ menu

## 5. Quy ước & lưu ý
- **Icon:** chuỗi tên icon Lucide đã được map trong JS (`resolveIcon`/`subsystemIcons`); icon không map sẽ fallback `Circle`.
- **Priority:** số nhỏ ưu tiên trước; JS/PHP đều sort theo `priority` (mặc định 10).
- **Điều hướng:** `screenId`/`id` phải khớp screen/route đã biết; `overview` là mặc định an toàn.
- **Build:** sau khi đổi `modules/Dashboard/src`, chạy `npm run build` trong `modules/Dashboard` để cập nhật `public/assets/dashboard/*` (cache-bust qua manifest/mtime trong `SpaController`); hard refresh trình duyệt.
- **Mở rộng:** thêm icon vào map JS nếu dùng icon mới; giữ contract field để tương thích normalizer/renderer 2 cấp.
