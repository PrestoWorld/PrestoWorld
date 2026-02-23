<?php

declare(strict_types=1);

namespace Modules\Memberships\Controllers;

use App\Foundation\Admin\AdminController;
use Modules\Memberships\Admin\MembershipTable;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class MembershipAdminController extends AdminController
{
    public function index(Request $request): Response
    {
        $table = (new MembershipTable($this->db()))->prepare($request);
        return $this->htmlResponse(
            $this->adminPage('Memberships', $table->render(), [
                'new_url' => '/dashboard/memberships/create',
                'new_label' => '+ New Membership',
            ])
        );
    }

    public function plans(Request $request): Response
    {
        $plans   = $this->db()->select('*')->from('optilarity_membership_plans')->orderBy('price', 'ASC')->fetchAll();
        $content = $this->renderPlanList($plans);
        return $this->htmlResponse(
            $this->adminPage('Membership Plans', $content, [
                'new_url' => '/dashboard/membership-plans/create',
                'new_label' => '+ Add Plan',
            ])
        );
    }

    public function createPlan(Request $request): Response
    {
        $form = $this->renderPlanForm([], '/dashboard/membership-plans/create');
        return $this->htmlResponse(
            $this->adminPage('Add Plan', $form, ['breadcrumbs' => ['Plans' => '/dashboard/membership-plans', 'Add' => '']])
        );
    }

    public function storePlan(Request $request): Response
    {
        $body = (array)$request->post();
        try {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $body['name'] ?? 'plan')) . '-' . time();
            $this->db()->insert('optilarity_membership_plans')->values([
                'name'          => $body['name']          ?? '',
                'slug'          => $slug,
                'description'   => $body['description']   ?? null,
                'price'         => (float)($body['price'] ?? 0),
                'billing_cycle' => $body['billing_cycle'] ?? 'monthly',
                'currency'      => $body['currency']      ?? 'USD',
                'max_licenses'  => $body['max_licenses']  ? (int)$body['max_licenses'] : null,
                'max_domains'   => $body['max_domains']   ? (int)$body['max_domains']  : null,
                'is_active'     => isset($body['is_active']) ? 1 : 0,
                'created_at'    => date('Y-m-d H:i:s'),
            ])->run();
            return $this->redirect('/dashboard/membership-plans');
        } catch (\Throwable $e) {
            return $this->htmlResponse($this->adminPage('Add Plan', $this->notice($e->getMessage(), 'error') . $this->renderPlanForm($body, '/dashboard/membership-plans/create')));
        }
    }

    public function create(Request $request): Response
    {
        $plans = $this->db()->select('id', 'name', 'billing_cycle')->from('optilarity_membership_plans')->where('is_active', 1)->fetchAll();
        $form  = $this->renderForm([], '/dashboard/memberships/create', 'POST', $plans);
        return $this->htmlResponse(
            $this->adminPage('New Membership', $form, ['breadcrumbs' => ['Memberships' => '/dashboard/memberships', 'New' => '']])
        );
    }

    public function store(Request $request): Response
    {
        $body  = (array)$request->post();
        $plans = $this->db()->select('id', 'name')->from('optilarity_membership_plans')->fetchAll();
        try {
            $this->db()->insert('optilarity_memberships')->values([
                'customer_id'    => (int)($body['customer_id']   ?? 0),
                'plan_id'        => (int)($body['plan_id']        ?? 0),
                'status'         => $body['status']               ?? 'active',
                'start_date'     => $body['start_date']           ?? date('Y-m-d'),
                'end_date'       => $body['end_date']             ?: null,
                'auto_renew'     => isset($body['auto_renew'])    ? 1 : 0,
                'notes'          => $body['notes']                ?? null,
                'created_at'     => date('Y-m-d H:i:s'),
            ])->run();
            return $this->redirect('/dashboard/memberships');
        } catch (\Throwable $e) {
            return $this->htmlResponse($this->adminPage('New Membership', $this->notice($e->getMessage(), 'error') . $this->renderForm($body, '/dashboard/memberships/create', 'POST', $plans)));
        }
    }

    public function edit(Request $request, int $id): Response
    {
        $row   = $this->db()->select('*')->from('optilarity_memberships')->where('id', $id)->run()->fetch();
        $plans = $this->db()->select('id', 'name')->from('optilarity_membership_plans')->fetchAll();
        if (!$row) { return $this->htmlResponse($this->adminPage('Not Found', $this->notice('Membership not found.', 'error')), 404); }
        return $this->htmlResponse(
            $this->adminPage('Edit Membership', $this->renderForm((array)$row, "/dashboard/memberships/{$id}/edit", 'PUT', $plans), ['breadcrumbs' => ['Memberships' => '/dashboard/memberships', 'Edit' => '']])
        );
    }

    public function update(Request $request, int $id): Response
    {
        $body = (array)$request->post();
        try {
            $this->db()->update('optilarity_memberships', [
                'status'     => $body['status']   ?? 'active',
                'end_date'   => $body['end_date'] ?: null,
                'auto_renew' => isset($body['auto_renew']) ? 1 : 0,
                'notes'      => $body['notes']    ?? null,
            ], ['id' => $id]);
            return $this->redirect('/dashboard/memberships');
        } catch (\Throwable $e) {
            return $this->htmlResponse($this->adminPage('Edit Membership', $this->notice($e->getMessage(), 'error')));
        }
    }

    private function renderPlanList(array $plans): string
    {
        if (empty($plans)) {
            return $this->notice('No plans defined yet. Create your first plan.', 'info');
        }
        $rows = '';
        foreach ($plans as $p) {
            $status = $p['is_active'] ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-expired">Inactive</span>';
            $price  = '$' . number_format((float)$p['price'], 2) . ' / ' . $p['billing_cycle'];
            $rows  .= "<tr><td><strong>{$p['name']}</strong></td><td>{$price}</td><td>{$status}</td>"
                    . "<td><a href='/dashboard/membership-plans/{$p['id']}/edit' class='presto-btn presto-btn-secondary' style='padding:4px 12px;font-size:12px'>Edit</a></td></tr>";
        }
        return "<div class='presto-table-wrap'><table class='presto-list-table'>"
             . "<thead><tr><th>Plan</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>"
             . "<tbody>{$rows}</tbody></table></div>";
    }

    private function renderPlanForm(array $data = [], string $action = ''): string
    {
        $cycleOpts    = ['monthly' => 'Monthly', 'yearly' => 'Yearly', 'lifetime' => 'Lifetime'];
        $currencyOpts = ['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'VND' => 'VND'];

        $fields = $this->fieldGroup('Plan Name *',     $this->input('name', 'text', $data['name'] ?? '', 'e.g. Pro', true))
                . $this->fieldGroup('Description',     $this->textarea('description', $data['description'] ?? ''))
                . $this->fieldGroup('Price',           $this->input('price', 'number', $data['price'] ?? 0))
                . $this->fieldGroup('Billing Cycle',   $this->select('billing_cycle', $cycleOpts, $data['billing_cycle'] ?? 'monthly'))
                . $this->fieldGroup('Currency',        $this->select('currency', $currencyOpts, $data['currency'] ?? 'USD'))
                . $this->fieldGroup('Max Licenses',    $this->input('max_licenses', 'number', $data['max_licenses'] ?? ''), 'Leave blank for unlimited.')
                . $this->fieldGroup('Max Domains',     $this->input('max_domains',  'number', $data['max_domains']  ?? ''))
                . $this->fieldGroup('Active',          $this->checkbox('is_active', (bool)($data['is_active'] ?? true), 'Plan is available for purchase'))
                . $this->submitBar('Save Plan', '/dashboard/membership-plans');

        return $this->formCard('Plan Details', $this->formOpen($action) . $fields . $this->formClose());
    }

    private function renderForm(array $data = [], string $action = '', string $method = 'POST', array $plans = []): string
    {
        $statusOpts = ['active' => 'Active', 'trialing' => 'Trialing', 'cancelled' => 'Cancelled', 'expired' => 'Expired'];
        $planOpts   = array_column($plans, 'name', 'id');

        $fields = $this->fieldGroup('Customer ID *',   $this->input('customer_id', 'number', $data['customer_id'] ?? '', '', true))
                . $this->fieldGroup('Plan *',          $this->select('plan_id', $planOpts, $data['plan_id'] ?? ''))
                . $this->fieldGroup('Status',          $this->select('status', $statusOpts, $data['status'] ?? 'active'))
                . $this->fieldGroup('Start Date',      $this->input('start_date', 'date', $data['start_date'] ?? date('Y-m-d')))
                . $this->fieldGroup('End Date',        $this->input('end_date',   'date', $data['end_date']   ?? ''), 'Leave blank for lifetime.')
                . $this->fieldGroup('Auto-Renew',      $this->checkbox('auto_renew', (bool)($data['auto_renew'] ?? false), 'Automatically renew on expiry'))
                . $this->fieldGroup('Notes',           $this->textarea('notes', $data['notes'] ?? ''))
                . $this->submitBar('Save Membership', '/dashboard/memberships');

        return $this->formCard('Membership Details', $this->formOpen($action, $method) . $fields . $this->formClose());
    }
}
